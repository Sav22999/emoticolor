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

// validate input
if (!isset($post['username']) || !checkUsernameValidity($post['username'])) {
    responseError(400, "Missing or invalid parameter: username");
    exit;
}

$username = $post['username'];

if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
    $c->set_charset("utf8mb4");
    global $users_table;

    try {
        $query = "SELECT `user-id` FROM $users_table WHERE `username` = ? LIMIT 1";
        $stmt = $c->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 0) {
            // available
            responseSuccess(204, null, null);
        } else {
            // already exists
            responseError(409, "Username already taken");
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        responseError(500, "Database error: " . $e->getMessage());
    }

    $c->close();
} else {
    responseError(500, "Database connection error");
}
?>
