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


$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["emotion-id"]) && checkNumberValidity($post["emotion-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $emotions_followed_table, $emotions_table;

        $login_id = $post["login-id"];
        $emotion_id = $post["emotion-id"];

        // 1) resolve current user
        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        if ($stmt_get_user_id === false) responseError(500, "Database prepare error: " . $c->error);
        $stmt_get_user_id->bind_param("s", $login_id);
        try {
            $stmt_get_user_id->execute();
            $res_uid = $stmt_get_user_id->get_result();
            if ($res_uid->num_rows !== 1) responseError(440, "Unauthorized: invalid or expired login-id");
            $user_row = $res_uid->fetch_assoc();
            $user_id = $user_row['user-id'];
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_user_id->close();

        // 2) ensure emotion exists
        $q_em = "SELECT `emotion-id` FROM $emotions_table WHERE `emotion-id` = ? LIMIT 1";
        $st_em = $c->prepare($q_em);
        if ($st_em === false) responseError(500, "Database prepare error: " . $c->error);
        $st_em->bind_param("s", $emotion_id);
        try {
            $st_em->execute();
            $r_em = $st_em->get_result();
            if ($r_em->num_rows !== 1) responseError(404, "Emotion not found.");
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $st_em->close();

        // 3) check followed
        $q_check = "SELECT 1 FROM $emotions_followed_table WHERE `user-id` = ? AND `emotion-id` = ? LIMIT 1";
        $stc = $c->prepare($q_check);
        if ($stc === false) responseError(500, "Database prepare error: " . $c->error);
        $stc->bind_param("ss", $user_id, $emotion_id);
        try {
            $stc->execute();
            $rf = $stc->get_result();
            if ($rf->num_rows === 0) responseError(404, "Emotion not followed.");
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stc->close();

        // 4) delete
        $q_del = "DELETE FROM $emotions_followed_table WHERE `user-id` = ? AND `emotion-id` = ?";
        $st_del = $c->prepare($q_del);
        if ($st_del === false) responseError(500, "Database prepare error: " . $c->error);
        $st_del->bind_param("ss", $user_id, $emotion_id);
        try {
            $st_del->execute();
            responseSuccess(204, null, null);
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $st_del->close();

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
    if (!isset($post["emotion-id"]) || !checkNumberValidity($post["emotion-id"])) {
        array_push($missing_parameters, "emotion-id");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>