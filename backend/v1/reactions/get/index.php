<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/logs.php");
global $localhost_db, $username_db, $password_db, $name_db;
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $post = json_decode(file_get_contents('php://input'), true);
} else {
    $post = $_POST;
}
$get = $_GET; //GET request


//use the following code of example for AUTHENTICATED requests
$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $reactions_table, $reactions_posts_table, $posts_table;

        $login_id = $post["login-id"];
        $user_id = null;

        $post_id = null;
        if (isset($post["post-id"]) && checkFieldValidity($post["post-id"])) {
            $post_id = $post["post-id"];
        }

                // If a post-id was provided, verify the post actually exists and load its owner
                $post_owner_id = null;
                if ($post_id !== null) {
                    try {
                        $q_check_post = "SELECT `post-id`, `user-id` AS `owner-id` FROM " . $posts_table . " WHERE `post-id` = ? LIMIT 1";
                        $st_check_post = $c->prepare($q_check_post);
                        if ($st_check_post === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                        $st_check_post->bind_param("s", $post_id);
                        $st_check_post->execute();
                        $res_check_post = $st_check_post->get_result();
                        if ($res_check_post->num_rows === 0) {
                            $st_check_post->close();
                            responseError(404, "Post not found.");
                        }
                        $rpost = $res_check_post->fetch_assoc();
                        $post_owner_id = isset($rpost['owner-id']) ? $rpost['owner-id'] : null;
                        $st_check_post->close();
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                }

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];

                // --- Fetch all reactions and their icon ids (if any) ---
                $reactions_list = array();

                // detect icon-like column in reactions table
                $icon_col = null;
                try {
                    $q_icon = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                    $st_icon = $c->prepare($q_icon);
                    if ($st_icon !== false) {
                        $tbln = $reactions_table;
                        $st_icon->bind_param("ss", $name_db, $tbln);
                        $st_icon->execute();
                        $res_ic = $st_icon->get_result();
                        $ric = $res_ic->fetch_assoc();
                        if ($ric && isset($ric['COLUMN_NAME']) && $ric['COLUMN_NAME'] !== '') $icon_col = $ric['COLUMN_NAME'];
                        $st_icon->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    $icon_col = null;
                }

                if ($icon_col !== null) {
                    $q_reacts = "SELECT `reaction-id`, `" . $icon_col . "` AS `icon_id` FROM " . $reactions_table . " ORDER BY `reaction-id` ASC";
                } else {
                    $q_reacts = "SELECT `reaction-id`, NULL AS `icon_id` FROM " . $reactions_table . " ORDER BY `reaction-id` ASC";
                }

                $st_reacts = $c->prepare($q_reacts);
                if ($st_reacts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                try {
                    $st_reacts->execute();
                    $res_reacts = $st_reacts->get_result();
                    while ($r = $res_reacts->fetch_assoc()) {
                        $reactions_list[$r['reaction-id']] = array('reaction-id' => (int)$r['reaction-id'], 'reaction-icon-id' => isset($r['icon_id']) ? $r['icon_id'] : null);
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $st_reacts->close();

                // build final output array according to rules:
                // always return objects: { 'reaction-id', 'reaction-icon-id', 'is-inserted', 'count' }
                $out = array();

                if ($post_id === null) {
                    // no post specified: is-inserted=null, count=null
                    foreach ($reactions_list as $rid => $info) {
                        $out[] = array(
                            'reaction-id' => (int)$info['reaction-id'],
                            'reaction-icon-id' => isset($info['reaction-icon-id']) ? $info['reaction-icon-id'] : null,
                            'is-inserted' => null,
                            'count' => null
                        );
                    }
                } else {
                    // post specified: behavior depends on whether requester is the post owner
                    if ($post_owner_id !== null && $post_owner_id === $user_id) {
                        // requester is owner: return counts per reaction for that post, is-inserted = null
                        $counts_map = array();
                        try {
                            $q_counts = "SELECT `reaction-id`, COUNT(*) AS `cnt` FROM " . $reactions_posts_table . " WHERE `post-id` = ? GROUP BY `reaction-id`";
                            $st_counts = $c->prepare($q_counts);
                            if ($st_counts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            $st_counts->bind_param("s", $post_id);
                            $st_counts->execute();
                            $res_counts = $st_counts->get_result();
                            while ($cr = $res_counts->fetch_assoc()) {
                                $counts_map[$cr['reaction-id']] = (int)$cr['cnt'];
                            }
                            $st_counts->close();
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }

                        foreach ($reactions_list as $rid => $info) {
                            $out[] = array(
                                'reaction-id' => (int)$info['reaction-id'],
                                'reaction-icon-id' => isset($info['reaction-icon-id']) ? $info['reaction-icon-id'] : null,
                                'is-inserted' => null,
                                'count' => isset($counts_map[$rid]) ? (int)$counts_map[$rid] : 0
                            );
                        }
                    } else {
                        // requester is not owner: return is-inserted for the requester, count = null
                        $user_inserted = array();
                        try {
                            $q_user_ins = "SELECT `reaction-id` FROM " . $reactions_posts_table . " WHERE `post-id` = ? AND `user-id` = ?";
                            $st_user_ins = $c->prepare($q_user_ins);
                            if ($st_user_ins === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            $st_user_ins->bind_param("ss", $post_id, $user_id);
                            $st_user_ins->execute();
                            $res_ui = $st_user_ins->get_result();
                            while ($ur = $res_ui->fetch_assoc()) {
                                $user_inserted[$ur['reaction-id']] = true;
                            }
                            $st_user_ins->close();
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }

                        foreach ($reactions_list as $rid => $info) {
                            $out[] = array(
                                'reaction-id' => (int)$info['reaction-id'],
                                'reaction-icon-id' => isset($info['reaction-icon-id']) ? $info['reaction-icon-id'] : null,
                                'is-inserted' => isset($user_inserted[$rid]) ? true : false,
                                'count' => null
                            );
                        }
                    }
                }

                responseSuccess(200, null, $out);

            } else {
                //unauthorized: login-id not found or expired
                responseError(440, "Unauthorized: invalid or expired login-id");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_user_id->close();

        $c->close();
    } else {
        responseError(500);
    }
} else {
    //bad request: missing parameters
    $missing_parameters = array();
    if (!isset($post["login-id"]) || !checkFieldValidity($post["login-id"])) {
        array_push($missing_parameters, "login-id");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>