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

$condition = isset($get["q"]) && checkFieldValidity($get["q"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;

        $login_id =null;
        if(isset($post["login-id"]) && checkFieldValidity($post["login-id"])){
            $login_id = $post["login-id"];
        }
        $user_id = null;

        $filter_by_user = false;
        if (isset($get["user"]) && checkFieldValidity($get["user"]) && $get["user"]==='true'){
            $filter_by_user = true;
        }
        $filter_by_emotion = false;
        if (isset($get["emotion"]) && checkFieldValidity($get["emotion"]) && $get["emotion"]==='true'){
            $filter_by_emotion = true;
        }

        $action = null;

        if($login_id !== null) {
            $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
            $stmt_get_user_id = $c->prepare($query_get_user_id);
            $stmt_get_user_id->bind_param("s", $login_id);
        }

        try {
            if ($login_id !== null) {
                $stmt_get_user_id->execute();
                $result = $stmt_get_user_id->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();

                    $user_id = $row["user-id"];
                } else {
                    //unauthorized: login-id not found or expired
                    responseError(440, "Unauthorized: invalid or expired login-id");
                }
            }

            //HERE THE CODE FOR THE SEARCH
            //if (emotion filter is true, check if the query "%LIKE%" the emotion names ("it" column))
            //if (user filter is true, check if the query "%LIKE%" the usernames ("username" column))
            //if login!==null, then check also if the user/emotion is FOLLOWED by the user with the login-id (tables: $users_followed_table, $emotions_followed_table)
            //the result should be in this was: [{type: "user"/"emotion", id: "null for user/the id of emotion", name: "the username/emotion name", followed: true/false | null if not logged}, ...]


        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_user_id->close();

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