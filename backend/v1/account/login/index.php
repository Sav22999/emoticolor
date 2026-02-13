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


$condition = isset($post["email"]) && checkEmailValidity($post["email"]) && isset($post["password"]) && checkPasswordValidity($post["password"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;

        $email_clean = strtolower(trim($post["email"]));
        $email_hashed = emailHash($email_clean);
        $password_passed = $post["password"];

        $query_get_user = "SELECT `user-id`, `password`, `email_aes`, `username` FROM $users_table WHERE `email` = ? AND `status` = 1";
        $stmt_get_user = $c->prepare($query_get_user);
        $stmt_get_user->bind_param("s", $email_hashed);

        try {
            $stmt_get_user->execute();
            $result = $stmt_get_user->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];
                $password = $row["password"];
                $username = $row["username"];

                if (checkPasswordHash($password_passed, $password)) {
                    $login_id = generateUUIDv4();

                    $otp_generated = false;
                    $otp_generated_max_attempts = 5;
                    $otp_generated_number = 0;
                    $otp_id_used = null;
                    $otp_code_used = null;
                    do {
                        //valid for 1 hour
                        $query_insert_otp = "INSERT INTO $otps_table (`otp-id`, `action`, `code`, `created`, `valid-until`, `used`) VALUES (?, ?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR), NULL)";
                        $otp_id = generateUUIDv4();
                        $otp_code_used = generateOtpCode();
                        $otp_code = argon2idHash($otp_code_used);
                        $action = "verify-login";
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
                        $query_insert_login = "INSERT INTO $logins_table (`login-id`, `user-id`, `otp-id`, `once-time`, `created`, `valid-until`) VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, NULL)";
                        $stmt_insert_login = $c->prepare($query_insert_login);
                        $stmt_insert_login->bind_param("sss", $login_id, $user_id, $otp_id);
                        try {
                            //add log
                            global $logs_table;
                            addLog($localhost_db, $username_db, $password_db, $name_db, $logs_table, $user_id, "login", getIpAddress());

                            $stmt_insert_login->execute();

                            $email_sent = false;
                            $email_sent_max_attempts = 3;
                            $email_sent_number = 0;
                            do {
                                $email_sent = sendEmailLoggingin($username, $email_clean, $otp_code_used, getIpAddress(), null, false);
                                $email_sent_number++;
                            } while ($email_sent === false && $email_sent_number < $email_sent_max_attempts);

                            if ($email_sent === false) {
                                $data = array("code" => 555, "user-id" => $user_id);
                                responseError(555, "Could not send verification email", $data);
                            } else {
                                responseSuccess(200, null, array("login-id" => $login_id));
                            }
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_insert_login->close();
                    }
                } else {
                    //unauthorized: wrong password
                    responseError(401, "Unauthorized: wrong email or password");
                }
            } else {
                //unauthorized: wrong email
                responseError(401, "Unauthorized: wrong email or password");
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
    if (!isset($post["email"]) || !checkEmailValidity($post["email"])) {
        array_push($missing_parameters, "email");
    }
    if (!isset($post["password"]) || !checkPasswordValidity($post["password"])) {
        array_push($missing_parameters, "password");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>