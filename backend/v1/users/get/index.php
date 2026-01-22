<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
global $localhost_db, $username_db, $password_db, $name_db;
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $post = json_decode(file_get_contents('php://input'), true);
} else {
    $post = $_POST;
}
$get = $_GET; //GET request

$condition = isset($get["username"]) && checkUsernameValidity($get["username"]);
if ($condition) {
    $response = null;

    //Using prepared statements -> it's the safest way for MySQL queries
    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $posts, $emotions_followed_table, $users_followed_table;
        $username = strtolower(trim($get["username"]));
        $login_id = null;
        if (isset($post["login-id"]) && checkFieldValidity($post["login-id"])) {
            $login_id = $post["login-id"];
        }

        $login_in_user_id = null;
        $username_user_id = null;

        if ($login_id !== null) {
            $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
            $stmt_get_user_id = $c->prepare($query_get_user_id);
            $stmt_get_user_id->bind_param("s", $login_id);

            try {
                $stmt_get_user_id->execute();
                $result = $stmt_get_user_id->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();

                    $login_in_user_id = $row["user-id"];
                } else {
                    // login-id not found or expired -> treat as anonymous (do not abort)
                    $login_in_user_id = null;
                }
            } catch (mysqli_sql_exception $e) {
                responseError(500, "Database error: " . $e->getMessage());
            }
            $stmt_get_user_id->close();
        }

        // get user-id from username (only active users)
        $query_get_by_username = "SELECT `user-id`, `username`, `profile-image`, `bio` FROM $users_table WHERE `username` = ? AND `status` = 1";
        $stmt_get_by_username = $c->prepare($query_get_by_username);
        $stmt_get_by_username->bind_param("s", $username);

        try {
            $stmt_get_by_username->execute();
            $result_user = $stmt_get_by_username->get_result();

            if ($result_user->num_rows !== 1) {
                responseError(404, "User not found.");
            }

            $row_user = $result_user->fetch_assoc();
            $username_user_id = $row_user["user-id"];
            $profile_image = isset($row_user["profile-image"]) ? $row_user["profile-image"] : null;
            $bio = isset($row_user["bio"]) ? $row_user["bio"] : null;
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_by_username->close();

        // if it's the own profile (login provided and refers to same user)
        if ($login_in_user_id !== null && strval($login_in_user_id) === strval($username_user_id)) {
            // number of followers (others following this user)
            $followers_count = 0;
            $query_followers = "SELECT COUNT(*) AS cnt FROM $users_followed_table WHERE `followed-user-id` = ?";
            $stmt_followers = $c->prepare($query_followers);
            $stmt_followers->bind_param("s", $username_user_id);
            try {
                $stmt_followers->execute();
                $res_f = $stmt_followers->get_result();
                if ($row = $res_f->fetch_assoc()) $followers_count = (int)$row['cnt'];
            } catch (mysqli_sql_exception $e) {
                responseError(500, "Database error: " . $e->getMessage());
            }
            $stmt_followers->close();

            // number of followings (this user following others)
            $following_count = 0;
            $query_following = "SELECT COUNT(*) AS cnt FROM $users_followed_table WHERE `user-id` = ?";
            $stmt_following = $c->prepare($query_following);
            $stmt_following->bind_param("s", $username_user_id);
            try {
                $stmt_following->execute();
                $res_fg = $stmt_following->get_result();
                if ($row = $res_fg->fetch_assoc()) $following_count = (int)$row['cnt'];
            } catch (mysqli_sql_exception $e) {
                responseError(500, "Database error: " . $e->getMessage());
            }
            $stmt_following->close();

            // number of emotions followed
            $emotions_followed_count = 0;
            $query_emotions_followed = "SELECT COUNT(*) AS cnt FROM $emotions_followed_table WHERE `user-id` = ?";
            $stmt_emotions_followed = $c->prepare($query_emotions_followed);
            $stmt_emotions_followed->bind_param("s", $username_user_id);
            try {
                $stmt_emotions_followed->execute();
                $res_ef = $stmt_emotions_followed->get_result();
                if ($row = $res_ef->fetch_assoc()) $emotions_followed_count = (int)$row['cnt'];
            } catch (mysqli_sql_exception $e) {
                responseError(500, "Database error: " . $e->getMessage());
            }
            $stmt_emotions_followed->close();

            $data = array(
                "username" => $username,
                "profile-image" => $profile_image,
                "bio" => $bio,
                "followers-count" => $followers_count,
                "following-count" => $following_count,
                "emotions-followed-count" => $emotions_followed_count
            );

            responseSuccess(200, null, $data);
        } else {
            // other profile or not logged in: return public data and is-following if login provided
            $is_following = false;
            if ($login_in_user_id !== null) {
                $query_is_following = "SELECT 1 FROM $users_followed_table WHERE `user-id` = ? AND `followed-user-id` = ? LIMIT 1";
                $stmt_is_following = $c->prepare($query_is_following);
                $stmt_is_following->bind_param("ss", $login_in_user_id, $username_user_id);
                try {
                    $stmt_is_following->execute();
                    $res_if = $stmt_is_following->get_result();
                    $is_following = ($res_if->num_rows === 1);
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_is_following->close();
            }

            $data = array(
                "username" => $username,
                "profile-image" => $profile_image,
                "bio" => $bio,
                "is-following" => $is_following
            );

            responseSuccess(200, null, $data);
        }

        $c->close();
    } else {
        responseError(500);
    }
} else {
    //bad request: missing parameters
    $missing_parameters = array();
    if (!isset($get["username"]) || !checkUsernameValidity($get["username"])) {
        array_push($missing_parameters, "username");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>