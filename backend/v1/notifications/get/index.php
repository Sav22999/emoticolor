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

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];

                $query_get_emotions_followed = "SELECT `emotions-followed`.`emotion-id` AS `emotion-id` FROM $emotions_followed_table AS `emotions-followed` INNER JOIN (SELECT * FROM $emotions_table) AS `emotions` ON `emotions-followed`.`emotion-id` = `emotions`.`emotion-id` WHERE `user-id` = ?";
                $stmt_get_emotions_followed = $c->prepare($query_get_emotions_followed);
                $stmt_get_emotions_followed->bind_param("s", $user_id);
                $stmt_get_emotions_followed->execute();
                $result_emotions_followed = $stmt_get_emotions_followed->get_result();
                $emotions_followed = array();
                while ($row_emotion = $result_emotions_followed->fetch_assoc()) {
                    array_push($emotions_followed, $row_emotion["emotion-id"]);
                }
                $stmt_get_emotions_followed->close();

                $query_get_users_followed = "SELECT `users-followed`.`followed-user-id` AS `followed-user-id` FROM $users_followed_table AS `users-followed` INNER JOIN (SELECT * FROM $users_table WHERE `status` = 1) AS `users` ON `users-followed`.`followed-user-id` = `users`.`user-id` WHERE `users-followed`.`user-id` = ?";
                $stmt_get_users_followed = $c->prepare($query_get_users_followed);
                $stmt_get_users_followed->bind_param("s", $user_id);
                $stmt_get_users_followed->execute();
                $result_users_followed = $stmt_get_users_followed->get_result();
                $users_followed = array();
                while ($row_user = $result_users_followed->fetch_assoc()) {
                    array_push($users_followed, $row_user["followed-user-id"]);
                }
                $stmt_get_users_followed->close();

                //get notifications –only from emotion-id IN emotions-followed and user-id IN users-followed, then join with "posts" to ensure the post it's not by the user themself
                //and then join with "notifications-read" to check if the notification has been read or not (all notifications are returned, both read and unread)
                //useful fields of posts: post-id, user-id and visibility (only =0)
                //useful fields of notifications-read: notification-id, user-id (which is the same as $user_id)
                //useful fields of notifications: notification-id, post-id, emotion-id

                $get_notifications = array();
                if (count($emotions_followed) > 0 || count($users_followed) > 0) {
                    // build placeholders only for non-empty lists
                    $placeholders_emotions = '';
                    $placeholders_users = '';
                    if (count($emotions_followed) > 0) {
                        $placeholders_emotions = implode(',', array_fill(0, count($emotions_followed), '?'));
                    }
                    if (count($users_followed) > 0) {
                        $placeholders_users = implode(',', array_fill(0, count($users_followed), '?'));
                    }

                    // build WHERE clause parts depending on which lists are present
                    $where_parts = array();
                    if ($placeholders_emotions !== '') {
                        $where_parts[] = "`notifications`.`emotion-id` IN ($placeholders_emotions)";
                    }
                    if ($placeholders_users !== '') {
                        $where_parts[] = "`posts`.`user-id` IN ($placeholders_users)";
                    }

                    // base query: two placeholders for user-id are always needed (posts != ? and notifications-read.user-id = ?)
                    $query_get_notifications = "SELECT `notifications`.`notification-id` AS `notification-id`, `notifications`.`post-id` AS `post-id`, `notifications`.`emotion-id` AS `emotion-id`, CASE WHEN `notifications-read`.`notification-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-read` FROM $notifications_table AS `notifications` INNER JOIN (SELECT * FROM $posts_table WHERE `visibility` = 0 AND `user-id` != ?) AS `posts` ON `notifications`.`post-id` = `posts`.`post-id` LEFT JOIN (SELECT * FROM $notifications_read_table WHERE `user-id` = ?) AS `notifications-read` ON `notifications`.`notification-id` = `notifications-read`.`notification-id`";

                    if (count($where_parts) > 0) {
                        $query_get_notifications .= " WHERE (" . implode(' OR ', $where_parts) . ")";
                    }
                    $query_get_notifications .= " ORDER BY `notifications`.`notification-id` DESC LIMIT 100";

                    $stmt_get_notifications = $c->prepare($query_get_notifications);
                    if ($stmt_get_notifications === false) {
                        throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                    }

                    // prepare types and params in correct order matching the placeholders in the query
                    // order: first user_id (posts != ?), second user_id (notifications-read.user-id = ?), then emotions, then users
                    $types = '';
                    $params = array();

                    // two user-id params
                    $types .= 'ss';
                    $params[] = $user_id; // for posts != ?
                    $params[] = $user_id; // for notifications-read.user-id = ?

                    // emotions params (if any)
                    foreach ($emotions_followed as $e) {
                        $types .= 's';
                        $params[] = $e;
                    }

                    // users params (if any)
                    foreach ($users_followed as $u) {
                        $types .= 's';
                        $params[] = $u;
                    }

                    // bind params using references (required by mysqli)
                    $bind_names = array();
                    // first element must be a reference to the types string
                    $bind_names[] = & $types;
                    for ($i = 0; $i < count($params); $i++) {
                        $bind_names[] = & $params[$i];
                    }
                    call_user_func_array(array($stmt_get_notifications, 'bind_param'), $bind_names);

                    $stmt_get_notifications->execute();
                    $result_notifications = $stmt_get_notifications->get_result();
                    while ($row_notification = $result_notifications->fetch_assoc()) {
                        array_push($get_notifications, $row_notification);
                    }
                    $stmt_get_notifications->close();
                }

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