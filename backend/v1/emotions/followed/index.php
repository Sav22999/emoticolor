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


$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $emotions_followed_table, $emotions_table;

        $login_id = $post["login-id"];
        $user_id = null;

        $language = "it"; //default language
        if (isset($post["language"]) && checkFieldValidity($post["language"])) {
            $language = $post["language"];
        }

        // 1) resolve current user from login (must be status=1)
        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        if ($stmt_get_user_id === false) responseError(500, "Database prepare error: " . $c->error);
        $stmt_get_user_id->bind_param("s", $login_id);
        try {
            $stmt_get_user_id->execute();
            $res_uid = $stmt_get_user_id->get_result();
            if ($res_uid->num_rows !== 1) responseError(440, "Unauthorized: invalid or expired login-id");
            $user_id = $res_uid->fetch_assoc()['user-id'];
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_user_id->close();

        // 2) select followed emotions for this user
        $q_f = "SELECT `emotions-followed`.`follow-id`, `emotions-followed`.`user-id`, `emotions-followed`.`emotion-id`,  `emotions-followed`.`created`, `emotions`.* FROM $emotions_followed_table AS `emotions-followed` INNER JOIN $emotions_table AS `emotions` ON `emotions-followed`.`emotion-id` = `emotions`.`emotion-id` WHERE `emotions-followed`.`user-id` = ? ORDER BY `emotions-followed`.`created` DESC";
        $stf = $c->prepare($q_f);
        if ($stf === false) responseError(500, "Database prepare error: " . $c->error);
        $stf->bind_param("s", $user_id);
        try {
            $stf->execute();
            $res_f = $stf->get_result();
            if ($res_f->num_rows > 0) {
                $rows = array();
                while ($r = $res_f->fetch_assoc()) {
                    //$rows[] = $r;
                    $array_to_add = [];
                    if (isset($r[$language])) {
                        $array_to_add['emotion-text'] = $r[$language];
                    } else {
                        $array_to_add['emotion-text'] = $r['it'];
                    }
                    if (isset($r['emotion-id'])) {
                        $array_to_add['emotion-id'] = $r['emotion-id'];
                    }
                    if ($array_to_add != []) $rows[] = $array_to_add;
                }
                responseSuccess(200, null, array_values($rows));
            } else {
                responseError(404, "No followed emotions found.", []);
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stf->close();

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
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>