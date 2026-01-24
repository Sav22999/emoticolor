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


$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $users_followed_table;

        $login_id = $post["login-id"];
        $user_id = null;

        $action = null;

        $query_get_users = "SELECT `users-followed-minimal`.*, `users-followed-details`.`username` AS `followed-username`, `users-followed-details`.`profile-image` AS `followed-profile-image` FROM $users_table AS `users-followed-details` LEFT JOIN (SELECT `users-followed`.`follow-id`, `users-followed`.`user-id`, `users-followed`.`followed-user-id` FROM $users_followed_table AS `users-followed` INNER JOIN (SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1) AS `users-users` ON `users-followed`.`user-id` = `users-users`.`user-id`) AS `users-followed-minimal` ON `users-followed-minimal`.`followed-user-id` = `users-followed-details`.`user-id`";
        $stmt_get_users = $c->prepare($query_get_users);
        $stmt_get_users->bind_param("s", $login_id);

        try {
            $stmt_get_users->execute();
            $result = $stmt_get_users->get_result();

            if ($result->num_rows > 0) {
                $rows = array();
                while ($row = $result->fetch_assoc()) {
                    if (isset($row["follow-id"]) && isset($row["user-id"]) && isset($row["followed-user-id"])) {
                        $rows[] = $row;
                    }
                }

                responseSuccess(200, null, array_values($rows));
            } else {
                //no users followed found
                responseError(404, "No users followed found");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_users->close();

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