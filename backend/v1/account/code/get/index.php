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

        global $logins_table, $users_table, $otps_table;
        global $emailSecretKeyAES;

        $login_id = $post["login-id"];
        $user_id = null;

        $action = null;

        $query_get_otp_code = "SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND ((`logins`.`once-time` = 1 AND `otps`.`used` IS NOT NULL AND `otps`.`valid-until` >= CURRENT_TIMESTAMP OR `otps`.`used` IS NULL) OR (`logins`.`once-time` = 0)) AND `logins`.`login-id` = ?";
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

                $new_code = generateOtpCode();
                $new_code_hashed = argon2idHash($new_code);

                //update OTP code and valid-until with new values
                $query_update_otp_used = "UPDATE $otps_table SET `code` = ?, `valid-until` = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR) WHERE `otp-id` = ?";
                $stmt_update_otp_used = $c->prepare($query_update_otp_used);
                $stmt_update_otp_used->bind_param("ss", $new_code_hashed, $otp_id_used);
                try {
                    $stmt_update_otp_used->execute();

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
                                //add log
                                global $logs_table;
                                if ($action == "verify-account") {
                                    addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "resend-verify-signup", getIpAddress());
                                } else if ($action == "reset-password") {
                                    addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "resend-reset-password", getIpAddress());
                                } else if ($action == "verify-login") {
                                    addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "resend-verify-login", getIpAddress());
                                }

                                $email_sent = false;
                                $email_sent_max_attempts = 3;
                                $email_sent_number = 0;
                                do {
                                    //check action: it can be "verify-account", "reset-password", "verify-login", etc.
                                    if ($action == "verify-account") {
                                        $email_sent = sendEmailSigningup($username, $email_decrypted, $new_code, getIpAddress(), null, true);
                                    } else if ($action == "reset-password") {
                                        //todo
                                    } else if ($action == "verify-login") {
                                        //todo
                                        $email_sent = sendEmailLoggingin($username, $email_decrypted, $new_code, getIpAddress(), null, true);
                                    } else {
                                        responseError(400, "Unknown action.");
                                    }
                                    $email_sent_number++;
                                } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);

                                if ($email_sent === false) {
                                    $data = array("code" => 555, "user-id" => $user_id);
                                    responseError(555, "Could not send verification email", $data);
                                } else {
                                    responseSuccess(200, "New code sent successfully.");
                                }
                            }
                        } else {
                            responseError(500, "User not found.");
                        }
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_get_user->close();
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_update_otp_used->close();
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
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>