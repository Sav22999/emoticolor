<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/logs.php");
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
 * Check if a username is valid (min 5 chars, max 20 chars, only letters (lowercase), numbers and the char '.')
 * @param $username string Username to check
 * @return null|string True if the username is valid, false otherwise
 */
function getUsernameValidated(string $username)
{
    $username_to_use = trim(strtolower($username));
    if (preg_match('/^[a-z0-9.]{5,20}$/', $username_to_use)) {
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
function checkUsernameValidity(string $username): bool
{
    return getUsernameValidated($username) !== null;
}

/**
 * Check if a field is valid (not null, not empty)
 * @param $field string|null Input field
 * @return bool True if the field is valid, false otherwise
 */
function checkFieldValidity(string|null $field): bool
{
    return $field !== null && trim($field) !== "";
}

/**
 * Check if a field is a number value
 * @param $field mixed Input field
 * @return bool True if the field is a number, false otherwise
 */
function checkNumberValidity(mixed $field): bool
{
    return is_numeric($field);
}

/**
 * Check if a field is an email address
 * @param $field string Input field
 * @return bool True if the field is a valid email address, false otherwise
 */
function checkEmailValidity(string $field): bool
{
    return filter_var($field, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if a field is a valid password (min 10 chars, min 1 uppercase, min 1 lowercase, min 1 number)
 * @param $password string Input password
 * @return bool True if the password is valid, false otherwise
 */
function checkPasswordValidity(string $password): bool
{
    if (strlen($password) < 10) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
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

/**
 * Generate Argon2id hash of a text
 * @param $text string Input text
 * @return string Argon2id hashed text
 */
function argon2idHash(string $text): string
{
    return password_hash($text, PASSWORD_ARGON2ID);
}

/**
 * Verify a password against its Argon2id hash
 * @param $password string Password in plain text
 * @param $hash_password string Hashed password
 * @return bool True if the password matches the hash, false otherwise
 */
function checkPasswordHash(string $password, string $hash_password)
{
    return checkArgon2idHash($password, $hash_password);
}

/**
 * Check if an Argon2id hashed text matches the original text
 * @param $text string First hashed text
 * @param $hash_text string Second hashed text
 * @return bool True if the hashes are equal, false otherwise
 */
function checkArgon2idHash(string $text, string $hash_text): bool
{
    return password_verify($text, $hash_text);
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
 * Encrypt a text using a password (using AES-256-CBC with HMAC-SHA256 for integrity)
 * @param $password string Password to derive the key
 * @param $salt string Salt for key derivation
 * @return string Derived key from password
 */
function encryptTextWithPassword($text, $password)
{
    $salt = openssl_random_pseudo_bytes(32);
    $derivedKeys = deriveKeyFromPassword($password, $salt, 64, 100000);
    $encKey = substr($derivedKeys, 0, 32);
    $hmacKey = substr($derivedKeys, 32, 32);

    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encryptedText = openssl_encrypt($text, 'aes-256-cbc', $encKey, OPENSSL_RAW_DATA, $iv);

    $messageToSign = $salt . $iv . $encryptedText;
    $hmac = hash_hmac('sha256', $messageToSign, $hmacKey, true);

    // Result: HMAC + Salt + IV + Ciphertext
    return base64_encode($hmac . $messageToSign);
}

/**
 * Decrypt a text using a password (using AES-256-CBC with HMAC-SHA256 for integrity)
 * @param $encryptedText string Encrypted text
 * @param $password string Password
 * @return false|string Decrypted text or false on failure
 */
function decryptTextWithPassword(string $encryptedText, string $password)
{
    $decoded = base64_decode($encryptedText);

    $hmacReceived = substr($decoded, 0, 32);
    $salt = substr($decoded, 32, 32);
    $iv = substr($decoded, 64, 16);
    $cipherTextOnly = substr($decoded, 80);
    $messageToVerify = substr($decoded, 32);

    $derivedKeys = deriveKeyFromPassword($password, $salt, 64, 100000);
    $encKey = substr($derivedKeys, 0, 32);
    $hmacKey = substr($derivedKeys, 32, 32);

    // Check integrity (use hash_equals to prevent timing attacks)
    $hmacCalculated = hash_hmac('sha256', $messageToVerify, $hmacKey, true);
    if (!hash_equals($hmacReceived, $hmacCalculated)) {
        return false; // Integrity check failed
    }

    return openssl_decrypt($cipherTextOnly, 'aes-256-cbc', $encKey, OPENSSL_RAW_DATA, $iv);
}

/**
 * Derive a key from a password using PBKDF2
 * @param $password string Password
 * @param $salt string Salt
 * @param $keyLength int Key length in bytes (default 32 bytes for AES-256)
 * @param $iterations int Number of iterations (default 100000)
 * @param $algorithm string Hash algorithm (default 'sha256')
 * @return string Derived key
 */
function deriveKeyFromPassword(string $password, string $salt, int $keyLength = 32, int $iterations = 100000, string $algorithm = 'sha256'): string
{
    return hash_pbkdf2($algorithm, $password, $salt, $iterations, $keyLength, true);
}

/**
 * Get the client's IP address
 * @return string Client's IP address
 */
function getIpAddress(): string
{
    $ip = 'Unknown';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // può essere una lista di IP separati da virgole
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        foreach ($parts as $p) {
            if (filter_var($p, FILTER_VALIDATE_IP)) {
                $ip = $p;
                break;
            }
        }
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
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
function generateOtpCode(): string
{
    //$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $letters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ'; //letters without O, to avoid confusion with 0
    $numbers = '0123456789';

    $pickLetter = function (int $count) use ($letters) {
        $out = '';
        $max = strlen($letters) - 1;
        for ($i = 0; $i < $count; $i++) {
            $out .= $letters[random_int(0, $max)];
        }
        return $out;
    };
    $pickNumber = function (int $count) use ($numbers) {
        $out = '';
        $max = strlen($numbers) - 1;
        for ($i = 0; $i < $count; $i++) {
            $out .= $numbers[random_int(0, $max)];
        }
        return $out;
    };

    //return $pickLetter(3) . '-' . $pickNumber(4) . '-' . $pickLetter(3);
    return $pickLetter(2) . "-" . $pickNumber(5);
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
 * Get the email template content
 * @return string Email template content
 */
function getEmailTemplate(): string
{
    $path = $_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/v1/email-template.php";
    if (is_readable($path)) {
        return file_get_contents($path);
    }
    return ""; // fallback vuoto
}

/**
 * Create and configure a PHPMailer instance
 * @return PHPMailer Configured PHPMailer instance
 */
function createMailer(): PHPMailer
{
    global $email_address, $email_password, $email_smtp;
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $email_smtp;
    $mail->SMTPAuth = true;
    $mail->Username = $email_address;
    $mail->Password = $email_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($email_address, 'Emoticolor');
    $mail->isHTML(true);

    return $mail;
}

/**
 * Send signup verification email
 * @param $username string Username of the user
 * @param $to_email string Recipient email address
 * @param $code string Verification code
 * @param $ip_address string IP address of the user
 * @param $verification_expiry bool|null Expiry time of the verification code
 * @param $new_code bool Whether it's a new code request
 * @return bool True if the email was sent successfully, false otherwise
 */
function sendEmailSigningup(string $username, string $to_email, string $code, string $ip_address, ?bool $verification_expiry = null, bool $new_code = false): bool
{
    $message_code = $new_code ? "You required another verification code.<br>" : "Thank you for signing up to Emoticolor.<br>";
    $message_title = $new_code ? "New code to verify your email" : "Verify your email";

    $section_1 = $message_title;
    $section_2 = $message_code . "To confirm your email address, please use the following code:";
    $section_3 = "The code will be valid for 1 hour.<br>If you aren't signing up to Emoticolor, please ignore this email.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "", $message);
    $message = str_replace("{{code}}", $code, $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    // use helper to get configured PHPMailer
    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
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
    $message = str_replace("{{hidden-code}}", "display: none;", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
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
 * @param $verification_expiry string|null Expiry time of the verification code
 * @param $new_code bool Whether it's a new code request
 * @return bool
 */
function sendEmailLoggingin(string $username, string $to_email, string $code, string $ip_address, ?string $verification_expiry = null, bool $new_code = false): bool
{
    $message_code = $new_code ? "You required another OTP to verify the login process.<br>" : "";
    $message_title = $new_code ? "New code to log in" : "Confirm your log in";

    $section_1 = $message_title;
    $section_2 = $message_code . "To confirm your login, please use the following code:";
    $section_3 = "The code will be valid for 1 hour.<br>If you didn't log in to Emoticolor, you should definitely change your password.";

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);
    $message = str_replace("{{hidden-code}}", "", $message);
    $message = str_replace("{{code}}", $code, $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
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
    $message = str_replace("{{hidden-code}}", "display: none;", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);

    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
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

    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
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
    $message = str_replace("{{hidden-code}}", "display: none;", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "hidden", $message);
    $message = str_replace("{{ip-address}}", "", $message);

    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
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
    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
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
    $message = str_replace("{{hidden-code}}", "display: none;", $message);
    $message = str_replace("{{code}}", "", $message);
    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", "", $message);
    $message = str_replace("{{ip-address}}", $ip_address, $message);
    $mail = createMailer();
    try {
        $mail->addAddress($to_email, $username);
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
 * Send a generic operation notification email.
 * Can be used both to send an OTP for an operation (success=false and $code provided)
 * or to send a confirmation that the operation completed (success=true).
 *
 * @param string $username
 * @param string $to_email
 * @param string $action e.g. 'reset-password', 'change-password', 'delete-account'
 * @param string $ip_address
 * @param bool $success whether the operation already succeeded (true) or it's a request that requires a code (false)
 * @param string|null $code optional OTP/code to include when success=false
 * @param string|null $expiry optional expiry text to include when sending a code
 * @return bool
 */
function sendEmailOperationNotification(string $username, string $to_email, string $action, string $ip_address = "", bool $success = true, ?string $code = null, ?string $expiry = null): bool
{
    // Normalize action to a human friendly label
    $action_label = str_replace('-', ' ', $action);
    $action_label = ucfirst($action_label);

    if ($success) {
        $section_1 = "$action_label";
        $section_2 = "You requested $action_label. The operation has been completed successfully.";
        $section_3 = "If you didn't perform this operation, please contact Emoticolor support or change your password immediately.";
    } else {
        // sending a code for confirmation
        $expiry_text = $expiry ? $expiry : "1 hour";
        $section_1 = "$action_label request";
        $section_2 = "You requested $action_label. To confirm this operation, please use the following code:";
        $section_3 = "The code will be valid for $expiry_text. If you didn't request this operation, please ignore this email or change your password immediately.";
    }

    $message = getEmailTemplate();
    $message = str_replace("{{username}}", $username, $message);
    $message = str_replace("{{section-1}}", $section_1, $message);
    $message = str_replace("{{section-2}}", $section_2, $message);

    if ($success) {
        $message = str_replace("{{hidden-code}}", "display: none;", $message);
        $message = str_replace("{{code}}", "", $message);
    } else {
        $message = str_replace("{{hidden-code}}", "", $message);
        $message = str_replace("{{code}}", $code ?? "", $message);
    }

    $message = str_replace("{{section-3}}", $section_3, $message);
    $message = str_replace("{{hidden-ip-address}}", $success ? "hidden" : "", $message);
    $message = str_replace("{{ip-address}}", $ip_address ?? "", $message);

    $mail = createMailer();

    try {
        $mail->addAddress($to_email, $username);
        $subject_action = $success ? "$action_label: operation successful" : "$action_label: action required";
        $mail->Subject = "Emoticolor: " . $subject_action;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

/**
 * Send a JSON error response
 * @param $code int HTTP status code
 * @param $message string|null Error message
 * @param $data mixed|null Additional data (optional)
 * @return void
 */
function responseError(int $code, ?string $message = null, mixed $data = null): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    if ($message === null || $message === "") {
        $message = "An error occurred.";
    }
    $response = ["message" => $message];
    if ($data !== null) $response["data"] = $data;
    echo json_encode($response);
    exit;
}

/**
 * Send a JSON success response
 * @param $code int HTTP status code
 * @param $message string|null Success message
 * @param $data mixed|null Additional data (optional)
 * @return void
 */
function responseSuccess(int $code, ?string $message = null, mixed $data = null): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    if ($message === null || $message === "") {
        $message = "Success.";
    }
    $response = ["message" => $message];
    if ($data !== null) $response["data"] = $data;
    echo json_encode($response);
    exit;
}

?>

