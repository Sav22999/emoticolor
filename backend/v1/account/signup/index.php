<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
global $localhost_db, $username_db, $password_db, $name_db;
//header("Content-Type:application/json");
$post = json_decode(file_get_contents('php://input'), true); //POST request
//$post = $_POST; //POST request fallback
$get = $_GET; //GET request
$post = $get;//TODO: to be removed, only for testing with GET requests

$condition = isset($post["email"]) && isset($post["password"]) && isset($post["username"]) && $post["username"] != "" && $post["username"] != null && checkUsernameValidity($post["username"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;

        $email_clean = strtolower(trim($post["email"]));
        $language_code = "it";
        if (isset($post["language"])) {
            $language_code = $post["language"];
        }
        $bio = null;
        if (isset($post["bio"]) && $post["bio"] != "") {
            $bio = $post["bio"];
            //max 500 characters
            if (strlen($bio) > 500) $bio = substr($bio, 0, 500);
        }
        $gravatar = md5Hash($email_clean);
        $email = emailHash($email_clean);
        $password = passwordHash($post["password"]);
        $username = getUsernameValidated($post["username"]);
        $user_id = generateUUIDv4();

        $login_id = generateUUIDv4();

        $stmt_check_user = $c->prepare("SELECT * FROM $users_table WHERE `username` = ? OR `email` = ?");
        $stmt_check_user->bind_param("ss", $username, $email);

        try {
            $stmt_check_user->execute();
            $result = $stmt_check_user->get_result();
            $stmt_check_user->close();

            if ($result->num_rows === 0) {
                $query_insert_user = "INSERT INTO $users_table (`user-id`, `email`, `password`, `status`, `username`, `language`, `profile-image`, `bio`, `created`) VALUES (?, ?, ?, 0, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                $stmt_insert_user = $c->prepare($query_insert_user);
                $c->query("LOCK TABLES $users_table WRITE");
                $stmt_insert_user->bind_param("sssssss", $user_id, $email, $password, $username, $language_code, $gravatar, $bio);
                $c->query("UNLOCK TABLES");
                try {
                    $stmt_insert_user->execute();

                    $otp_generated = false;
                    $otp_generated_max_attempts = 5;
                    $otp_generated_number = 0;
                    $otp_id_used = null;
                    $otp_code_used = null;
                    do {
                        $query_insert_otp = "INSERT INTO $otps_table (`otp-id`, `action`, `code`, `created`, `valid-until`, `used`) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, NULL)";
                        $otp_id = generateUUIDv4();
                        $otp_code_used = generateOtpCode();
                        $otp_code = argon2idHash($otp_code_used);
                        $action = "check-signup";
                        $valid_until = getTimestampPlusMinutes("60");
                        $stmt_insert_otp = $c->prepare($query_insert_otp);
                        $c->query("LOCK TABLES $otps_table WRITE");
                        $stmt_insert_otp->bind_param("ssss", $otp_id, $action, $otp_code, $valid_until);
                        $c->query("UNLOCK TABLES");
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
                        $c->query("LOCK TABLES $users_table WRITE");
                        $stmt_insert_login->bind_param("sss", $login_id, $user_id, $otp_id);
                        $c->query("UNLOCK TABLES");
                        try {
                            $stmt_insert_login->execute();

                            $email_sent = false;
                            $email_sent_max_attempts = 3;
                            $email_sent_number = 0;
                            do {
                                $email_sent = sendEmailSigningup($username, $email_clean, $otp_code_used, getIpAddress(), false);
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
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_insert_user->close();
            } else {
                //conflict: username or email already in use
                responseError(409, "Username or email already in use");
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
    if (!isset($post["email"]) || $post["email"] == "" || $post["email"] == null) {
        array_push($missing_parameters, "email");
    }
    if (!isset($post["password"]) || $post["password"] == "" || $post["password"] == null) {
        array_push($missing_parameters, "password");
    }
    if (!isset($post["username"]) || $post["username"] == "" || $post["username"] == null) {
        array_push($missing_parameters, "username");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>