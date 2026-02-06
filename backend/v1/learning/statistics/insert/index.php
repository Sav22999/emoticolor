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
        global $learning_statistics_table, $emotions_table;

        $login_id = $post["login-id"];
        $emotion_id = $post["emotion-id"];

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

                //QUI CODICE

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

                //retrieve existing learning statistics for this user and emotion (locked)
                $query_get_stats = "SELECT `statistic-id`, `type` FROM $learning_statistics_table WHERE `user-id` = ? AND `emotion-id` = ? ORDER BY `created` ASC FOR UPDATE";
                $stmt_get_stats = $c->prepare($query_get_stats);
                $stmt_get_stats->bind_param("ss", $user_id, $emotion_id);

                try {
                    $stmt_get_stats->execute();
                    $result_stats = $stmt_get_stats->get_result();

                    $type = intval($post["type"]);

                    //allowed types: 1,2,3
                    if (!in_array($type, array(1, 2, 3), true)) {
                        responseError(400, "Invalid type. Allowed values: 1,2,3");
                        $stmt_get_stats->close();
                        $stmt_get_user_id->close();
                        $c->close();
                        exit();
                    }

                    $has_any = $result_stats->num_rows > 0;
                    $type1_count = 0;
                    $type2_count = 0;
                    $type3_count = 0;
                    // reset result pointer: we already have result_stats, iterate to count types
                    $rows_stats = array();
                    while ($row_stat = $result_stats->fetch_assoc()) {
                        $rows_stats[] = $row_stat;
                        $tval = intval($row_stat['type']);
                        if ($tval === 1) $type1_count++;
                        if ($tval === 2) $type2_count++;
                        if ($tval === 3) $type3_count++;
                    }

                    //logic with stricter constraints:
                    // - at most 1 record with type=1
                    // - at most 1 record with type=2
                    // - all other records must be type=3
                    // - to insert type=2 there must be an existing type=1
                    // - to insert type=3 there must be an existing type=2

                    $inserted = false;
                    $response_message = null;

                    if ($type === 1) {
                        // Allow insert of type=1 only if there is no existing type=1
                        if ($type1_count === 0) {
                            $query_insert = "INSERT INTO $learning_statistics_table (`statistic-id`, `user-id`, `emotion-id`, `type`, `created`) VALUES (NULL, ?, ?, ?, CURRENT_TIMESTAMP)";
                            $stmt_insert = $c->prepare($query_insert);
                            $stmt_insert->bind_param("sss", $user_id, $emotion_id, $type);
                            try {
                                $stmt_insert->execute();
                                $inserted = true;
                                $response_message = "Statistic inserted";
                            } catch (mysqli_sql_exception $e) {
                                responseError(500, "Database error: " . $e->getMessage());
                            }
                            $stmt_insert->close();
                        } else {
                            // already a type=1 present
                            responseError(409, "Statistic type=1 already present.");
                        }
                    } elseif ($type === 2) {
                        // to insert type=2 there must be an existing type=1
                        if ($type1_count === 0) {
                            responseError(409, "Cannot insert type=2 without existing type=1.");
                        }
                        // only one type=2 allowed
                        if ($type2_count > 0) {
                            responseError(409, "Statistic type=2 already present.");
                        }

                        $query_insert = "INSERT INTO $learning_statistics_table (`statistic-id`, `user-id`, `emotion-id`, `type`, `created`) VALUES (NULL, ?, ?, ?, CURRENT_TIMESTAMP)";
                        $stmt_insert = $c->prepare($query_insert);
                        $stmt_insert->bind_param("sss", $user_id, $emotion_id, $type);
                        try {
                            $stmt_insert->execute();
                            $inserted = true;
                            $response_message = "Statistic inserted";
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_insert->close();

                    } else { // type === 3
                        // to insert type=3 there must be an existing type=2
                        if ($type2_count === 0) {
                            responseError(409, "Cannot insert type=3 without existing type=2.");
                        }

                        // insert type=3 freely otherwise
                        $query_insert = "INSERT INTO $learning_statistics_table (`statistic-id`, `user-id`, `emotion-id`, `type`, `created`) VALUES (NULL, ?, ?, ?, CURRENT_TIMESTAMP)";
                        $stmt_insert = $c->prepare($query_insert);
                        $stmt_insert->bind_param("sss", $user_id, $emotion_id, $type);
                        try {
                            $stmt_insert->execute();
                            $inserted = true;
                            $response_message = "Statistic inserted";
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_insert->close();
                    }

                    // normalization: keep at most 1 record type=1 and 1 record type=2 (keep the oldest by created), set all other records to type=3
                    if ($inserted) {
                        // normalize type 1
                        $query_get_type1 = "SELECT `statistic-id` FROM $learning_statistics_table WHERE `user-id` = ? AND `emotion-id` = ? AND `type` = 1 ORDER BY `created` ASC";
                        $stmt_get_type1 = $c->prepare($query_get_type1);
                        $stmt_get_type1->bind_param("ss", $user_id, $emotion_id);
                        try {
                            $stmt_get_type1->execute();
                            $res1 = $stmt_get_type1->get_result();
                            $ids1 = array();
                            while ($r1 = $res1->fetch_assoc()) {
                                $ids1[] = $r1['statistic-id'];
                            }
                            if (count($ids1) > 1) {
                                $keep1 = array_shift($ids1); // keep the oldest
                                // update the remaining to type=3
                                $placeholders = implode(",", array_fill(0, count($ids1), "?"));
                                $types = str_repeat("s", count($ids1));
                                $sql_update1 = "UPDATE $learning_statistics_table SET `type` = 3 WHERE `statistic-id` IN (" . $placeholders . ")";
                                $stmt_update1 = $c->prepare($sql_update1);
                                // bind params dynamically
                                $params = array_merge($ids1);
                                // prepare params with types string first, then ids
                                $bind_params = array_merge(array($types), $ids1);
                                // convert to references
                                $refs = array();
                                foreach ($bind_params as $k => $v) {
                                    $refs[$k] = &$bind_params[$k];
                                }
                                call_user_func_array(array($stmt_update1, 'bind_param'), $refs);
                                $stmt_update1->execute();
                                $stmt_update1->close();
                            }
                        } catch (mysqli_sql_exception $e) {
                            // normalization failure shouldn't block main flow; log and continue
                            //logError($e->getMessage());
                        }
                        $stmt_get_type1->close();

                        // normalize type 2
                        $query_get_type2 = "SELECT `statistic-id` FROM $learning_statistics_table WHERE `user-id` = ? AND `emotion-id` = ? AND `type` = 2 ORDER BY `created` ASC";
                        $stmt_get_type2 = $c->prepare($query_get_type2);
                        $stmt_get_type2->bind_param("ss", $user_id, $emotion_id);
                        try {
                            $stmt_get_type2->execute();
                            $res2 = $stmt_get_type2->get_result();
                            $ids2 = array();
                            while ($r2 = $res2->fetch_assoc()) {
                                $ids2[] = $r2['statistic-id'];
                            }
                            if (count($ids2) > 1) {
                                $keep2 = array_shift($ids2);
                                $placeholders2 = implode(",", array_fill(0, count($ids2), "?"));
                                $types2 = str_repeat("s", count($ids2));
                                $sql_update2 = "UPDATE $learning_statistics_table SET `type` = 3 WHERE `statistic-id` IN (" . $placeholders2 . ")";
                                $stmt_update2 = $c->prepare($sql_update2);
                                $bind_params2 = array_merge(array($types2), $ids2);
                                $refs2 = array();
                                foreach ($bind_params2 as $k => $v) {
                                    $refs2[$k] = &$bind_params2[$k];
                                }
                                call_user_func_array(array($stmt_update2, 'bind_param'), $refs2);
                                $stmt_update2->execute();
                                $stmt_update2->close();
                            }
                        } catch (mysqli_sql_exception $e) {
                            //logError($e->getMessage());
                        }
                        $stmt_get_type2->close();
                    }

                    // after normalization send success response and commit
                    if ($inserted) {
                        try {
                            $c->commit();
                        } catch (mysqli_sql_exception $e) {
                            // ignore commit errors
                        }
                        responseSuccess(201, $response_message, null);
                    }

                } catch (mysqli_sql_exception $e) {
                    // rollback transaction if started
                    try {
                        $c->rollback();
                    } catch (Exception $ex) {
                    }
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_get_stats->close();
                // if nothing inserted and transaction still open, rollback to release locks
                try {
                    if ($c->connect_errno === 0 && $c->in_transaction) $c->rollback();
                } catch (Exception $ex) {
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
