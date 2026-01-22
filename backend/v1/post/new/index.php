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

$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) &&
    isset($post["visibility"]) && checkNumberValidity($post["visibility"]) &&
    ($post["visibility"] == 0 || $post["visibility"] == 1) &&
    isset($post["emotion-id"]) && checkNumberValidity($post["emotion-id"]) &&
    isset($post["language"]) && checkFieldValidity($post["language"]) &&
    isset($post["color-id"]) && checkFieldValidity($post["color-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $reactions_table, $posts_table, $emotions_table, $colors_table, $places_table, $locations_table, $weather_table, $together_with_table, $body_parts_table, $images_table;
        global $notifications_table;

        $login_id = $post["login-id"];
        $language = $post["language"];
        $visibility = $post["visibility"];
        $emotion_id = $post["emotion-id"];
        $color_id = $post["color-id"];

        //optional
        $text = null;
        if (isset($post["text"]) && checkFieldValidity($post["text"])) {
            $text = $post["text"];
        }
        $place_id = null;
        if (isset($post["place-id"]) && checkNumberValidity($post["place-id"])) {
            $place_id = $post["place-id"];
        }
        $location = null;
        if (isset($post["location"]) && checkFieldValidity($post["location"])) {
            $location = $post["location"];
        }
        $weather_id = null;
        if (isset($post["weather-id"]) && checkNumberValidity($post["weather-id"])) {
            $weather_id = $post["weather-id"];
        }
        $together_with_id = null;
        if (isset($post["together-with-id"]) && checkNumberValidity($post["together-with-id"])) {
            $together_with_id = $post["together-with-id"];
        }
        $body_part_id = null;
        if (isset($post["body-part-id"]) && checkNumberValidity($post["body-part-id"])) {
            $body_part_id = $post["body-part-id"];
        }
        $image_id = null;
        if (isset($post["image-id"]) && checkFieldValidity($post["image-id"])) {
            $image_id = $post["image-id"];
        }

        $user_id = null;

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];

                //check if all data is valid (e.g., emotion-id exists, color-id exists, etc.)
                $query_check_emotion_id = "SELECT `emotion-id` FROM $emotions_table WHERE `emotion-id` = ?";
                $stmt_check_emotion_id = $c->prepare($query_check_emotion_id);
                $stmt_check_emotion_id->bind_param("i", $emotion_id);
                try {
                    $stmt_check_emotion_id->execute();
                    $result_check_emotion_id = $stmt_check_emotion_id->get_result();

                    if ($result_check_emotion_id->num_rows > 0) {
                        //emotion-id exists

                        $query_check_color_id = "SELECT `color-id` FROM $colors_table WHERE `color-id` = ?";
                        $stmt_check_color_id = $c->prepare($query_check_color_id);
                        $stmt_check_color_id->bind_param("s", $color_id);
                        try {
                            $stmt_check_color_id->execute();
                            $result_check_color_id = $stmt_check_color_id->get_result();

                            if ($result_check_color_id->num_rows > 0) {
                                //color-id exists

                                $wrong_optional_parameters = array();

                                //check optional parameters
                                if ($place_id !== null) {
                                    $query_check_place_id = "SELECT `place-id` FROM $places_table WHERE `place-id` = ?";
                                    $stmt_check_place_id = $c->prepare($query_check_place_id);
                                    $stmt_check_place_id->bind_param("i", $place_id);
                                    try {
                                        $stmt_check_place_id->execute();
                                        $result_check_place_id = $stmt_check_place_id->get_result();

                                        if ($result_check_place_id->num_rows === 0) {
                                            array_push($wrong_optional_parameters, "place-id");
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        //database error
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_check_place_id->close();
                                }

                                if ($weather_id !== null) {
                                    $query_check_weather_id = "SELECT `weather-id` FROM $weather_table WHERE `weather-id` = ?";
                                    $stmt_check_weather_id = $c->prepare($query_check_weather_id);
                                    $stmt_check_weather_id->bind_param("i", $weather_id);
                                    try {
                                        $stmt_check_weather_id->execute();
                                        $result_check_weather_id = $stmt_check_weather_id->get_result();

                                        if ($result_check_weather_id->num_rows === 0) {
                                            array_push($wrong_optional_parameters, "weather-id");
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        //database error
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_check_weather_id->close();
                                }

                                if ($together_with_id !== null) {
                                    $query_check_together_with_id = "SELECT `together-with-id` FROM $together_with_table WHERE `together-with-id` = ?";
                                    $stmt_check_together_with_id = $c->prepare($query_check_together_with_id);
                                    $stmt_check_together_with_id->bind_param("i", $together_with_id);
                                    try {
                                        $stmt_check_together_with_id->execute();
                                        $result_check_together_with_id = $stmt_check_together_with_id->get_result();

                                        if ($result_check_together_with_id->num_rows === 0) {
                                            array_push($wrong_optional_parameters, "together-with-id");
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        //database error
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_check_together_with_id->close();
                                }

                                if ($body_part_id !== null) {
                                    $query_check_body_part_id = "SELECT `body-part-id` FROM $body_parts_table WHERE `body-part-id` = ?";
                                    $stmt_check_body_part_id = $c->prepare($query_check_body_part_id);
                                    $stmt_check_body_part_id->bind_param("i", $body_part_id);
                                    try {
                                        $stmt_check_body_part_id->execute();
                                        $result_check_body_part_id = $stmt_check_body_part_id->get_result();

                                        if ($result_check_body_part_id->num_rows === 0) {
                                            array_push($wrong_optional_parameters, "body-part-id");
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        //database error
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_check_body_part_id->close();
                                }

                                if ($image_id !== null) {
                                    $query_check_image_id = "SELECT `image-id` FROM $images_table WHERE `image-id` = ?";
                                    $stmt_check_image_id = $c->prepare($query_check_image_id);
                                    $stmt_check_image_id->bind_param("s", $image_id);
                                    try {
                                        $stmt_check_image_id->execute();
                                        $result_check_image_id = $stmt_check_image_id->get_result();

                                        if ($result_check_image_id->num_rows === 0) {
                                            array_push($wrong_optional_parameters, "image-id");
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        //database error
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_check_image_id->close();
                                }

                                if ($text !== null) {
                                    if (strlen($text) > 500) {
                                        array_push($wrong_optional_parameters, "text");
                                    }
                                }

                                if ($location !== null) {
                                    if (strlen($location) > 200) {
                                        array_push($wrong_optional_parameters, "location");
                                    }
                                }

                                if (count($wrong_optional_parameters) > 0) {
                                    responseError(400, "Invalid optional parameters: " . implode(", ", $wrong_optional_parameters));
                                } else {
                                    //all data is valid
                                    //insert the new post

                                    $post_id = generateUUIDv4();

                                    $query_insert_post = "INSERT INTO $posts_table (`post-id`, `user-id`, `visibility`, `emotion-id`, `language`, `color-id`, `text`, `place-id`, `location`, `weather-id`, `together-with-id`, `body-part-id`, `image-id`, `created`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                                    $stmt_insert_post = $c->prepare($query_insert_post);
                                    $stmt_insert_post->bind_param("ssiisssisiiis", $post_id, $user_id, $visibility, $emotion_id, $language, $color_id, $text, $place_id, $location, $weather_id, $together_with_id, $body_part_id, $image_id);
                                    try {
                                        $stmt_insert_post->execute();
                                        $stmt_insert_post->close();

                                        //add record in Notifications
                                        $query_insert_notification = "INSERT INTO $notifications_table (`notification-id`, `user-id`, `emotion-id`, `post-id`, `action`, `language`, `created`) VALUES (NULL, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                                        $stmt_insert_notification = $c->prepare($query_insert_notification);
                                        $action_notification = "new-post";
                                        $stmt_insert_notification->bind_param("sisss", $user_id, $emotion_id, $post_id, $action_notification, $language);
                                        try {
                                            $stmt_insert_notification->execute();
                                        } catch (mysqli_sql_exception $e) {
                                            //database error
                                        }
                                        $stmt_insert_notification->close();

                                        responseSuccess(200, null, array("post-id" => $post_id));
                                    } catch (mysqli_sql_exception $e) {
                                        responseError(500, "Database error: " . $e->getMessage());
                                    }
                                    $stmt_insert_post->close();
                                }
                            } else {
                                responseError(400, "Invalid color-id");
                            }
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_check_color_id->close();
                    } else {
                        responseError(400, "Invalid emotion-id");
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_check_emotion_id->close();
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
    if (!isset($post["visibility"]) || !checkNumberValidity($post["visibility"]) ||
        ($post["visibility"] != 0 && $post["visibility"] != 1)) {
        array_push($missing_parameters, "visibility");
    }
    if (!isset($post["emotion-id"]) || !checkNumberValidity($post["emotion-id"])) {
        array_push($missing_parameters, "emotion-id");
    }
    if (!isset($post["language"]) || !checkFieldValidity($post["language"])) {
        array_push($missing_parameters, "language");
    }
    if (!isset($post["color-id"]) || !checkFieldValidity($post["color-id"])) {
        array_push($missing_parameters, "color-id");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>