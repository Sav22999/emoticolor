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

$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["code"]) && checkFieldValidity($post["code"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $refresh_tokens_table;
        // additional tables used for deletion
        global $posts_table, $reactions_posts_table, $notifications_table, $notifications_read_table, $users_followed_table, $emotions_followed_table, $learning_statistics_table;
        global $emailSecretKeyAES;

        $login_id = $post["login-id"];
        $code = strtoupper(trim($post["code"]));
        $user_id = null;

        $action = null;

        $query_get_otp_code = "SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND ((`logins`.`once-time` = 1 AND `otps`.`used` IS NOT NULL AND `otps`.`valid-until` >= CURRENT_TIMESTAMP OR `otps`.`used` IS NULL) OR (`logins`.`once-time` = 0)) AND `logins`.`login-id` = ?";
        $stmt_get_otp_code = $c->prepare($query_get_otp_code);
        $stmt_get_otp_code->bind_param("s", $login_id);

        try {
            $stmt_get_otp_code->execute();
            $result = $stmt_get_otp_code->get_result();

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

                                        //add log
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
                        // Create a new login for the user, valid for 1 day, to allow password change
                        $new_login_id = generateUUIDv4();
                        $query_insert_login = "INSERT INTO $logins_table (`login-id`, `user-id`, `otp-id`, `once-time`, `created`, `valid-until`) VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY))";
                        $stmt_insert_login = $c->prepare($query_insert_login);
                        $stmt_insert_login->bind_param("sss", $new_login_id, $user_id, $otp_id_used);
                        try {
                            $stmt_insert_login->execute();

                            // Return the new login-id to the client so it can call change-password set endpoint
                            responseSuccess(200, "Reset code verified.", array("login-id" => $new_login_id));
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_insert_login->close();
                    } else if ($action == "delete-account") {
                        // fetch email and username before deleting so we can send confirmation after
                        $email_decrypted = null;
                        $username = null;
                        $stmt_email = $c->prepare("SELECT `email_aes`, `username` FROM $users_table WHERE `user-id` = ?");
                        $stmt_email->bind_param("s", $user_id);
                        try {
                            $stmt_email->execute();
                            $res_email = $stmt_email->get_result();
                            if ($res_email->num_rows === 1) {
                                $row_email = $res_email->fetch_assoc();
                                $email_decrypted = decryptTextWithPassword($row_email["email_aes"], $emailSecretKeyAES);
                                $username = $row_email["username"];
                            }
                        } catch (mysqli_sql_exception $e) {
                            // ignore error retrieving email; proceed with deletion
                        }
                        $stmt_email->close();

                        // perform full user deletion in a transaction
                        try {
                            $c->begin_transaction();

                            // remove reactions by the user
                            $stmt = $c->prepare("DELETE FROM $reactions_posts_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove reactions on posts authored by user
                            $stmt = $c->prepare("DELETE rp FROM $reactions_posts_table rp INNER JOIN $posts_table p ON rp.`post-id` = p.`post-id` WHERE p.`user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove notifications_read entries for this user
                            $stmt = $c->prepare("DELETE FROM $notifications_read_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove notifications related to posts created by this user
                            $stmt = $c->prepare("DELETE n FROM $notifications_table n INNER JOIN $posts_table p ON n.`post-id` = p.`post-id` WHERE p.`user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove posts authored by user
                            $stmt = $c->prepare("DELETE FROM $posts_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove learning statistics
                            $stmt = $c->prepare("DELETE FROM $learning_statistics_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove follows where user is follower or followed
                            $stmt = $c->prepare("DELETE FROM $users_followed_table WHERE `user-id` = ? OR `followed-user-id` = ?");
                            $stmt->bind_param("ss", $user_id, $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove emotions followed by user
                            $stmt = $c->prepare("DELETE FROM $emotions_followed_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove refresh tokens
                            $stmt = $c->prepare("DELETE FROM $refresh_tokens_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // remove logins
                            $stmt = $c->prepare("DELETE FROM $logins_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // finally remove user row
                            $stmt = $c->prepare("DELETE FROM $users_table WHERE `user-id` = ?");
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $stmt->close();

                            // commit
                            $c->commit();

                            // add log
                            global $logs_table;
                            addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "delete-account", getIpAddress());

                            // send confirmation email (if possible) using previously fetched values
                            if ($email_decrypted !== null && $email_decrypted !== false && $username !== null) {
                                $email_sent = false;
                                $email_sent_max_attempts = 3;
                                $email_sent_number = 0;
                                do {
                                    $email_sent = sendEmailOperationNotification($username, $email_decrypted, 'delete-account', getIpAddress(), true, null, null);
                                    $email_sent_number++;
                                } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);
                            }

                            responseSuccess(200, "Account deleted permanently.");
                        } catch (mysqli_sql_exception $e) {
                            $c->rollback();
                            responseError(500, "Database error while deleting account: " . $e->getMessage());
                        }

                    } else if ($action == "verify-login") {
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
                                    //insert a new login for the user, valid for 1 day in logins_table
                                    $new_login_id = generateUUIDv4();
                                    $query_insert_login = "INSERT INTO $logins_table (`login-id`, `user-id`, `otp-id`, `once-time`, `created`, `valid-until`) VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 DAY))";
                                    $stmt_insert_login = $c->prepare($query_insert_login);
                                    $stmt_insert_login->bind_param("sss", $new_login_id, $user_id, $otp_id_used);
                                    try {
                                        $stmt_insert_login->execute();

                                        //insert a new refresh token for the user, valid for 30 days in refresh_tokens_table
                                        $refresh_token_id = generateUUIDv4();
                                        $query_insert_refresh_token = "INSERT INTO $refresh_tokens_table (`token-id`, `user-id`, `created`, `valid-until`) VALUES (?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY))";
                                        $stmt_insert_refresh_token = $c->prepare($query_insert_refresh_token);
                                        $stmt_insert_refresh_token->bind_param("ss", $refresh_token_id, $user_id);
                                        try {
                                            $stmt_insert_refresh_token->execute();

                                            //add log
                                            global $logs_table;
                                            addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "verify-login", getIpAddress());

                                            $email_sent = false;
                                            $email_sent_max_attempts = 3;
                                            $email_sent_number = 0;
                                            do {
                                                $email_sent = sendEmailOperationNotification($username, $email_decrypted, 'verify-login', getIpAddress(), true, null, null);
                                                $email_sent_number++;
                                            } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);

                                            if ($email_sent === false) {
                                                $data = array("code" => 555, "user-id" => $user_id);
                                                responseError(555, "Could not send verification email", $data);
                                            } else {
                                                $data = array(
                                                    "login-id" => $new_login_id, //to be used to authenticate requests
                                                    "token-id" => $refresh_token_id //to be used to get new login tokens
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
        $stmt_get_otp_code->close();

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