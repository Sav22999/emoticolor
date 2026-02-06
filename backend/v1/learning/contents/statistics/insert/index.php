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


//use the following code of example for AUTHENTICATED requests
$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["emotion-id"]) && checkNumberValidity($post["emotion-id"]) && isset($post["type"]) && checkNumberValidity($post["type"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $learning_statistics_table, $emotions_table, $learning_contents_statistics_table;

        $login_id = $post["login-id"];
        $emotion_id = $post["emotion-id"];

        $type2 = null;
        if (isset($post["type2"]) && checkNumberValidity($post["type2"])) {
            $type2 = intval($post["type2"]);
        }

        $user_id = null;

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];

                //check if emotion exists
                $query_check_emotion = "SELECT `emotion-id` FROM $emotions_table WHERE `emotion-id` = ?";
                $stmt_check_emotion = $c->prepare($query_check_emotion);
                $stmt_check_emotion->bind_param("s", $emotion_id);

                try {
                    $stmt_check_emotion->execute();
                    $result_check_emotion = $stmt_check_emotion->get_result();
                    if ($result_check_emotion->num_rows !== 1) {
                        //emotion doesn't exist
                        responseError(404, "Emotion not found.");
                        $stmt_check_emotion->close();
                        $stmt_get_user_id->close();
                        $c->close();
                        exit();
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                    $stmt_check_emotion->close();
                    $stmt_get_user_id->close();
                    $c->close();
                    exit();
                }
                $stmt_check_emotion->close();

                // start transaction to avoid races and use SELECT ... FOR UPDATE
                try {
                    $c->begin_transaction();
                } catch (mysqli_sql_exception $e) {
                    // if transaction not supported, continue without it
                }

                //HERE THE CODE

                try {
                    $type = intval($post["type"]);

                    // allowed types: 0 (guided path), 1 (pills)
                    if (!in_array($type, array(0, 1), true)) {
                        responseError(400, "Invalid type. Allowed values: 0,1");
                        $stmt_get_user_id->close();
                        $c->close();
                        exit();
                    }

                    $inserted = false;

                    if ($type2 === null) {
                        // insert with NULL for type-level2
                        $query_insert = "INSERT INTO $learning_contents_statistics_table (`statistic-id`, `user-id`, `emotion-id`, `type`, `type-level2`, `created`) VALUES (NULL, ?, ?, ?, NULL, CURRENT_TIMESTAMP)";
                        $stmt_insert = $c->prepare($query_insert);
                        if ($stmt_insert === false) {
                            responseError(500, "Database error: failed to prepare statement");
                            $stmt_get_user_id->close();
                            $c->close();
                            exit();
                        }
                        $stmt_insert->bind_param("sss", $user_id, $emotion_id, $type);
                    } else {
                        $type2_val = intval($type2);
                        $query_insert = "INSERT INTO $learning_contents_statistics_table (`statistic-id`, `user-id`, `emotion-id`, `type`, `type-level2`, `created`) VALUES (NULL, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                        $stmt_insert = $c->prepare($query_insert);
                        if ($stmt_insert === false) {
                            responseError(500, "Database error: failed to prepare statement");
                            $stmt_get_user_id->close();
                            $c->close();
                            exit();
                        }
                        $stmt_insert->bind_param("ssii", $user_id, $emotion_id, $type, $type2_val);
                    }

                    try {
                        $stmt_insert->execute();
                        $inserted = true;
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }

                    if (isset($stmt_insert) && $stmt_insert !== false) $stmt_insert->close();

                    if ($inserted) {
                        try {
                            $c->commit();
                        } catch (mysqli_sql_exception $e) {
                            // ignore commit error
                        }
                        responseSuccess(201, "Statistic inserted", null);
                    }

                } catch (mysqli_sql_exception $e) {
                    // rollback transaction if started
                    try {
                        $c->rollback();
                    } catch (Exception $ex) {
                    }
                    responseError(500, "Database error: " . $e->getMessage());
                }


            } else {
                //unauthorized: login-id not found or expired
                responseError(440, "Unauthorized: invalid or expired login-id");
            }
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
    if (!isset($post["emotion-id"]) || !checkNumberValidity($post["emotion-id"])) {
        array_push($missing_parameters, "emotion-id");
    }
    if (!isset($post["type"]) || !checkNumberValidity($post["type"])) {
        array_push($missing_parameters, "type");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}
?>
