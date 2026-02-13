<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
global $localhost_db, $username_db, $password_db, $name_db;
header("Content-Type:application/json");
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $post = json_decode(file_get_contents('php://input'), true);
} else {
    $post = $_POST;
}
$get = $_GET; //GET request

$condition = true;
if ($condition) {
    $response = null;

    //Using prepared statements -> it's the safest way for MySQL queries
    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $emotions_table, $emotions_followed_table, $logins_table, $users_table, $otps_table;

        $emotion_id = null;
        if (isset($get["emotion-id"]) && checkNumberValidity($get["emotion-id"])) $emotion_id = $get["emotion-id"];

        // optional login-id param: detect presence separately from resolution result
        $login_id = null;
        $login_id_provided = false;
        // accept multiple common parameter names for robustness and search recursively in POST JSON
        $possible_keys = array("login-id", "loginid", "loginId");

        // if $post is null or empty but raw input may contain JSON, try decode raw input again
        if ((is_null($post) || $post === []) && !empty($contentType)) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && trim($raw) !== '') {
                $maybe = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($maybe)) {
                    $post = $maybe;
                }
            }
        }

        // recursive search helper
        $findInArray = function ($arr, $keys) use (&$findInArray) {
            if (!is_array($arr)) return null;
            foreach ($keys as $k) {
                if (array_key_exists($k, $arr) && checkFieldValidity($arr[$k])) return $arr[$k];
            }
            // search nested arrays
            foreach ($arr as $v) {
                if (is_array($v)) {
                    $res = $findInArray($v, $keys);
                    if ($res !== null) return $res;
                }
            }
            return null;
        };

        // search POST (decoded JSON / form) first
        $found = $findInArray($post, $possible_keys);
        if ($found !== null) {
            $login_id = (string)$found;
            $login_id_provided = true;
        } else {
            // fallback to GET params
            foreach ($possible_keys as $k) {
                if (isset($get[$k]) && checkFieldValidity($get[$k])) {
                    $login_id = (string)$get[$k];
                    $login_id_provided = true;
                    break;
                }
            }
        }

        // resolve login-id to user-id (non-fatal)
        $user_id = null;
        if ($login_id_provided && $login_id !== null) {
            $query_get_user_id = "SELECT `users`.`user-id` AS `user-id` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
            $st_uid = $c->prepare($query_get_user_id);
            if ($st_uid) {
                $st_uid->bind_param("s", $login_id);
                try {
                    $st_uid->execute();
                    $res_uid = $st_uid->get_result();
                    if ($res_uid && $res_uid->num_rows === 1) {
                        $user_id = $res_uid->fetch_assoc()['user-id'];
                    }
                } catch (mysqli_sql_exception $e) {
                    // non-fatal: treat as no user
                    $user_id = null;
                }
                $st_uid->close();
            }
        }

        $stmt = '';
        if ($emotion_id != null) {
            $stmt = $c->prepare("SELECT `emotion-id`, `it` FROM $emotions_table WHERE `emotion-id` = ?");
            $stmt->bind_param("s", $emotion_id);
        } else {
            $stmt = $c->prepare("SELECT `emotion-id`, `it` FROM $emotions_table");
        }

        try {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $rows = array();

                // load all followed emotion-ids for this user (if available) into a map for fast checks
                $followed_map = null;
                if ($login_id_provided) {
                    if ($user_id !== null && !empty($emotions_followed_table)) {
                        try {
                            $q_followed = "SELECT `emotion-id` FROM $emotions_followed_table WHERE `user-id` = ?";
                            $st_followed = $c->prepare($q_followed);
                            if ($st_followed) {
                                $st_followed->bind_param("s", $user_id);
                                $st_followed->execute();
                                $res_followed = $st_followed->get_result();
                                $followed_map = array();
                                while ($fr = $res_followed->fetch_assoc()) {
                                    if (isset($fr['emotion-id'])) $followed_map[$fr['emotion-id']] = true;
                                }
                                $st_followed->close();
                            }
                        } catch (mysqli_sql_exception $e) {
                            $followed_map = array(); // treat as empty: login provided but error -> default to not followed
                        }
                    } else {
                        // login provided but couldn't resolve to a user: mark as not followed (empty map)
                        $followed_map = array();
                    }
                } else {
                    // login not provided: keep followed_map=null so is-followed stays null
                    $followed_map = null;
                }

                while ($row = $result->fetch_assoc()) {
                    if (isset($row["emotion-id"]) && isset($row["it"])) {
                        // default is-followed value
                        $is_followed = null;
                        if ($followed_map !== null) {
                            $check_emotion_id = $row["emotion-id"];
                            $is_followed = isset($followed_map[$check_emotion_id]) ? true : false;
                        }

                        // build output row: map `it` -> `emotion-text` and include is-followed
                        $out_row = $row; // copy
                        $out_row["emotion-text"] = $row["it"];
                        unset($out_row["it"]);
                        $out_row["is-followed"] = $is_followed;

                        $rows[] = $out_row;
                    }
                }


                responseSuccess(200, null, array_values($rows));
            } else {
                responseError(404, "No emotions found");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt->close();

        $c->close();
    } else {
        responseError(500);
    }
} else {
    responseError(400);
}

?>