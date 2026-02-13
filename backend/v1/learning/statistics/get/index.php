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

        global $logins_table, $users_table, $otps_table;
        global $learning_contents_table, $learning_statistics_table, $emotions_table;

        $login_id = $post["login-id"];
        $user_id = null;

        $emotion_id = null;
        if (isset($post["emotion-id"]) && is_numeric($post["emotion-id"])) {
            $emotion_id = intval($post["emotion-id"]);
        }

        // language handling: default 'it'
        $language = 'it';
        if (isset($post["language"]) && is_string($post["language"])) {
            if (preg_match('/^[a-z]{2}$/i', $post["language"])) {
                $language = strtolower($post["language"]);
            }
        }

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];

                // if emotion-id passed, verify it exists
                if ($emotion_id !== null) {
                    $query_check_emotion = "SELECT `emotion-id` FROM $emotions_table WHERE `emotion-id` = ?";
                    $stmt_check_emotion = $c->prepare($query_check_emotion);
                    $stmt_check_emotion->bind_param("s", $emotion_id);
                    try {
                        $stmt_check_emotion->execute();
                        $result_check_emotion = $stmt_check_emotion->get_result();
                        if ($result_check_emotion->num_rows !== 1) {
                            responseError(404, "Emotion not found.");
                        }
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_check_emotion->close();
                }

                //code here
                try {
                    if ($emotion_id !== null) {
                        $query_get_stats = "SELECT `statistic-id`, `emotion-id`, `type`, `created` FROM $learning_statistics_table WHERE `user-id` = ? AND `emotion-id` = ? ORDER BY `created` DESC";
                        $stmt_get_stats = $c->prepare($query_get_stats);
                        $stmt_get_stats->bind_param("ss", $user_id, $emotion_id);
                    } else {
                        $query_get_stats = "SELECT `statistic-id`, `emotion-id`, `type`, `created` FROM $learning_statistics_table WHERE `user-id` = ? ORDER BY `created` DESC";
                        $stmt_get_stats = $c->prepare($query_get_stats);
                        $stmt_get_stats->bind_param("s", $user_id);
                    }

                    $stmt_get_stats->execute();
                    $result_stats = $stmt_get_stats->get_result();

                    $rows = array();
                    while ($r = $result_stats->fetch_assoc()) {
                        array_push($rows, $r);
                    }

                    // If there are any emotion-ids in the result, fetch their text/description/banner in bulk
                    if (count($rows) > 0) {
                        $emotion_ids = array();
                        foreach ($rows as $rr) {
                            if (isset($rr['emotion-id'])) {
                                $eid = intval($rr['emotion-id']);
                                $emotion_ids[$eid] = $eid;
                            }
                        }

                        if (count($emotion_ids) > 0) {
                            // prepare IN clause
                            $placeholders = implode(',', array_fill(0, count($emotion_ids), '?'));
                            $lang_col = preg_match('/^[a-z]{2}$/', $language) ? $language : 'it';
                            $desc_col = 'description-' . $lang_col;

                            // check if the language columns actually exist in the emotions table
                            $table_name = trim($emotions_table, "`\n\r \t");
                            $col_text_exists = false;
                            $col_desc_exists = false;
                            try {
                                $qcol = "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
                                // check text column (e.g., 'en')
                                $stmt_col = $c->prepare($qcol);
                                if ($stmt_col) {
                                    $colname = $lang_col;
                                    $stmt_col->bind_param("sss", $name_db, $table_name, $colname);
                                    $stmt_col->execute();
                                    $rc = $stmt_col->get_result();
                                    if ($rc && $rcc = $rc->fetch_assoc()) {
                                        $col_text_exists = intval($rcc['c']) > 0;
                                    }
                                    $stmt_col->close();
                                }

                                // check description column (e.g., 'description-en')
                                $stmt_col2 = $c->prepare($qcol);
                                if ($stmt_col2) {
                                    $colname2 = $desc_col;
                                    $stmt_col2->bind_param("sss", $name_db, $table_name, $colname2);
                                    $stmt_col2->execute();
                                    $rc2 = $stmt_col2->get_result();
                                    if ($rc2 && $rcc2 = $rc2->fetch_assoc()) {
                                        $col_desc_exists = intval($rcc2['c']) > 0;
                                    }
                                    $stmt_col2->close();
                                }
                            } catch (mysqli_sql_exception $e) {
                                // if information_schema query fails, assume columns don't exist
                                $col_text_exists = false;
                                $col_desc_exists = false;
                            }

                            // build select list conditionally: if column exists select it, otherwise select NULL
                            $select_text = $col_text_exists ? "`$lang_col` AS `emotion-text`" : "NULL AS `emotion-text`";
                            $select_desc = $col_desc_exists ? "`$desc_col` AS `emotion-description`" : "NULL AS `emotion-description`";

                            $query_em = "SELECT `emotion-id`, $select_text, $select_desc, `banner-url` AS `emotion-banner-url` FROM $emotions_table WHERE `emotion-id` IN ($placeholders)";
                            $stmt_em = $c->prepare($query_em);
                            if ($stmt_em) {
                                // bind params dynamically
                                $ids = array_values($emotion_ids);
                                $types = str_repeat('s', count($ids));
                                $bind_names = array();
                                $bind_names[] = $types;
                                for ($i = 0; $i < count($ids); $i++) {
                                    $bind_var = 'b' . $i;
                                    $$bind_var = $ids[$i];
                                    $bind_names[] = &$$bind_var;
                                }
                                call_user_func_array(array($stmt_em, 'bind_param'), $bind_names);

                                try {
                                    $stmt_em->execute();
                                    $res_em = $stmt_em->get_result();
                                    $emotions_map = array();
                                    while ($er = $res_em->fetch_assoc()) {
                                        $eid = intval($er['emotion-id']);
                                        $text = (isset($er['emotion-text']) && $er['emotion-text'] !== null && $er['emotion-text'] !== '') ? $er['emotion-text'] : null;
                                        $description = (isset($er['emotion-description']) && $er['emotion-description'] !== null && $er['emotion-description'] !== '') ? $er['emotion-description'] : null;
                                        $banner = isset($er['emotion-banner-url']) ? $er['emotion-banner-url'] : null;
                                        $emotions_map[$eid] = array(
                                            'emotion-text' => $text,
                                            'emotion-description' => $description,
                                            'emotion-banner-url' => $banner
                                        );
                                    }

                                    // merge emotion fields into each statistics row
                                    foreach ($rows as &$row_item) {
                                        $eid = isset($row_item['emotion-id']) ? intval($row_item['emotion-id']) : null;
                                        if ($eid !== null && isset($emotions_map[$eid])) {
                                            $row_item['emotion-text'] = $emotions_map[$eid]['emotion-text'];
                                            $row_item['emotion-description'] = $emotions_map[$eid]['emotion-description'];
                                            $row_item['emotion-banner-url'] = $emotions_map[$eid]['emotion-banner-url'];
                                        } else {
                                            $row_item['emotion-text'] = null;
                                            $row_item['emotion-description'] = null;
                                            $row_item['emotion-banner-url'] = null;
                                        }
                                    }

                                } catch (mysqli_sql_exception $e) {
                                    // ignore and leave emotion fields null
                                    foreach ($rows as &$row_item) {
                                        $row_item['emotion-text'] = null;
                                        $row_item['emotion-description'] = null;
                                        $row_item['emotion-banner-url'] = null;
                                    }
                                }
                                $stmt_em->close();
                            } else {
                                // statement couldn't be prepared: set fields to null
                                foreach ($rows as &$row_item) {
                                    $row_item['emotion-text'] = null;
                                    $row_item['emotion-description'] = null;
                                    $row_item['emotion-banner-url'] = null;
                                }
                            }
                        }
                    }

                    responseSuccess(200, null, array_values($rows));
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }

                // ...existing code...

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