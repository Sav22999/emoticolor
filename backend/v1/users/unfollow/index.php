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


$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["username"]) && checkUsernameValidity($post["username"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $users_followed_table;

        $login_id = $post["login-id"];
        $username = strtolower(trim($post["username"]));
        $follow_id = null;
        $user_id = null;

        $action = null;

        $query_followed_user = "SELECT `users`.*, `followed-user`.`username` AS `username` FROM $users_table AS `followed-user` INNER JOIN (SELECT `users-followed`.`follow-id`, `users-followed`.`user-id`, `users-followed`.`followed-user-id` FROM $users_followed_table AS `users-followed` INNER JOIN (SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1) AS `users-users` ON `users-followed`.`user-id` = `users-users`.`user-id`) AS `users` ON `followed-user`.`user-id`=`users`.`followed-user-id` WHERE `username` = ?";
        $stmt_get_followed_user = $c->prepare($query_followed_user);
        $stmt_get_followed_user->bind_param("ss", $login_id, $username);

        try {
            $stmt_get_followed_user->execute();
            $result = $stmt_get_followed_user->get_result();

            if ($result->num_rows === 1) {
                //user already followed

                $row = $result->fetch_assoc();
                $follow_id = $row["followed-user-id"];

                //check if the "user-id" exists
                $query_check_user = "SELECT `user-id` FROM $users_table WHERE `username` = ?";
                $stmt_check_user = $c->prepare($query_check_user);
                $stmt_check_user->bind_param("s", $username);

                try {
                    $stmt_check_user->execute();

                    $result_check_user = $stmt_check_user->get_result();
                    if ($result_check_user->num_rows === 1) {
                        //user exists, get user id

                        //get user id from logins
                        $query_get_user_id = "SELECT `logins`.`user-id` AS `user-id` FROM $logins_table AS `logins` WHERE `logins`.`login-id` = ?";
                        $stmt_get_user_id = $c->prepare($query_get_user_id);
                        $stmt_get_user_id->bind_param("s", $login_id);
                        try {
                            $stmt_get_user_id->execute();
                            $result_get_user_id = $stmt_get_user_id->get_result();
                            if ($result_get_user_id->num_rows === 1) {
                                $row_user = $result_get_user_id->fetch_assoc();
                                $user_id = $row_user["user-id"];

                                //remove follow user
                                $query_remove_follow = "DELETE FROM $users_followed_table WHERE `user-id` = ? AND `followed-user-id` = ?";
                                $stmt_remove_follow = $c->prepare($query_remove_follow);
                                $stmt_remove_follow->bind_param("ss", $user_id, $follow_id);

                                try {
                                    $stmt_remove_follow->execute();

                                    responseSuccess(204, null, null);
                                } catch (mysqli_sql_exception $e) {
                                    responseError(500, "Database error: " . $e->getMessage());
                                }
                                $stmt_remove_follow->close();
                            } else {
                                responseError(500, "User not found.");
                            }
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_get_user_id->close();
                    } else {
                        //user doesn't exist
                        responseError(404, "User not found.");
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_check_user->close();
            } else {
                //user not already followed
                responseError(404, "User not followed.");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_followed_user->close();

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
    if (!isset($post["username"]) || !checkUsernameValidity($post["username"])) {
        array_push($missing_parameters, "username");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>