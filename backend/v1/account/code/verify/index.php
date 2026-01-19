<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/logs.php");
global $localhost_db, $username_db, $password_db, $name_db;
//header("Content-Type:application/json");
$post = json_decode(file_get_contents('php://input'), true); //POST request
//$post = $_POST; //POST request fallback
$get = $_GET; //GET request
$post = $get;//TODO: to be removed, only for testing with GET requests

$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["code"]) && checkFieldValidity($post["code"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $refresh_tokens_table;
        global $emailSecretKeyAES;

        $login_id = $post["login-id"];
        $code = $post["code"];
        $user_id = null;

        $action = null;

        $query_get_otp_code = "SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM `Logins` AS `logins` INNER JOIN `Otps` AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND ((`logins`.`once-time` = 1 AND `otps`.`used` IS NOT NULL AND `otps`.`valid-until` >= CURRENT_TIMESTAMP OR `otps`.`used` IS NULL) OR (`logins`.`once-time` = 0)) AND `logins`.`login-id` = ?";
        $stmt_get_otp_code = $c->prepare($query_get_otp_code);
        $stmt_get_otp_code->bind_param("s", $login_id);

        try {
            $stmt_get_otp_code->execute();
            $result = $stmt_get_otp_code->get_result();
            $stmt_get_otp_code->close();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];
                $login_id_used = $row["login-id"];
                $otp_id_used = $row["otp-id"];
                $once_time = $row["once-time"];
                $action = $row["action"];

                if (checkArgon2idHash($code, $row["code"])) {
                    //mark OTP as used
                    $query_update_otp_used = "UPDATE $otps_table SET `used` = CURRENT_TIMESTAMP WHERE `otp-id` = ?";
                    $stmt_update_otp_used = $c->prepare($query_update_otp_used);
                    $stmt_update_otp_used->bind_param("s", $otp_id_used);
                    try {
                        $stmt_update_otp_used->execute();
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_update_otp_used->close();

                    //check action: it can be "verify-account", "reset-password", "verify-login", etc.
                    if ($action == "verify-account") {
                        //delete record where login-id = $login_id_used in logins table
                        $query_delete_login = "DELETE FROM $logins_table WHERE `login-id` = ?";
                        $stmt_delete_login = $c->prepare($query_delete_login);
                        $stmt_delete_login->bind_param("s", $login_id_used);
                        try {
                            $stmt_delete_login->execute();

                            //set otp-id as used = now() in otps table
                            $query_update_otp_used = "UPDATE $otps_table SET `used` = CURRENT_TIMESTAMP WHERE `otp-id` = ?";
                            $stmt_update_otp_used = $c->prepare($query_update_otp_used);
                            $stmt_update_otp_used->bind_param("s", $otp_id_used);
                            try {
                                $stmt_update_otp_used->execute();
                            } catch (mysqli_sql_exception $e) {
                                responseError(500, "Database error: " . $e->getMessage());
                            }
                            $stmt_update_otp_used->close();
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_delete_login->close();

                        //get user from users table (where user-id = $user_id)
                        $query_get_user = "SELECT * FROM $users_table WHERE `user-id` = ?";
                        $stmt_get_user = $c->prepare($query_get_user);
                        $stmt_get_user->bind_param("s", $user_id);
                        try {
                            $stmt_get_user->execute();
                            $result_get_user = $stmt_get_user->get_result();
                            if ($result_get_user->num_rows === 1) {
                                $row_user = $result_get_user->fetch_assoc();
                                $email_aes = $row_user["email_aes"];
                                $username = $row_user["username"];
                                $email_decrypted = decryptTextWithPassword($email_aes, $emailSecretKeyAES);

                                if ($email_decrypted !== false) {
                                    //set user status to 1 (active) in users table
                                    $query_update_user_status = "UPDATE $users_table SET `status` = 1 WHERE `user-id` = ?";
                                    $stmt_update_user_status = $c->prepare($query_update_user_status);
                                    $stmt_update_user_status->bind_param("s", $user_id);
                                    try {
                                        $stmt_update_user_status->execute();

                                        //add log for the signup
                                        global $logs_table;
                                        addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "verify-signup", getIpAddress());

                                        $email_sent = false;
                                        $email_sent_max_attempts = 3;
                                        $email_sent_number = 0;
                                        do {
                                            $email_sent = sendEmailSignedup($username, $email_decrypted, getIpAddress());
                                            $email_sent_number++;
                                        } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);

                                        if ($email_sent === false) {
                                            $data = array("code" => 555, "user-id" => $user_id);
                                            responseError(555, "Could not send verification email", $data);
                                        } else {
                                            responseSuccess(200, "Account verified successfully.");
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_update_user_status->close();
                                }
                            } else {
                                responseError(500, "User not found.");
                            }
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_get_user->close();
                    } else if ($action == "reset-password") {
                        //responseSuccess -- allow user to reset password
                        //TODO: implement password reset flow
                    } else if ($action == "verify-login") {
                        //set login-id as valid-until = now() in logins table
                        $query_update_login_valid = "UPDATE $logins_table SET `valid-until` = CURRENT_TIMESTAMP WHERE `login-id` = ?";
                        $stmt_update_login_valid = $c->prepare($query_update_login_valid);
                        $stmt_update_login_valid->bind_param("s", $login_id_used);
                        try {
                            $stmt_update_login_valid->execute();

                            //set otp-id as used = now() in otps table
                            $query_update_otp_used = "UPDATE $otps_table SET `used` = CURRENT_TIMESTAMP WHERE `otp-id` = ?";
                            $stmt_update_otp_used = $c->prepare($query_update_otp_used);
                            $stmt_update_otp_used->bind_param("s", $otp_id_used);
                            try {
                                $stmt_update_otp_used->execute();
                            } catch (mysqli_sql_exception $e) {
                                responseError(500, "Database error: " . $e->getMessage());
                            }
                            $stmt_update_otp_used->close();
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_update_login_valid->close();

                        //get user from users table (where user-id = $user_id)
                        $query_get_user = "SELECT * FROM $users_table WHERE `user-id` = ?";
                        $stmt_get_user = $c->prepare($query_get_user);
                        $stmt_get_user->bind_param("s", $user_id);
                        try {
                            $stmt_get_user->execute();
                            $result_get_user = $stmt_get_user->get_result();
                            if ($result_get_user->num_rows === 1) {
                                $row_user = $result_get_user->fetch_assoc();
                                $email_aes = $row_user["email_aes"];
                                $username = $row_user["username"];
                                $email_decrypted = decryptTextWithPassword($email_aes, $emailSecretKeyAES);

                                if ($email_decrypted !== false) {
                                    //insert a new login for the user, valid for 1 day in logins_table
                                    $new_login_id = generateUUIDv4();
                                    $query_insert_login = "INSERT INTO $logins_table (`login-id`, `user-id`, `otp-id`, `once-time`, `created`, `valid-until`) VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY))";
                                    $stmt_insert_login = $c->prepare($query_insert_login);
                                    $stmt_insert_login->bind_param("ss", $new_login_id, $user_id);
                                    try {
                                        $stmt_insert_login->execute();

                                        //insert a new refresh token for the user, valid for 30 days in refresh_tokens_table
                                        $refresh_token_id = generateUUIDv4();
                                        $query_insert_refresh_token = "INSERT INTO $refresh_tokens_table (`token-id`, `user-id`, `created`, `valid-until`) VALUES (?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY))";
                                        $stmt_insert_refresh_token = $c->prepare($query_insert_refresh_token);
                                        $stmt_insert_refresh_token->bind_param("ss", $refresh_token_id, $user_id);
                                        try {
                                            $stmt_insert_refresh_token->execute();

                                            //add log for the signup
                                            global $logs_table;
                                            addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "verify-login", getIpAddress());

                                            $email_sent = false;
                                            $email_sent_max_attempts = 3;
                                            $email_sent_number = 0;
                                            do {
                                                $email_sent = sendEmailLoggedin($username, $email_decrypted, getIpAddress());
                                                $email_sent_number++;
                                            } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);

                                            if ($email_sent === false) {
                                                $data = array("code" => 555, "user-id" => $user_id);
                                                responseError(555, "Could not send verification email", $data);
                                            } else {
                                                $data = array(
                                                    "login-id" => $new_login_id, //to be used to authenticate requests
                                                    "refresh-token-id" => $refresh_token_id //to be used to get new login tokens
                                                );
                                                responseSuccess(200, "Login verified successfully.", $data);
                                            }
                                        } catch (mysqli_sql_exception $e) {
                                            responseError(500, "Database error: " . $e->getMessage());
                                        }
                                        $stmt_insert_refresh_token->close();
                                    } catch (mysqli_sql_exception $e) {
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_insert_login->close();
                                } else {
                                    responseError(500, "Could not decrypt email.");
                                }
                            } else {
                                responseError(500, "User not found.");
                            }
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_get_user->close();
                    } else {
                        responseError(400, "Unknown action.");
                    }
                } else {
                    //unauthorized: wrong code
                    responseError(401, "Wrong code");
                }
            } else {
                //unauthorized: login-id not found or expired
                responseError(401, "Invalid or expired login-id");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }

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
    if (!isset($post["code"]) || !checkFieldValidity($post["code"])) {
        array_push($missing_parameters, "code");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>