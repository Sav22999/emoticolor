<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

/**
 * Send signup verification email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $code string Verification code
 * @param $ip_address string IP address of the user
 * @param $new_code bool Whether it's a new code request
 * @return bool True if the email was sent successfully, false otherwise
 */
function sendEmailSigningup(string $username, string $to_email, string $code, string $ip_address, bool $new_code = false): bool
{
    $message_code = $new_code ? "You required another verification code." : "Thank you for signing up to Emoticolor.";
    $message_title = $new_code ? "New code to verify your email" : "Verify your email";

    $section_1 = $message_title;
    $section_2 = $message_code . "To confirm your login, please use the following code:";
    $section_3 = "If you aren't signing up to Emoticolor, please ignore this email.";

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

    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: verify your email";
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Send account created email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $ip_address string IP address of the user
 * @return bool
 */
function sendEmailSignedup(string $username, string $to_email, string $ip_address): bool
{
    $section_1 = "Account created";
    $section_2 = "You just created a Emoticolor account with this email.";
    $section_3 = "If you didn't sign up to Emoticolor, please contact the Emoticolor support.";
    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "hidden-small", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $mail = new PHPMailer(true);

    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: account created";
        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Send login verification email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $code string Verification code
 * @param $ip_address string IP address of the user
 * @param $verification_expiry string Expiry time of the verification code
 * @param $new_code bool Whether it's a new code request
 * @return bool
 */
function sendEmailLoggingin(string $username, string $to_email, string $code, string $ip_address, string $verification_expiry, bool $new_code = false): bool
{
    $message_code = $new_code ? "You required another OTP to verify the login process.<br>" : "";
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

    $mail = new PHPMailer(true);

    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: confirm your login";
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Send email notification for successful login
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $ip_address string IP address of the user
 * @return bool
 */
function sendEmailLoggedin(string $username, string $to_email, string $ip_address): bool
{
    $section_1 = "Just logged in";
    $section_2 = "You just logged in to your Emoticolor account.";
    $section_3 = "If you haven't logged in to Emoticolor, please change your password immediately.\nYour login session will remain active unless you manually log out for 30 days.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "hidden-small", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $mail = new PHPMailer(true);

    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: just logged in";
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Send account deletion confirmation email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $code string Deletion confirmation code
 * @param $ip_address string IP address of the user
 * @param $expiry string Expiry time of the deletion code
 * @param $new_code bool Whether it's a new code request
 * @return bool
 */
function sendEmailDeleting(string $username, string $to_email, string $code, string $ip_address, string $expiry, bool $new_code = false): bool
{
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

    $mail = new PHPMailer(true);

    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: confirm deleting account";
        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}


/**
 * Send account deleted confirmation email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @return bool
 */
function sendEmailDeleted(string $username, string $to_email): bool
{
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

    $mail = new PHPMailer(true);

    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: account deleted";
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Send account password reset email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $code string Password reset code
 * @param $ip_address string IP address of the user
 * @param $expiry string Expiry time of the reset code
 * @return bool
 */
function sendEmailPasswordReset(string $username, string $to_email, string $code, string $ip_address, string $expiry): bool
{
    $section_1 = "Password reset request";
    $section_2 = "You requested to reset your Emoticolor account password.<br>To reset your password, please use the following code:";
    $section_3 = "The code will be valid for 1 hour (until " . $expiry . ").<br>If you didn't ask for resetting your password, please change your password immediately.";
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

    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: password reset request";
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Send account password changed confirmation email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $ip_address string IP address of the user
 * @return bool
 */
function sendEmailPasswordChanged(string $username, string $to_email, string $ip_address): bool
{
    $section_1 = "Password changed";
    $section_2 = "You just changed the password of your Emoticolor account.";
    $section_3 = "If you didn't change your password, please change it immediately.";
    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "hidden-small", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);
    $mail = new PHPMailer(true);
    try {
        global $email_address, $email_password, $email_smtp;
        $mail->isSMTP();
        $mail->Host = $email_smtp;
        $mail->SMTPAuth = true;
        $mail->Username = $email_address;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($email_address, 'Emoticolor');
        $mail->addAddress($to_email, $username);

        $mail->isHTML(true);
        $mail->Subject = "Emoticolor: password changed";
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Get the email template content
 * @return string Email template content
 */
function getEmailTemplate(): string
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