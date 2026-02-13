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
$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $learning_contents_table, $learning_statistics_table, $emotions_table;

        $login_id = $post["login-id"];
        $user_id = null;

        $emotion_id = null;
        if (isset($post["emotion-id"]) && is_numeric($post["emotion-id"])) {
            $emotion_id = intval($post["emotion-id"]);
        }

        // language handling: default 'it'
        $language = 'it';
        if (isset($post["language"]) && is_string($post["language"])) {
            if (preg_match('/^[a-z]{2}$/i', $post["language"])) {
                $language = strtolower($post["language"]);
            }
        }

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

                // if emotion-id passed, verify it exists
                if ($emotion_id !== null) {
                    $query_check_emotion = "SELECT `emotion-id` FROM $emotions_table WHERE `emotion-id` = ?";
                    $stmt_check_emotion = $c->prepare($query_check_emotion);
                    $stmt_check_emotion->bind_param("s", $emotion_id);
                    try {
                        $stmt_check_emotion->execute();
                        $result_check_emotion = $stmt_check_emotion->get_result();
                        if ($result_check_emotion->num_rows !== 1) {
                            responseError(404, "Emotion not found.");
                        }
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_check_emotion->close();
                }

                //code here
                global $learning_contents_statistics_table, $learning_contents_table;

                // Helper to build result object for a single emotion
                $build_for_emotion = function ($emotionId) use ($c, $user_id, $learning_contents_table, $learning_contents_statistics_table) {
                    $resultObj = array();
                    $resultObj['emotion-id'] = intval($emotionId);
                    $resultObj['path'] = array();
                    $resultObj['pills'] = array();

                    // Step 1: get distinct pairs (type, type-level2) from LearningContents for this emotion
                    $pairs = array();
                    $query_pairs = "SELECT `type`, `type-level2` FROM $learning_contents_table WHERE `emotion-id` = ? GROUP BY `type`, `type-level2` ORDER BY `type`, `type-level2`";
                    $stmt_pairs = $c->prepare($query_pairs);
                    if ($stmt_pairs !== false) {
                        $stmt_pairs->bind_param("s", $emotionId);
                        try {
                            $stmt_pairs->execute();
                            $res_pairs = $stmt_pairs->get_result();
                            while ($row = $res_pairs->fetch_assoc()) {
                                $type = intval($row['type']);
                                $type_level2 = isset($row['type-level2']) ? $row['type-level2'] : null;
                                if ($type_level2 !== null && is_numeric($type_level2)) $type_level2 = intval($type_level2);
                                $pairs[] = array('type' => $type, 'type_level2' => $type_level2);
                            }
                        } catch (mysqli_sql_exception $e) {
                            // ignore and continue with empty pairs
                        }
                        $stmt_pairs->close();
                    }

                    // Step 2: get user's existing stats for this emotion (one query)
                    $stats_map = array();
                    $query_stats = "SELECT `type`, `type-level2` FROM $learning_contents_statistics_table WHERE `user-id` = ? AND `emotion-id` = ?";
                    $stmt_stats = $c->prepare($query_stats);
                    if ($stmt_stats !== false) {
                        $stmt_stats->bind_param("ss", $user_id, $emotionId);
                        try {
                            $stmt_stats->execute();
                            $res_stats = $stmt_stats->get_result();
                            while ($rs = $res_stats->fetch_assoc()) {
                                $t = intval($rs['type']);
                                $tl2 = isset($rs['type-level2']) ? $rs['type-level2'] : null;
                                if ($tl2 !== null && is_numeric($tl2)) $tl2 = intval($tl2);
                                $key = $t . '|' . ($tl2 === null ? 'n' : strval($tl2));
                                $stats_map[$key] = true;
                            }
                        } catch (mysqli_sql_exception $e) {
                            // ignore and consider stats empty
                        }
                        $stmt_stats->close();
                    }

                    // Step 3: build final arrays by comparing pairs with stats_map
                    foreach ($pairs as $p) {
                        $type = $p['type'];
                        $tl2 = $p['type_level2'];
                        $key = $type . '|' . ($tl2 === null ? 'n' : strval($tl2));
                        $done = isset($stats_map[$key]);
                        $entry = array('type-level2' => $tl2, 'done' => $done);
                        if ($type === 0) array_push($resultObj['path'], $entry);
                        else array_push($resultObj['pills'], $entry);
                    }

                    return $resultObj;
                };

                // If emotion-id provided, return single object; otherwise return array of objects for all emotions
                if ($emotion_id !== null) {
                    $out = $build_for_emotion($emotion_id);
                    responseSuccess(200, null, $out);
                } else {
                    // fetch all emotion-ids that have learning contents
                    $query_emotions = "SELECT DISTINCT `emotion-id` FROM $learning_contents_table ORDER BY `emotion-id`";
                    $stmt_em = $c->prepare($query_emotions);
                    if ($stmt_em === false) {
                        responseError(500, "Database error: failed to prepare statement");
                        $stmt_get_user_id->close();
                        $c->close();
                        exit();
                    }
                    try {
                        $stmt_em->execute();
                        $res_em = $stmt_em->get_result();
                        $all = array();
                        while ($r = $res_em->fetch_assoc()) {
                            $eid = $r['emotion-id'];
                            $all[] = $build_for_emotion($eid);
                        }
                        responseSuccess(200, null, $all);
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_em->close();
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
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>