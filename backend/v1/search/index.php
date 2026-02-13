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
        global $users_followed_table, $emotions_followed_table, $emotions_table;

        $login_id = null;
        if (isset($post["login-id"]) && checkFieldValidity($post["login-id"])) {
            $login_id = $post["login-id"];
        }
        $user_id = null;

        $filter_by_user = true;
        if (isset($get["user"]) && checkFieldValidity($get["user"]) && $get["user"] === 'false') {
            $filter_by_user = false;
        }
        $filter_by_emotion = true;
        if (isset($get["emotion"]) && checkFieldValidity($get["emotion"]) && $get["emotion"] === 'false') {
            $filter_by_emotion = false;
        }

        // pagination
        $offset = 0;
        if (isset($get["offset"]) && checkNumberValidity($get["offset"])) $offset = max(0, intval($get["offset"]));

        $limit = 50;
        if (isset($get["limit"]) && checkNumberValidity($get["limit"])) $limit = min(intval($get["limit"]), $limit); //cap limit to max 50
        $limit = max(1, $limit); //ensure limit is at least 1

        $rawSearch = isset($get["q"]) ? trim($get["q"]) : '';
        $searchLike = '%' . $rawSearch . '%';
        // For username search use the raw search term and rely on COLLATE for case-insensitive matching
        $searchLikeUser = '%' . $rawSearch . '%';

        // optional language GET param for emotion search (two-letter code)
        $language = 'it';
        if (isset($get["language"]) && is_string($get["language"])) {
            if (preg_match('/^[a-z]{2}$/i', $get["language"])) {
                $language = strtolower($get["language"]);
            }
        }

        $action = null;

        if ($login_id !== null) {
            $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
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

            // SEARCH IMPLEMENTATION
            $rows = array();

            // Determine what to search: users, emotions, or both
            $search_users = $filter_by_user || (!$filter_by_user && !$filter_by_emotion);
            $search_emotions = $filter_by_emotion || (!$filter_by_user && !$filter_by_emotion);

            $users_found = array(); // array of ['user-id' => ..., 'username' => ...]
            $emotions_found = array(); // array of ['emotion-id' => ..., 'it' => ...]

            if ($search_users) {
                // include profile-image so we can return avatar for user results
                // interpolate limit/offset as integers (safer across MySQL setups)
                $limit_int = intval($limit);
                $offset_int = intval($offset);
                // use COLLATE on the username column to make LIKE case/locale-insensitive and match partial usernames
                $query_users = "SELECT `user-id`, `username`, `profile-image` FROM $users_table WHERE `username` COLLATE utf8mb4_unicode_ci LIKE ? AND `status` = 1 ORDER BY `username` COLLATE utf8mb4_unicode_ci ASC LIMIT $limit_int OFFSET $offset_int";
                $stmt_users = $c->prepare($query_users);
                $stmt_users->bind_param("s", $searchLikeUser);

                try {
                    $stmt_users->execute();
                    $res_users = $stmt_users->get_result();
                    while ($r = $res_users->fetch_assoc()) {
                        $users_found[] = $r;
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_users->close();

                // If logged, exclude the user's own profile from results
                if ($user_id !== null && count($users_found) > 0) {
                    $users_found = array_values(array_filter($users_found, function ($u) use ($user_id) {
                        return strval($u['user-id']) !== strval($user_id);
                    }));
                }
            }

            if ($search_emotions) {
                // decide which column to use for emotion text (default 'it')
                $lang_col = 'it';
                if ($language !== null) $lang_col = $language; // safe since validated as two letters
                $limit_int = intval($limit);
                $offset_int = intval($offset);
                $query_emotions = "SELECT `emotion-id`, `$lang_col` AS `it` FROM $emotions_table WHERE `$lang_col` COLLATE utf8mb4_unicode_ci LIKE ? ORDER BY `$lang_col` COLLATE utf8mb4_unicode_ci ASC LIMIT $limit_int OFFSET $offset_int";
                $stmt_emotions = $c->prepare($query_emotions);
                $stmt_emotions->bind_param("s", $searchLikeUser);

                try {
                    $stmt_emotions->execute();
                    $res_emotions = $stmt_emotions->get_result();
                    while ($r = $res_emotions->fetch_assoc()) {
                        $emotions_found[] = $r;
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_emotions->close();
            }

            // Prepare results and check follow status if logged
            if ($user_id !== null) {
                // check users followed
                foreach ($users_found as $u) {
                    $is_following = false;
                    try {
                        $query_is_following = "SELECT 1 FROM $users_followed_table WHERE `user-id` = ? AND `followed-user-id` = ? LIMIT 1";
                        $stmt_is_following = $c->prepare($query_is_following);
                        $stmt_is_following->bind_param("ss", $user_id, $u['user-id']);
                        $stmt_is_following->execute();
                        $res_if = $stmt_is_following->get_result();
                        $is_following = ($res_if->num_rows === 1);
                        $stmt_is_following->close();
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }

                    $rows[] = array(
                        "type" => "user",
                        "id" => isset($u['user-id']) ? $u['user-id'] : null,
                        "text" => isset($u['username']) ? $u['username'] : null,
                        // return avatar as profile-image if present, otherwise null
                        "avatar" => isset($u['profile-image']) ? $u['profile-image'] : null,
                        "followed" => $is_following
                    );
                }

                // check emotions followed
                foreach ($emotions_found as $em) {
                    $is_following = false;
                    try {
                        $query_is_following_em = "SELECT 1 FROM $emotions_followed_table WHERE `user-id` = ? AND `emotion-id` = ? LIMIT 1";
                        $stmt_is_following_em = $c->prepare($query_is_following_em);
                        $stmt_is_following_em->bind_param("ss", $user_id, $em['emotion-id']);
                        $stmt_is_following_em->execute();
                        $res_ief = $stmt_is_following_em->get_result();
                        $is_following = ($res_ief->num_rows === 1);
                        $stmt_is_following_em->close();
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }

                    $rows[] = array(
                        "type" => "emotion",
                        "id" => isset($em['emotion-id']) ? $em['emotion-id'] : null,
                        "text" => isset($em['it']) ? $em['it'] : null,
                        // emotions don't have avatars in this context
                        "avatar" => null,
                        "followed" => $is_following
                    );
                }
            } else {
                // not logged -> followed is null
                foreach ($users_found as $u) {
                    $rows[] = array(
                        "type" => "user",
                        "id" => isset($u['user-id']) ? $u['user-id'] : null,
                        "text" => isset($u['username']) ? $u['username'] : null,
                        "avatar" => isset($u['profile-image']) ? $u['profile-image'] : null,
                        "followed" => null
                    );
                }
                foreach ($emotions_found as $em) {
                    $rows[] = array(
                        "type" => "emotion",
                        "id" => isset($em['emotion-id']) ? $em['emotion-id'] : null,
                        "text" => isset($em['it']) ? $em['it'] : null,
                        "avatar" => null,
                        "followed" => null
                    );
                }
            }

            // Return results (200 with array, possibly empty)
            responseSuccess(200, null, array_values($rows));

        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        if (isset($stmt_get_user_id)) $stmt_get_user_id->close();

        $c->close();
    } else {
        responseError(500);
    }
} else {
    //bad request: missing parameters
    $missing_parameters = array();
    if (!isset($get["q"]) || !checkFieldValidity($get["q"])) {
        array_push($missing_parameters, "q");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>