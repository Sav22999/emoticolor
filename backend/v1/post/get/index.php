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

//use the following code of example for AUTHENTICATED requests
$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $posts_table, $emotions_table, $emotions_followed_table, $users_followed_table, $reactions_table, $reactions_posts_table;

        $login_id = $post["login-id"];
        $username = null;
        if (isset($post["username"]) && checkUsernameValidity($post["username"])) {
            $username = strtolower(trim($post["username"]));
        }
        $post_id = null;
        if (isset($post["post-id"]) && checkFieldValidity($post["post-id"])) {
            $post_id = $post["post-id"];
        }
        $offset = 0;
        if (isset($get["offset"]) && checkNumberValidity($get["offset"])) $offset = intval($get["offset"]);

        $limit = 50;
        if (isset($get["limit"]) && checkNumberValidity($get["limit"])) $limit = min(intval($get["limit"]), $limit); //cap limit to max 50
        $limit = max(1, $limit); //ensure limit is at least 1


        $user_id = null;

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`language` AS `language` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];
                $language = $row["language"];

                // determine target user if username provided
                $target_user_id = null;
                if ($username !== null) {
                    $query_get_target = "SELECT `user-id` FROM $users_table WHERE `username` = ? AND `status` = 1";
                    $stmt_get_target = $c->prepare($query_get_target);
                    if ($stmt_get_target === false) {
                        throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                    }
                    $stmt_get_target->bind_param("s", $username);
                    try {
                        $stmt_get_target->execute();
                        $res_target = $stmt_get_target->get_result();
                        if ($res_target->num_rows === 1) {
                            $r = $res_target->fetch_assoc();
                            $target_user_id = $r['user-id'];
                        } else {
                            $stmt_get_target->close();
                            responseError(404, "User not found.");
                        }
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_get_target->close();
                }

                // Build and execute the proper posts query
                $posts = array();

                try {
                    if ($post_id !== null) {
                        // single post: must be visible (visibility=0) OR own post
                        $query_get_post = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `post-id` = ? AND (`visibility` = 0 OR `user-id` = ?)) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id`";
                        $stmt_get_posts = $c->prepare($query_get_post);
                        if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                        $stmt_get_posts->bind_param("ssss", $user_id, $post_id, $user_id, $user_id);
                    } else if ($target_user_id !== null) {
                        // posts of a specific user
                        if ($target_user_id === $user_id) {
                            // own posts: include all visibilities
                            $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, 1 AS `is-own-post`, 0 AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `user-id` = ?) AS `posts` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` ORDER BY `posts`.`created` DESC LIMIT ? OFFSET ?";
                            $stmt_get_posts = $c->prepare($query_get_posts);
                            if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            // bind: user-id, limit, offset
                            $stmt_get_posts->bind_param("sii", $user_id, $limit, $offset);
                        } else {
                            // other user's posts: only visible ones
                            $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `user-id` = ? AND `visibility` = 0) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` ORDER BY `posts`.`created` DESC LIMIT ? OFFSET ?";
                            $stmt_get_posts = $c->prepare($query_get_posts);
                            if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            // bind: user_id (for CASE), target_user_id (filter), user_id (for uf), limit, offset
                            $stmt_get_posts->bind_param("sssii", $user_id, $target_user_id, $user_id, $limit, $offset);
                        }
                    } else {
                        // feed: posts from followed users or followed emotions, only visible, same language
                        $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `visibility` = 0 AND `language` = ?) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` WHERE (`posts`.`user-id` IN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) OR `posts`.`emotion-id` IN (SELECT `emotion-id` FROM $emotions_followed_table WHERE `user-id` = ?)) ORDER BY `posts`.`created` DESC LIMIT ? OFFSET ?";
                        $stmt_get_posts = $c->prepare($query_get_posts);
                        if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                        // bind: user_id, language, user_id, user_id, user_id, limit, offset
                        $types = 'sssssii';
                        $params = array($user_id, $language, $user_id, $user_id, $user_id, $limit, $offset);
                        $bind_names = array();
                        $bind_names[] = &$types;
                        for ($i = 0; $i < count($params); $i++) {
                            $bind_names[] = &$params[$i];
                        }
                        call_user_func_array(array($stmt_get_posts, 'bind_param'), $bind_names);
                    }

                    // execute and fetch posts
                    if (!$stmt_get_posts->execute()) {
                        $err = $stmt_get_posts->error;
                        $stmt_get_posts->close();
                        responseError(500, "Database execute error: " . $err);
                    }

                    $result_posts = $stmt_get_posts->get_result();
                    if ($result_posts->num_rows === 0) {
                        $stmt_get_posts->close();
                        // return empty list for consistency with other list endpoints
                        responseSuccess(200, null, array());
                    }

                    $get_posts = array();
                    while ($row_post = $result_posts->fetch_assoc()) {
                        // normalize and add flags
                        $row_post['visibility'] = (int)$row_post['visibility'];
                        $row_post['is-own-post'] = ($row_post['is-own-post'] == 1);
                        $row_post['is-user-followed'] = ($row_post['is-user-followed'] == 1);

                        // ensure username and profile-image are present and remove user-id
                        $row_post['username'] = isset($row_post['username']) ? $row_post['username'] : null;
                        $row_post['profile-image'] = isset($row_post['profile-image']) ? $row_post['profile-image'] : null;
                        if (isset($row_post['user-id'])) unset($row_post['user-id']);

                        // reactions handling
                        if ($row_post['is-own-post']) {
                            // own post: only include reactions counts if visibility == 0
                            if ($row_post['visibility'] === 0) {
                                $query_reactions_count = "SELECT `reaction-id`, COUNT(*) AS `count` FROM $reactions_posts_table WHERE `post-id` = ? GROUP BY `reaction-id`";
                                $stmt_reactions = $c->prepare($query_reactions_count);
                                if ($stmt_reactions === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                                $stmt_reactions->bind_param("s", $row_post['post-id']);
                                try {
                                    $stmt_reactions->execute();
                                    $res_reactions = $stmt_reactions->get_result();
                                    $reactions = array();
                                    while ($r = $res_reactions->fetch_assoc()) {
                                        // cast count to int
                                        $r['count'] = (int)$r['count'];
                                        array_push($reactions, $r);
                                    }
                                    $row_post['reactions'] = $reactions;
                                } catch (mysqli_sql_exception $e) {
                                    responseError(500, "Database error: " . $e->getMessage());
                                }
                                $stmt_reactions->close();
                            } else {
                                $row_post['reactions'] = array();
                            }
                        } else {
                            // other user's post: return reactions inserted by login-id's user (if any)
                            $query_user_reactions = "SELECT `reaction-id` FROM $reactions_posts_table WHERE `post-id` = ? AND `user-id` = ?";
                            $stmt_user_reactions = $c->prepare($query_user_reactions);
                            if ($stmt_user_reactions === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            $stmt_user_reactions->bind_param("ss", $row_post['post-id'], $user_id);
                            try {
                                $stmt_user_reactions->execute();
                                $res_user_reactions = $stmt_user_reactions->get_result();
                                $user_reactions = array();
                                while ($ur = $res_user_reactions->fetch_assoc()) {
                                    array_push($user_reactions, $ur['reaction-id']);
                                }
                                $row_post['reactions'] = $user_reactions;
                            } catch (mysqli_sql_exception $e) {
                                responseError(500, "Database error: " . $e->getMessage());
                            }
                            $stmt_user_reactions->close();
                        }

                        array_push($get_posts, $row_post);
                    }

                    $stmt_get_posts->close();

                    responseSuccess(200, null, $get_posts);

                } catch (mysqli_sql_exception $e) {
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
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>