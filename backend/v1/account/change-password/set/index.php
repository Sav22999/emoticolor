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


// Expect login-id and new password
$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["password"]) && checkFieldValidity($post["password"]) && checkPasswordValidity($post["password"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $emailSecretKeyAES;

        $login_id = $post["login-id"];
        $new_password_plain = $post["password"];

        // retrieve user and otp-id associated with this login-id
        // NOTE: do not assume an `action` column exists in otps with the same name in all environments; we'll fetch it separately
        // direct join with Logins table to avoid ambiguous subqueries on different schemas
        $query_get_user = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status`, `users`.`email_aes` AS `email_aes`, `users`.`username` AS `username`, `logins`.`otp-id` AS `otp-id` FROM $users_table AS `users` INNER JOIN $logins_table AS `logins` ON `users`.`user-id` = `logins`.`user-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND `logins`.`login-id` = ? AND `users`.`status` = 1";
        $stmt_get_user = $c->prepare($query_get_user);
        $stmt_get_user->bind_param("s", $login_id);

        try {
            $stmt_get_user->execute();
            $result = $stmt_get_user->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];
                $status = $row["status"];
                $email_aes = $row["email_aes"];
                $username = $row["username"];
                $otp_id = $row["otp-id"];

                // fetch action from otps table using otp-id (safer and avoids schema assumptions)
                $action = null;
                if ($otp_id !== null && $otp_id !== "") {
                    $stmt_get_action = $c->prepare("SELECT `action` FROM $otps_table WHERE `otp-id` = ? LIMIT 1");
                    if ($stmt_get_action) {
                        $stmt_get_action->bind_param("s", $otp_id);
                        try {
                            $stmt_get_action->execute();
                            $res_action = $stmt_get_action->get_result();
                            if ($res_action && $res_action->num_rows === 1) {
                                $row_action = $res_action->fetch_assoc();
                                if (isset($row_action['action'])) $action = $row_action['action'];
                            }
                        } catch (mysqli_sql_exception $e) {
                            // proceed with null action which will be rejected below
                        }
                        $stmt_get_action->close();
                    }
                }

                // Only allow if action is reset-password or change-password
                if ($action !== "reset-password" && $action !== "change-password") {
                    responseError(400, "Invalid action for this login-id");
                }

                // hash new password and update user
                $new_password_hash = passwordHash($new_password_plain);

                $query_update_password = "UPDATE $users_table SET `password` = ? WHERE `user-id` = ?";
                $stmt_update_password = $c->prepare($query_update_password);
                $stmt_update_password->bind_param("ss", $new_password_hash, $user_id);
                try {
                    $stmt_update_password->execute();

                    // invalidate all logins for this user (optional: keep current one?) - safer to delete existing logins
                    $query_delete_logins = "DELETE FROM $logins_table WHERE `user-id` = ?";
                    $stmt_delete_logins = $c->prepare($query_delete_logins);
                    $stmt_delete_logins->bind_param("s", $user_id);
                    try {
                        $stmt_delete_logins->execute();
                    } catch (mysqli_sql_exception $e) {
                        // non-fatal, continue
                    }
                    $stmt_delete_logins->close();

                    // mark otp as used
                    $query_update_otp_used = "UPDATE $otps_table SET `used` = CURRENT_TIMESTAMP WHERE `otp-id` = ?";
                    $stmt_update_otp_used = $c->prepare($query_update_otp_used);
                    $stmt_update_otp_used->bind_param("s", $otp_id);
                    try {
                        $stmt_update_otp_used->execute();
                    } catch (mysqli_sql_exception $e) {
                        // non-fatal
                    }
                    $stmt_update_otp_used->close();

                    // add log
                    global $logs_table;
                    addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "change-password", getIpAddress());

                    // send confirmation email
                    $email_decrypted = decryptTextWithPassword($email_aes, $emailSecretKeyAES);
                    if ($email_decrypted !== false) {
                        // use generic operation notification for consistency
                        $email_sent = false;
                        $email_sent_max_attempts = 3;
                        $email_sent_number = 0;
                        do {
                            $email_sent = sendEmailOperationNotification($username, $email_decrypted, 'change-password', getIpAddress(), true, null, null);
                            $email_sent_number++;
                        } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);
                    }

                    responseSuccess(200, "Password changed successfully.");
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_update_password->close();
            } else {
                responseError(440, "Unauthorized: invalid or expired login-id");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_user->close();

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
    // If a password is present but doesn't satisfy the requirements, return a clear message
    if (isset($post["password"]) && checkFieldValidity($post["password"]) && !checkPasswordValidity($post["password"])) {
        $rules = "Password must be at least 10 characters, contain at least one uppercase letter, one lowercase letter and one number.";
        responseError(400, "Invalid password: " . $rules);
    }

    if (!isset($post["password"]) || !checkFieldValidity($post["password"])) {
        array_push($missing_parameters, "password");
    }

    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>