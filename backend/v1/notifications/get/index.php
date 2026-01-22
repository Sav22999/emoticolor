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
$post = $get;//TODO: to be removed, only for testing with GET requests

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
                $language = $row["language"];

                // NOTE: the `notifications` table is treated as a lightweight mapping (notification-id, post-id, action, created)
                // It intentionally DOES NOT need to store the `user-id`, `emotion-id` or `language` because those are
                // obtained from the `posts` table via the `post-id` foreign key. This simplifies storage and avoids
                // denormalization. The query below uses `posts`.`emotion-id` and `posts`.`language` and does not
                // reference `notifications`.`user-id` anywhere.

                // If notifications table doesn't contain emotion-id or language, read them from posts
                // include language constraint: only posts with the same language as the user
                // obtain emotion-id and post's user-id from posts (notifications table does not store them)
                $query_get_notifications = "SELECT
    `notifications`.`notification-id` AS `notification-id`,
    `notifications`.`post-id` AS `post-id`,
    -- return post emotion id only when the user follows that emotion
    CASE WHEN `ef`.`emotion-id` IS NOT NULL THEN `posts`.`emotion-id` ELSE NULL END AS `post-emotion-id`,
    -- return post user id only when the user follows that author
    CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN `posts`.`user-id` ELSE NULL END AS `post-user-id`,
    CASE WHEN `ef`.`emotion-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-emotion`,
    CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user`,
    CASE WHEN `notifications-read`.`notification-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-read`
FROM $notifications_table AS `notifications`
INNER JOIN (SELECT * FROM $posts_table WHERE `visibility` = 0 AND `user-id` != ? AND `language` = ?) AS `posts` ON `notifications`.`post-id` = `posts`.`post-id`
LEFT JOIN (SELECT * FROM $notifications_read_table WHERE `user-id` = ?) AS `notifications-read` ON `notifications`.`notification-id` = `notifications-read`.`notification-id`
LEFT JOIN (SELECT `emotion-id` FROM $emotions_followed_table WHERE `user-id` = ?) AS `ef` ON `ef`.`emotion-id` = `posts`.`emotion-id`
LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id`
WHERE (`ef`.`emotion-id` IS NOT NULL OR `uf`.`followed-user-id` IS NOT NULL)
ORDER BY `notifications`.`notification-id` DESC
LIMIT 100";

                $stmt_get_notifications = $c->prepare($query_get_notifications);
                if ($stmt_get_notifications === false) {
                    throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                }
                // bind parameters in the order of placeholders: posts.user-id != ?, posts.language = ?, notifications-read.user-id = ?, ef.user-id = ?, uf.user-id = ?
                $types = 'sssss';
                $params = array($user_id, $language, $user_id, $user_id, $user_id);

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