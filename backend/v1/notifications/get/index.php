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

// optional input language (default "it" if not provided or invalid)
$requested_language = 'it';
if (isset($post["language"]) && is_string($post["language"])) {
    if (preg_match('/^[a-z]{2}$/i', $post["language"])) {
        $requested_language = strtolower($post["language"]);
    }
}

// optional pagination params (limit, offset)
$limit = 100; // default maximum items
$offset = 0;
if (isset($post['limit'])) {
    // allow numeric strings and ints; enforce limits
    if (is_numeric($post['limit'])) {
        $l = intval($post['limit']);
        if ($l > 0) {
            // cap maximum limit to prevent abuse
            $limit = min($l, 100);
        }
    }
}
if (isset($post['offset'])) {
    if (is_numeric($post['offset'])) {
        $o = intval($post['offset']);
        if ($o >= 0) {
            $offset = $o;
        }
    }
}

$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $posts_table, $emotions_table, $notifications_table, $notifications_read_table;
        global $emotions_followed_table, $users_followed_table;

        $login_id = $post["login-id"];
        $user_id = null;

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`language` AS `language` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];
                $user_language = $row["language"];

                // Validate that the emotions table has the requested language column to avoid unknown column errors
                $lang_col = $requested_language;
                $col_exists = false;
                $stmt_col = $c->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
                if ($stmt_col) {
                    $stmt_col->bind_param("sss", $name_db, $emotions_table, $lang_col);
                    $stmt_col->execute();
                    $res_col = $stmt_col->get_result();
                    if ($res_col && ($r = $res_col->fetch_assoc())) {
                        $col_exists = intval($r['cnt']) > 0;
                    }
                    $stmt_col->close();
                }

                // Build the emotion text expression depending on column existence
                if ($col_exists) {
                    // safe column name already validated to 2 letters via requested_language regex
                    // Return the emotion text when the POST has an emotion-id (not only when the user follows it)
                    $emotion_text_expr = "CASE WHEN `posts`.`emotion-id` IS NOT NULL THEN `emotions`.`$lang_col` ELSE NULL END AS `post-emotion-text`,";
                } else {
                    $emotion_text_expr = "NULL AS `post-emotion-text`,";
                }

                $query_get_notifications = "SELECT
        `notifications`.`notification-id` AS `notification-id`,
        `notifications`.`post-id` AS `post-id`,
        $emotion_text_expr
        CASE WHEN `ef`.`emotion-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-emotion`,
        CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user`,
        CASE WHEN `notifications-read`.`notification-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-read`,
        `post-user`.`username` AS `username`,
        `post-user`.`profile-image` AS `profile-image`,
        `notifications`.`created` AS `notification-datetime`
    FROM $notifications_table AS `notifications`
    INNER JOIN (SELECT * FROM $posts_table WHERE `visibility` = 0 AND `user-id` != ? AND `language` = ?) AS `posts` ON `notifications`.`post-id` = `posts`.`post-id`
    LEFT JOIN (SELECT * FROM $notifications_read_table WHERE `user-id` = ?) AS `notifications-read` ON `notifications`.`notification-id` = `notifications-read`.`notification-id`
    LEFT JOIN (SELECT `emotion-id` FROM $emotions_followed_table WHERE `user-id` = ?) AS `ef` ON `ef`.`emotion-id` = `posts`.`emotion-id`
    LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id`
    LEFT JOIN $emotions_table AS `emotions` ON `posts`.`emotion-id` = `emotions`.`emotion-id`
    LEFT JOIN $users_table AS `post-user` ON `posts`.`user-id` = `post-user`.`user-id`
    WHERE (`ef`.`emotion-id` IS NOT NULL OR `uf`.`followed-user-id` IS NOT NULL)
    ORDER BY `notifications`.`created` DESC
    LIMIT ?
    OFFSET ?";

                $stmt_get_notifications = $c->prepare($query_get_notifications);
                if ($stmt_get_notifications === false) {
                    throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                }
                // bind parameters in the order of placeholders: posts.user-id != ?, posts.language = ?, notifications-read.user-id = ?, ef.user-id = ?, uf.user-id = ?, limit = ?, offset = ?
                $params = array($user_id, $requested_language, $user_id, $user_id, $user_id, $limit, $offset);
                // build types string: first five are strings, last two are integers
                $types = '';
                for ($i = 0; $i < count($params); $i++) {
                    if ($i < 5) {
                        $types .= 's';
                    } else {
                        $types .= 'i';
                        // ensure integer values are actually ints
                        $params[$i] = intval($params[$i]);
                    }
                }

                $bind_names = array();
                $bind_names[] = &$types;
                for ($i = 0; $i < count($params); $i++) {
                    $bind_names[] = &$params[$i];
                }
                call_user_func_array(array($stmt_get_notifications, 'bind_param'), $bind_names);

                // execute and handle errors; then fetch results and respond
                if (!$stmt_get_notifications->execute()) {
                    // execution failed
                    $err = $stmt_get_notifications->error;
                    $stmt_get_notifications->close();
                    responseError(500, "Database execute error: " . $err);
                }

                $result_notifications = $stmt_get_notifications->get_result();
                $get_notifications = array();
                while ($row_notification = $result_notifications->fetch_assoc()) {
                    if (isset($row_notification["is-emotion"])) {
                        //convert to boolean
                        $row_notification["is-emotion"] = $row_notification["is-emotion"] === 1 ? true : false;
                    }
                    if (isset($row_notification["is-user"])) {
                        //convert to boolean
                        $row_notification["is-user"] = $row_notification["is-user"] === 1 ? true : false;
                    }
                    if (isset($row_notification["is-read"])) {
                        //convert to boolean
                        $row_notification["is-read"] = $row_notification["is-read"] === 1 ? true : false;
                    }
                    array_push($get_notifications, $row_notification);
                }
                $stmt_get_notifications->close();

                responseSuccess(200, null, $get_notifications);

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