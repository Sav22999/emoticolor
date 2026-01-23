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

// Request password reset: client provides email, server sends OTP to that email
$condition = isset($post["email"]) && checkFieldValidity($post["email"]) && checkEmailValidity($post["email"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $emailSecretKeyAES;

        $email_clean = strtolower(trim($post["email"]));
        $email_hashed = emailHash($email_clean);

        // find user by hashed email and active status
        $query_get_user = "SELECT `user-id`, `email_aes`, `username` FROM $users_table WHERE `email` = ? AND `status` = 1";
        $stmt_get_user = $c->prepare($query_get_user);
        $stmt_get_user->bind_param("s", $email_hashed);

        try {
            $stmt_get_user->execute();
            $result = $stmt_get_user->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];
                $username = $row["username"];
                $email_decrypted = decryptTextWithPassword($row["email_aes"], $emailSecretKeyAES);

                if ($email_decrypted === false) {
                    responseError(500, "Could not decrypt email.");
                }

                // generate OTP with action reset-password, valid for 1 hour
                $otp_generated = false;
                $otp_generated_max_attempts = 5;
                $otp_generated_number = 0;
                $otp_id_used = null;
                $otp_code_used = null;
                do {
                    $query_insert_otp = "INSERT INTO $otps_table (`otp-id`, `action`, `code`, `created`, `valid-until`, `used`) VALUES (?, ?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR), NULL)";
                    $otp_id = generateUUIDv4();
                    $otp_code_used = generateOtpCode();
                    $otp_code = argon2idHash($otp_code_used);
                    $action = "reset-password";
                    $stmt_insert_otp = $c->prepare($query_insert_otp);
                    $stmt_insert_otp->bind_param("sss", $otp_id, $action, $otp_code);
                    try {
                        $stmt_insert_otp->execute();
                        $otp_generated = true;
                        $otp_id_used = $otp_id;
                    } catch (mysqli_sql_exception $e) {
                        $otp_generated = false;
                    }
                    $stmt_insert_otp->close();
                    $otp_generated_number++;
                } while ($otp_generated === false && $otp_generated_number < $otp_generated_max_attempts);

                if ($otp_generated === false) {
                    responseError(409, "Could not generate OTP code");
                } else {
                    // insert login entry (once-time = 1) linked to otp
                    $login_id = generateUUIDv4();
                    $query_insert_login = "INSERT INTO $logins_table (`login-id`, `user-id`, `otp-id`, `once-time`, `created`, `valid-until`) VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, NULL)";
                    $stmt_insert_login = $c->prepare($query_insert_login);
                    $stmt_insert_login->bind_param("sss", $login_id, $user_id, $otp_id_used);
                    try {
                        // add log
                        global $logs_table;
                        addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "reset-password-request", getIpAddress());

                        $stmt_insert_login->execute();

                        $email_sent = false;
                        $email_sent_max_attempts = 3;
                        $email_sent_number = 0;
                        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));
                        do {
                            $email_sent = sendEmailPasswordReset($username, $email_decrypted, $otp_code_used, getIpAddress(), $expiry);
                            $email_sent_number++;
                        } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);

                        if ($email_sent === false) {
                            $data = array("code" => 555, "user-id" => $user_id);
                            responseError(555, "Could not send password reset email", $data);
                        } else {
                            responseSuccess(200, null, array("login-id" => $login_id));
                        }
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_insert_login->close();
                }
            } else {
                // do not reveal whether email exists: respond with 200 to avoid user enumeration
                responseSuccess(200, "If the email exists, you will receive an otp to reset your password.");
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
    if (!isset($post["email"]) || !checkFieldValidity($post["email"]) || !checkEmailValidity($post["email"])) {
        array_push($missing_parameters, "email");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>