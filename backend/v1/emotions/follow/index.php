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

$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["emotion-id"]) && checkNumberValidity($post["emotion-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $emotions_followed_table, $emotions_table;

        $login_id = $post["login-id"];
        $emotion_id = $post["emotion-id"];
        $user_id = null;

        $action = null;

        $query_followed_emotion = "SELECT `emotions-followed`.`follow-id`, `emotions-followed`.`user-id`, `emotions-followed`.`emotion-id` FROM $emotions_followed_table AS `emotions-followed` INNER JOIN (SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1) AS `emotions-users` ON `emotions-followed`.`user-id` = `emotions-users`.`user-id` WHERE `emotion-id` = ?";
        $stmt_get_followed_emotion = $c->prepare($query_followed_emotion);
        $stmt_get_followed_emotion->bind_param("ss", $login_id, $emotion_id);

        try {
            $stmt_get_followed_emotion->execute();
            $result = $stmt_get_followed_emotion->get_result();


            if ($result->num_rows === 0) {
                //emotion not yet followed

                //check if the "emotion-id" exists
                $query_check_emotion = "SELECT `emotion-id` FROM $emotions_table WHERE `emotion-id` = ?";
                $stmt_check_emotion = $c->prepare($query_check_emotion);
                $stmt_check_emotion->bind_param("s", $emotion_id);

                try {
                    $stmt_check_emotion->execute();

                    $result_check_emotion = $stmt_check_emotion->get_result();
                    if ($result_check_emotion->num_rows === 1) {
                        //emotion exists, get user id

                        //get user id from logins
                        $query_get_user_id = "SELECT `logins`.`user-id` AS `user-id` FROM $logins_table AS `logins` WHERE `logins`.`login-id` = ?";
                        $stmt_get_user_id = $c->prepare($query_get_user_id);
                        $stmt_get_user_id->bind_param("s", $login_id);
                        try {
                            $stmt_get_user_id->execute();
                            $result_get_user_id = $stmt_get_user_id->get_result();
                            if ($result_get_user_id->num_rows === 1) {
                                $row_user = $result_get_user_id->fetch_assoc();
                                $user_id = $row_user["user-id"];

                                //insert follow record
                                $query_insert_follow = "INSERT INTO $emotions_followed_table (`follow-id`, `user-id`, `created`, `emotion-id`) VALUES (NULL, ?, CURRENT_TIMESTAMP, ?)";
                                $stmt_insert_follow = $c->prepare($query_insert_follow);
                                $stmt_insert_follow->bind_param("ss", $user_id, $emotion_id);

                                try {
                                    $stmt_insert_follow->execute();

                                    responseSuccess(204, null, null);
                                } catch (mysqli_sql_exception $e) {
                                    responseError(500, "Database error: " . $e->getMessage());
                                }
                                $stmt_insert_follow->close();
                            } else {
                                responseError(500, "User not found.");
                            }
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_get_user_id->close();
                    } else {
                        //emotion doesn't exist
                        responseError(404, "Emotion not found.");
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_check_emotion->close();
            } else {
                //emotion already followed
                responseError(404, "Emotion already followed.");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_followed_emotion->close();

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