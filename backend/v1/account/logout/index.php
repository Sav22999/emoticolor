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

        global $logins_table, $users_table, $refresh_tokens_table, $otps_table;

        $login_id = $post["login-id"];
        $token_id = $post["token-id"];
        $logout_all_devices = false;
        if (isset($post["invalid-all-sessions"]) && $post["invalid-all-sessions"] === "true") {
            $logout_all_devices = true;
        }
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

                //update "refresh-tokens" table, "logins" table to invalidate all sessions for this user (delete rows)
                $query_delete_tokens = "DELETE FROM $refresh_tokens_table WHERE `user-id` = ?";
                if (!$logout_all_devices) {
                    $query_delete_tokens = "DELETE FROM $refresh_tokens_table WHERE `user-id` = ? AND `token-id` = ?";
                }
                $stmt_delete_tokens = $c->prepare($query_delete_tokens);
                if (!$logout_all_devices) {
                    $stmt_delete_tokens->bind_param("ss", $user_id, $token_id);
                } else {
                    $stmt_delete_tokens->bind_param("s", $user_id);
                }
                try {
                    //delete all refresh tokens for this user
                    $stmt_delete_tokens->execute();

                    //delete all logins for this user
                    $query_delete_logins = "DELETE FROM $logins_table WHERE `user-id` = ?";
                    if (!$logout_all_devices) {
                        $query_delete_logins = "DELETE FROM $logins_table WHERE `user-id` = ? AND `login-id` = ?";
                    }
                    $stmt_delete_logins = $c->prepare($query_delete_logins);
                    if (!$logout_all_devices) {
                        $stmt_delete_logins->bind_param("ss", $user_id, $login_id);
                    } else {
                        $stmt_delete_logins->bind_param("s", $user_id);
                    }

                    try {
                        $stmt_delete_logins->execute();

                        global $logs_table;
                        $logout_string = $logout_all_devices ? "logout-all-devices" : "logout";
                        addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, $logout_string, getIpAddress());

                        responseSuccess(204, null, null);
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_delete_logins->close();
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_delete_tokens->close();
            } else {
                responseError(401, "Invalid login-id");
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