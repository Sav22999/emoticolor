<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
require __DIR__ . '/vendor/autoload.php';

/**
 * Generate a UUID v4
 * @param bool $withHyphens Whether to include hyphens in the UUID
 * @return string Generated UUID v4
 */
function generateUUIDv4(bool $withHyphens = true): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10
    if ($withHyphens) {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    } else {
        return vsprintf("%s%s%s%s%s%s%s%s", str_split(bin2hex($data), 4));
    }
}

/**
 * Check if a username is valid (min 5 chars, max 20 chars, only letters (lowercase) and numbers)
 * @param $username string Username to check
 * @return null|string True if the username is valid, false otherwise
 */
function getUsernameValidated(string $username)
{
    $username_to_use = trim(strtolower($username));
    if (preg_match('/^[a-z0-9]{5,20}$/', $username_to_use)) {
        return $username_to_use;
    } else {
        return null;
    }
}

/**
 * Check if a username is valid (min 5 chars, max 20 chars, only letters (lowercase) and numbers)
 * @param $username string Username to check
 * @return bool True if the username is valid, false otherwise
 */
function checkUsernameValidity(string $username)
{
    return getUsernameValidated($username) !== null;
}

/**
 * Generate HMAC-SHA256 hash of an email with a secret key
 * @param $email string User email
 * @return string HMAC-SHA256 hashed email with secret key
 */
function emailHash(string $email)
{
    global $emailSecretKey;
    return hash_hmac('sha256', strtolower(trim($email)), $emailSecretKey);
}

/**
 * Generate Argon2id hash of a password
 * @param $password string Password in plain text
 * @return string Hashed password using Argon2id
 */
function passwordHash(string $password)
{
    return argon2idHash($password);
}

function argon2idHash(string $text)
{
    return password_hash($text, PASSWORD_ARGON2ID);
}

/**
 * Verify a password against its Argon2id hash
 * @param $password string Password in plain text
 * @param $hash string Hashed password
 * @return bool True if the password matches the hash, false otherwise
 */
function checkPasswordHash(string $password, string $hash)
{
    return password_verify($password, $hash);
}

/**
 * Generate MD5 hash of a text
 * @param $text string Input text
 * @return string MD5 hashed text
 */
function md5Hash(string $text)
{
    return md5($text);
}

/*function encryptHash($text)
{
    return hash("sha512", $text);
}

function deriveKeyFromPassword($password, $salt, $keyLength = 32, $iterations = 10000, $algorithm = 'sha256')
{
    return hash_pbkdf2($algorithm, $password, $salt, $iterations, $keyLength, true);
}

function encryptTextWithPassword($text, $password)
{
    $salt = openssl_random_pseudo_bytes(16); // Generate a random salt
    $key = deriveKeyFromPassword($password, $salt);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedText = openssl_encrypt($text, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($salt . $iv . $encryptedText);
}

function decryptTextWithPassword($encryptedText, $password)
{
    $decoded = base64_decode($encryptedText);
    $salt = substr($decoded, 0, 16);
    $iv = substr($decoded, 16, openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedText = substr($decoded, 16 + openssl_cipher_iv_length('aes-256-cbc'));
    $key = deriveKeyFromPassword($password, $salt);
    return openssl_decrypt($encryptedText, 'aes-256-cbc', $key, 0, $iv);
}*/

/**
 * Get the client's IP address
 * @return string Client's IP address
 */
function getIpAddress()
{
    $ip_address = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR']);
    $ip_address = $ip_address ?: "Unknown";
    return $ip_address;
}

/**
 * Get the current timestamp in 'Y-m-d H:i:s' format
 * @return string Current timestamp
 */
function getTimestamp()
{
    return date('Y-m-d H:i:s');
}

/**
 * Get the timestamp plus a specified number of minutes in 'Y-m-d H:i:s' format
 * @param $minutes int Number of minutes to add
 * @return string Timestamp plus specified minutes
 */
function getTimestampPlusMinutes(int $minutes)
{
    return date('Y-m-d H:i:s', strtotime('+' . $minutes . ' minutes'));
}

/**
 * Generate a random otp code in the format XXX-0000-XXX (letters-numbers-letters)
 * @return string Generated otp code
 */
function generateOtpCode()
{
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';

    $part1 = '';
    for ($i = 0; $i < 3; $i++) {
        $part1 .= $letters[rand(0, strlen($letters) - 1)];
    }

    $part2 = '';
    for ($i = 0; $i < 4; $i++) {
        $part2 .= $numbers[rand(0, strlen($numbers) - 1)];
    }

    $part3 = '';
    for ($i = 0; $i < 3; $i++) {
        $part3 .= $letters[rand(0, strlen($letters) - 1)];
    }

    return $part1 . '-' . $part2 . '-' . $part3;
}

/**
 * Correct a date string to 'Y-m-d H:i:s' format
 * @param $date string Input date string
 * @return string Corrected date timestamp
 */
function getCorrectedDateTimestamp(string $date)
{
    return date('Y-m-d H:i:s', strtotime($date));
}

/*function getRandomString($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $max)];
    }
    return $randomString;
}*/

