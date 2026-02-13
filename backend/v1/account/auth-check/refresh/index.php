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

$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"] && isset($post["token-id"]) && checkFieldValidity($post["token-id"]));
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $refresh_tokens_table;

        $login_id = $post["login-id"];
        $token_id = $post["token-id"];
        $user_id = null;

        $action = null;

        $query_get_user_id = "SELECT `refresh-tokens`.`token-id` AS `token-id`, `users-filtered`.`otp-id` AS `otp-id`, `users-filtered`.`user-id` AS `user-id` FROM $refresh_tokens_table AS `refresh-tokens` INNER JOIN (SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status`, `logins-otps`.`otp-id` AS `otp-id` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` IS NOT NULL AND `logins`.`valid-until` <= CURRENT_TIMESTAMP) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1) AS `users-filtered` ON `refresh-tokens`.`user-id` = `users-filtered`.`user-id` WHERE `refresh-tokens`.`valid-until` >= CURRENT_TIME AND `refresh-tokens`.`token-id` = ?";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("ss", $login_id, $token_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];
                $otp_id = $row["otp-id"];
                $new_login_id = generateUUIDv4();

                //create a new login-id and return it
                $query_insert_login = "INSERT INTO $logins_table (`login-id`, `user-id`, `otp-id`, `once-time`, `created`, `valid-until`) VALUES (?, ?, ?, 0, CURRENT_TIME, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY))";
                $stmt_insert_login = $c->prepare($query_insert_login);
                $stmt_insert_login->bind_param("sss", $new_login_id, $user_id, $otp_id);
                try {
                    $stmt_insert_login->execute();

                    //delete the old login-id
                    $query_delete_old_login = "DELETE FROM $logins_table WHERE `login-id` = ?";
                    $stmt_delete_old_login = $c->prepare($query_delete_old_login);
                    $stmt_delete_old_login->bind_param("s", $login_id);
                    try {
                        $stmt_delete_old_login->execute();
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_delete_old_login->close();

                    global $logs_table;
                    addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "refresh-login-id", getIpAddress());

                    responseSuccess(200, "Generated a new login-id", array("login-id" => $new_login_id));
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_insert_login->close();
            } else {
                //unauthorized: token-id not found or expired
                responseError(440, "Unauthorized: invalid, not expired login-id or expired token-id");
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
    if (!isset($post["token-id"]) || !checkFieldValidity($post["token-id"])) {
        array_push($missing_parameters, "token-id");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>