/**
 * Send signup verification email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $code string Verification code
 * @param $ip_address string IP address of the user
 * @param $new_code bool Whether it's a new code request
 * @return bool True if the email was sent successfully, false otherwise
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmailSignup($username, $to_email, $code, $ip_address, $new_code = false): bool
{
    $message_code = $new_code ? "You required another verification code." : "Thank you for signing up to Notefox.";
    $message_title = $new_code ? "New code to verify your email" : "Verify your email";

    $section_1 = $message_title;
    $section_2 = $message_code . "To confirm your login, please use the following code:";
    $section_3 = "If you didn't log in to Notefox, you should definitely change your password.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "", $message);
    $message = str_replace("{{code}}", $code, $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $mail = new PHPMailer(true);

    global $email_address, $email_password, $email_smtp;
    try {
        // Configurazione Server
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Destinatari
        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        // Contenuto (Tua logica dei template)
        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: verify your email";
        $mail->Body = $message; // La variabile $message generata dai tuoi str_replace

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

function sendEmailSignedup($username, $to_email, $ip_address)
{
    //send email from no-reply@emoticolor.org to the email with the verification code (unencrypted)

    $section_1 = "Account created";
    $section_2 = "You just created a Emoticolor account with this email.";
    $section_3 = "If you didn't sign up to Emoticolor, please ignore this email.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "hidden-small", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $to = $to_email;
    $subject = "Emoticolor: account created";

    $headers = "From: no-reply@emoticolor.org\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    mail($to, $subject, $message, $headers);
}

function sendEmailLogin($username, $to_email, $code, $ip_address, $verification_expiry, $new_code = false)
{
//send email from no-reply@emoticolor.org to the email with the verification code (unencrypted)

    $message_code = $new_code ? "You required another otp to verify the login process.<br>" : "";
    $message_title = $new_code ? "New code to log in" : "Confirm your log in";

    $section_1 = $message_title;
    $section_2 = $message_code . "To confirm your login, please use the following code:";
    $section_3 = "The code will be valid for 1 hour (until " . $verification_expiry . ").<br>If you didn't log in to Emoticolor, you should definitely change your password.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "", $message);
    $message = str_replace("{{code}}", $code, $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $to = $to_email;
    $subject = "Emoticolor: confirm your login";

    $headers = "From: no-reply@emoticolor.org\r\n";
    $headers .= "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    mail($to, $subject, $message, $headers);
}

function sendEmailLoggedin($username, $to_email, $ip_address)
{
    //send email from no-reply@emoticolor.org to the email with the verification code (unencrypted)

    $section_1 = "Just logged in";
    $section_2 = "You just logged in to your Emoticolor account.";
    $section_3 = "If you haven't logged in to Emoticolor, please change your password immediately.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "hidden-small", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $to = $to_email;
    $subject = "Emoticolor: just logged in";

    $headers = "From: no-reply@emoticolor.org\r\n";
    $headers .= "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    mail($to, $subject, $message, $headers);
}

function sendEmailDeleting($username, $to_email, $code, $ip_address, $expiry, $new_code = false)
{
    //send email from no-reply@emoticolor.org to the email with the verification code (unencrypted)

    $message_code = $new_code ? "You required another otp to confirm the deleting of your Emoticolor account.<br>" : "";
    $message_title = $new_code ? "New code to delete account" : "Confirm deleting account";

    $section_1 = $message_title;
    $section_2 = $message_code . "To confirm you want to delete permanently your account, please use the following deleting code:";
    $section_3 = "The code will be valid for 1 hour (until " . $expiry . ").<br>If you didn't ask for deleting your Emoticolor account, please change your password immediately.<br>Once deleted the account, all data will be definitely deleted from database and you'll lose data forever.<br><br>If you asked for deleting your account, but you changed your mind, please ignore this email.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "", $message);
    $message = str_replace("{{code}}", $code, $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $to = $to_email;
    $subject = "Emoticolor: confirm deleting account";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@emoticolor.org\r\n";

    mail($to, $subject, $message, $headers);
}

function sendEmailDeleted($username, $to_email)
{
    //send email from

    $section_1 = "Account permanently deleted";
    $section_2 = "Your Emoticolor account is now deleted permanently, together to all your data.<br>I'm really sorry about your decision to leave Emoticolor.";
    $section_3 = "If you would like creating a new one, you can also reuse this email address.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "hidden-small", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "hidden", $message);
    $message = str_replace("{{ip-address}}", "", $message);

    $to = $to_email;
    $subject = "Emoticolor: account deleted";

    $headers = "From: no-reply@emoticolor.org\r\n";
    $headers .= "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    mail($to, $subject, $message, $headers);
}

function getEmailTemplate()
{
    $path = $_SERVER['DOCUMENT_ROOT'] . "";
    return file_get_contents($path . "/api/emoticolor/v1/email-template.php");
}

// http_response_code(200); // successful response
// http_response_code(204); // no content (successful but no content to return)
// http_response_code(400); // bad request
// http_response_code(401); // unauthorized (not provided credentials)
// http_response_code(403); // forbidden (provided credentials are not valid)
// http_response_code(440); // login time-out (need to re-login)
// http_response_code(500); // internal server error

/**
 * Send a JSON error response
 * @param $code int HTTP status code
 * @param $message string|null Error message
 * @param $data mixed|null Additional data (optional)
 * @return void
 */
function responseError(int $code, ?string $message = null, mixed $data = null)
{
    http_response_code($code);
    if ($message === null || $message === "") {
        $message = "An error occurred.";
    }
    $response = array("message" => $message);
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response);
}

/**
 * Send a JSON success response
 * @param $code int HTTP status code
 * @param $message string|null Success message
 * @param $data mixed|null Additional data (optional)
 * @return void
 */
function responseSuccess(int $code, ?string $message = null, mixed $data = null)
{
    http_response_code($code);
    if ($message === null || $message === "") {
        $message = "Success.";
    }
    $response = array("message" => $message);
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response);
}

?>