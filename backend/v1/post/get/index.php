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
$get = $_GET; //GET request (unused - do not rely on this)


// Make login-id optional: if provided and valid try to authenticate, otherwise proceed as anonymous
$has_login = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
$response = null;

if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
    $c->set_charset("utf8mb4");

    global $logins_table, $users_table, $otps_table;
    global $posts_table, $emotions_table, $emotions_followed_table, $users_followed_table, $reactions_table, $reactions_posts_table;
    // tables used for localized linked entities and icons
    global $icons_table, $weather_table, $places_table, $together_with_table, $body_parts_table, $colors_table, $images_table;

    // Prepare commonly used input variables
    $login_id = $has_login ? $post["login-id"] : null;
    $username = null;
    if (isset($post["username"]) && checkUsernameValidity($post["username"])) {
        $username = strtolower(trim($post["username"]));
    }
    $post_id = null;
    if (isset($post["post-id"]) && checkFieldValidity($post["post-id"])) {
        $post_id = $post["post-id"];
    }
    $offset = 0;
    // accept offset only from POST
    if (isset($post["offset"]) && checkNumberValidity($post["offset"])) {
        $offset = max(0, intval($post["offset"]));
    }

    $limit = 50;
    // accept limit only from POST (cap to max 50)
    if (isset($post["limit"]) && checkNumberValidity($post["limit"])) $limit = min(intval($post["limit"]), $limit);
    $limit = max(1, $limit); //ensure limit is at least 1


    $user_id = null; // will remain null for anonymous
    $language = 'it'; // default language

    // If login-id present, try to resolve user. If invalid or expired, treat caller as anonymous (do not fail)
    if ($has_login) {
        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`language` AS `language` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        if ($stmt_get_user_id !== false) {
            $stmt_get_user_id->bind_param("s", $login_id);
            try {
                $stmt_get_user_id->execute();
                $result = $stmt_get_user_id->get_result();
                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    $user_id = $row["user-id"];
                    $language = $row["language"];
                } else {
                    // invalid login -> keep $user_id = null and continue as anonymous
                }
            } catch (mysqli_sql_exception $e) {
                responseError(500, "Database error: " . $e->getMessage());
            }
            $stmt_get_user_id->close();
        }
    }

    // If caller is anonymous and requested a single post, allow caller to provide a preferred language (two-letter) alongside the request
    if ($user_id === null && $post_id !== null && isset($post['language']) && preg_match('/^[a-z]{2}$/i', $post['language'])) {
        $language = strtolower($post['language']);
    }

    // determine target user if username provided or my-profile flag set
    $target_user_id = null;
    $use_my_profile = false;
    if (isset($post['my-profile'])) {
        $use_my_profile = filter_var($post['my-profile'], FILTER_VALIDATE_BOOLEAN);
    }
    // only allow my-profile to target current user when caller is logged
    if ($use_my_profile && $user_id !== null) {
        $target_user_id = $user_id;
    } else if ($username !== null) {
        // check username existence (works for both logged and anonymous callers)
        $query_check_user = "SELECT `user-id` FROM $users_table WHERE `username` = ? AND `status` = 1 LIMIT 1";
        $stmt_check_user = $c->prepare($query_check_user);
        if ($stmt_check_user === false) {
            responseError(500, "Database error: " . $c->error);
        }
        $stmt_check_user->bind_param("s", $username);
        try {
            $stmt_check_user->execute();
            $res_check_user = $stmt_check_user->get_result();
            if ($res_check_user->num_rows === 1) {
                $r = $res_check_user->fetch_assoc();
                $target_user_id = $r['user-id'];
            } else {
                $stmt_check_user->close();
                responseError(404, "User not found.");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_check_user->close();
    }

    // Build and execute the proper posts query
    $posts = array();

    try {
        // Build clearer, branch-specific queries that match original intent and ensure correct placeholders/binds

        if ($post_id !== null) {
            // Single post - if a username was provided, ensure we only return posts by that username
            if (isset($target_user_id) && $target_user_id !== null) {
                if ($target_user_id === $user_id && $user_id !== null) {
                    // requesting own single post: allow all visibilities
                    $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, 1 AS `is-own-post`, 0 AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `post-id` = ? AND `user-id` = ?) AS `posts` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` WHERE `u`.`status` = 1";
                    $stmt_get_posts = $c->prepare($query_get_posts);
                    if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                    $stmt_get_posts->bind_param("ss", $post_id, $target_user_id);
                } else {
                    // requesting another user's single post: only public posts
                    $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `post-id` = ? AND `user-id` = ? AND `visibility` = 0) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` WHERE `u`.`status` = 1";
                    $stmt_get_posts = $c->prepare($query_get_posts);
                    if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                    // bind order: CASE WHEN (current user), post-id, target_user_id, uf subselect user-id
                    // if anonymous, $user_id will be null which simply yields no follows
                    $stmt_get_posts->bind_param("ssss", $user_id, $post_id, $target_user_id, $user_id);
                }
            } else {
                // No username provided: keep previous single-post behavior (private allowed if owner)
                $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `post-id` = ? AND (`visibility` = 0 OR `user-id` = ?)) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` WHERE `u`.`status` = 1";
                $stmt_get_posts = $c->prepare($query_get_posts);
                if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                // bind order: CASE WHEN (current user), post-id, owner-check (current user), uf subselect user-id
                $stmt_get_posts->bind_param("ssss", $user_id, $post_id, $user_id, $user_id);
            }

        } else if ($target_user_id !== null) {
            if ($target_user_id === $user_id && $user_id !== null) {
                // Own posts (all visibilities). Use explicit SELECT (no uf)
                $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, 1 AS `is-own-post`, 0 AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `user-id` = ?) AS `posts` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` WHERE `u`.`status` = 1 ORDER BY `posts`.`created` DESC";
                $query_get_posts .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
                $stmt_get_posts = $c->prepare($query_get_posts);
                if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                $stmt_get_posts->bind_param("s", $target_user_id);

            } else {
                // Other user's public posts only
                $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `user-id` = ? AND `visibility` = 0) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` WHERE `u`.`status` = 1 ORDER BY `posts`.`created` DESC";
                $query_get_posts .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
                $stmt_get_posts = $c->prepare($query_get_posts);
                if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                // bind order: CASE WHEN (current user), target_user_id, current user (for uf)
                $stmt_get_posts->bind_param("sss", $user_id, $target_user_id, $user_id);
            }

        } else {
            // Feed: build follow lists first and construct a safe IN(...) query
            $followed_user_ids = array();
            $followed_emotion_ids = array();

            if ($user_id !== null) {
                // fetch followed users
                $q_fu = "SELECT `followed-user-id` FROM " . $users_followed_table . " WHERE `user-id` = ?";
                $st_fu = $c->prepare($q_fu);
                if ($st_fu !== false) {
                    $st_fu->bind_param("s", $user_id);
                    try {
                        $st_fu->execute();
                        $res_fu = $st_fu->get_result();
                        while ($r = $res_fu->fetch_assoc()) {
                            if (!empty($r['followed-user-id'])) $followed_user_ids[] = $r['followed-user-id'];
                        }
                    } catch (mysqli_sql_exception $e) {
                        // ignore and leave empty
                    }
                    $st_fu->close();
                }

                // fetch followed emotions
                $q_fe = "SELECT `emotion-id` FROM " . $emotions_followed_table . " WHERE `user-id` = ?";
                $st_fe = $c->prepare($q_fe);
                if ($st_fe !== false) {
                    $st_fe->bind_param("s", $user_id);
                    try {
                        $st_fe->execute();
                        $res_fe = $st_fe->get_result();
                        while ($r = $res_fe->fetch_assoc()) {
                            if (!empty($r['emotion-id'])) $followed_emotion_ids[] = $r['emotion-id'];
                        }
                    } catch (mysqli_sql_exception $e) {
                        // ignore
                    }
                    $st_fe->close();
                }
            }

            // base select (no CASE columns here; we'll compute follow flags later in PHP)
            $select_base = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url` FROM $posts_table AS `posts` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id`";

            // build WHERE clauses
            $where = "WHERE `u`.`status` = 1";
            $bind_types = '';
            $bind_values = array();

            // track whether the public-posts clause was appended (so we can close the grouping reliably)
            $public_clause_added = false;

            // If caller is logged, include own posts in feed; otherwise only public posts matching follows/emotions
            if ($user_id !== null) {
                $where .= " AND (`posts`.`user-id` = ?"; // own posts
                $bind_types = 's';
                $bind_values = array($user_id);
            } else {
                $where .= " AND ("; // start a grouping for public-only clauses
            }

            // public posts clause: include public posts that are from followed users OR have an emotion followed by the user
            if (count($followed_user_ids) > 0 || count($followed_emotion_ids) > 0 || $user_id === null) {
                $public_clause_added = true;
                // when anonymous ($user_id === null) we still want public posts (but not own posts)
                if ($user_id !== null) {
                    $where .= " OR ( `posts`.`visibility` = 0 AND (";
                } else {
                    $where .= " `posts`.`visibility` = 0 AND (";
                }

                $parts = array();
                if (count($followed_user_ids) > 0) {
                    $ph = implode(',', array_fill(0, count($followed_user_ids), '?'));
                    $parts[] = "`posts`.`user-id` IN ($ph)";
                    $bind_types .= str_repeat('s', count($followed_user_ids));
                    foreach ($followed_user_ids as $fu) $bind_values[] = $fu;
                }

                // Always add EXISTS check for emotions followed by the current user when logged; when anonymous skip the EXISTS user check and keep a generic true condition to include public posts
                if ($user_id !== null) {
                    $parts[] = "EXISTS (SELECT 1 FROM $emotions_followed_table ef WHERE ef.`user-id` = ? AND ef.`emotion-id` = `posts`.`emotion-id`)";
                    $bind_types .= 's';
                    $bind_values[] = $user_id;
                } else {
                    // anonymous: include public posts regardless of emotion follows
                    $parts[] = "1=1";
                }

                if (count($followed_emotion_ids) > 0) {
                    $ph2 = implode(',', array_fill(0, count($followed_emotion_ids), '?'));
                    $parts[] = "`posts`.`emotion-id` IN ($ph2)";
                    $bind_types .= str_repeat('s', count($followed_emotion_ids));
                    foreach ($followed_emotion_ids as $fe) $bind_values[] = $fe;
                }

                $where .= implode(' OR ', $parts);
                $where .= ") )"; // close visibility and OR
                // close the initial grouping started earlier (for own posts OR public posts)
                // only when caller is logged-in: anonymous branch already closed the grouping with ") )"
                if ($user_id !== null) {
                    $where .= ")";
                }
            }

            // If we didn't add the public posts clause above we still need to close the opening parenthesis
            if (!$public_clause_added) {
                $where .= ")"; // close grouping started for own posts / public OR
            }

            // Sanitize WHERE clause: trim stray AND/OR and ensure parentheses are balanced
            $where = rtrim($where);
            // remove trailing AND/OR if present
            $where = preg_replace('/(AND|OR)\s*$/i', '', $where);
            // auto-balance parentheses: if there are more '(' than ')', close them
            $open_parens = substr_count($where, '(') - substr_count($where, ')');
            while ($open_parens > 0) { $where .= ')'; $open_parens--; }

            $order_limit = " ORDER BY `posts`.`created` DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

            $query_get_posts = $select_base . " " . $where . $order_limit;

            $stmt_get_posts = $c->prepare($query_get_posts);
            if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);

            // bind parameters dynamically
            if ($bind_types !== '') {
                $bind_names = array();
                $bind_names[] = &$bind_types;
                for ($i = 0; $i < count($bind_values); $i++) {
                    $bind_names[] = &$bind_values[$i];
                }
                call_user_func_array(array($stmt_get_posts, 'bind_param'), $bind_names);
            }
        }


        // execute and fetch posts
        // optional debug headers: set when {"debug":1} (POST) to inspect final SQL and pagination values
        $debug_mode = (isset($post['debug']) && $post['debug']);
        if ($debug_mode) {
            // $query_get_posts is used for list/feed/target-user branches
            // $query_get_post is used for single post branch – pick whichever exists
            $final_query = isset($query_get_posts) ? $query_get_posts : (isset($query_get_post) ? $query_get_post : '');
            header('X-Debug-SQL: ' . base64_encode($final_query));
            header('X-Debug-Limit: ' . intval($limit));
            header('X-Debug-Offset: ' . intval($offset));
            // expose lightweight debug binds (user id + language) to help troubleshoot filtering
            header('X-Debug-User: ' . (isset($user_id) ? $user_id : ''));
            header('X-Debug-Language: ' . (isset($language) ? $language : ''));
        }
        if (!$stmt_get_posts->execute()) {
            $err = $stmt_get_posts->error;
            $stmt_get_posts->close();
            responseError(500, "Database execute error: " . $err);
        }

        $result_posts = $stmt_get_posts->get_result();
        if ($result_posts->num_rows === 0) {
            $stmt_get_posts->close();
            // return empty list for consistency with other list endpoints
            responseSuccess(201, null, array());
        }

        // Fetch all rows first so we can batch-query related emotion translations/icons
        $all_rows = $result_posts->fetch_all(MYSQLI_ASSOC);

        // Ensure followed lists exist (they may be defined only in feed branch)
        if (!isset($followed_user_ids) || !is_array($followed_user_ids)) $followed_user_ids = array();
        if (!isset($followed_emotion_ids) || !is_array($followed_emotion_ids)) $followed_emotion_ids = array();

        // Build quick lookup maps for flags
        $followed_user_map = array();
        foreach ($followed_user_ids as $fu) $followed_user_map[$fu] = true;
        $followed_emotion_map = array();
        foreach ($followed_emotion_ids as $fe) $followed_emotion_map[$fe] = true;

        // Precompute flags per row to ensure correct OR logic: own posts OR (public AND (user followed OR emotion followed))
        for ($i = 0; $i < count($all_rows); $i++) {
            $row = $all_rows[$i];

            if ($user_id === null) {
                $is_own = null; // anonymous callers: is-own-post is null
            } else {
                $is_own = (isset($row['user-id']) && $row['user-id'] === $user_id) ? 1 : 0;
            }

            $is_user_followed = (isset($row['user-id']) && isset($followed_user_map[$row['user-id']])) ? 1 : 0;
            $is_emotion_followed = (isset($row['emotion-id']) && isset($followed_emotion_map[$row['emotion-id']])) ? 1 : 0;

            $all_rows[$i]['is-own-post'] = $is_own;
            $all_rows[$i]['is-user-followed'] = $is_user_followed;
            // keep is-emotion-followed here too (will also be set later via bulk query for robustness)
            $all_rows[$i]['is-emotion-followed'] = $is_emotion_followed;
        }

        // sanitize language code and check if column exists in linked tables
        // fallback to 'it' if invalid or not present
        $lang = 'it';
        if (isset($language) && preg_match('/^[a-z]{2}$/', $language)) {
            $lang = $language;
        }

        // --- BUILD emotion translations and localized entity maps (weather/place/together-with/body-part) ---
        $emotion_map = array(); // emotion-id => ['text' => ...]
        $entity_maps = array(
            'weather' => array('map' => array(), 'icons' => array()),
            'place' => array('map' => array(), 'icons' => array()),
            'together-with' => array('map' => array(), 'icons' => array()),
            'body-part' => array('map' => array(), 'icons' => array()),
        );

        // Collect ids used in the fetched posts
        $emotion_ids = array();
        $weather_ids = array();
        $place_ids = array();
        $together_ids = array();
        $body_part_ids = array();
        foreach ($all_rows as $r) {
            if (!empty($r['emotion-id'])) $emotion_ids[] = $r['emotion-id'];
            if (!empty($r['weather-id'])) $weather_ids[] = $r['weather-id'];
            if (!empty($r['place-id'])) $place_ids[] = $r['place-id'];
            if (!empty($r['together-with-id'])) $together_ids[] = $r['together-with-id'];
            if (!empty($r['body-part-id'])) $body_part_ids[] = $r['body-part-id'];
        }
        $emotion_ids = array_values(array_unique($emotion_ids));
        $weather_ids = array_values(array_unique($weather_ids));
        $place_ids = array_values(array_unique($place_ids));
        $together_ids = array_values(array_unique($together_ids));
        $body_part_ids = array_values(array_unique($body_part_ids));

        // helper: find a textual column name for a table that best matches language (search column names containing lang code)
        $find_text_column = function($table, $preferLang = null) use ($c, $name_db, $lang) {
            $col = null;
            $candidates = array();
            try {
                $q = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                $st = $c->prepare($q);
                if ($st !== false) {
                    $st->bind_param("ss", $name_db, $table);
                    $st->execute();
                    $res = $st->get_result();
                    while ($rowc = $res->fetch_assoc()) {
                        $candidates[] = $rowc['COLUMN_NAME'];
                    }
                    $st->close();
                }
            } catch (mysqli_sql_exception $e) {
                // ignore and return null
            }

            if (count($candidates) === 0) return null;

            // Try preferred language (if provided) then fall back to $lang then 'it'
            $tryLangs = array();
            if ($preferLang !== null) $tryLangs[] = strtolower($preferLang);
            if (isset($lang)) $tryLangs[] = strtolower($lang);
            if (!in_array('it', $tryLangs)) $tryLangs[] = 'it';

            // search candidates for language-specific columns first for each tryLang
            foreach ($tryLangs as $tlang) {
                foreach ($candidates as $cn) {
                    $lcn = strtolower($cn);
                    if (preg_match('/[_-]' . preg_quote($tlang, '/') . '$/i', $lcn)) return $cn;
                }
                foreach ($candidates as $cn) {
                    $lcn = strtolower($cn);
                    if (strpos($lcn, $tlang) !== false) return $cn;
                }
            }

            // fallback to common textual column names
            $common = array('text', 'name', 'title', 'label');
            foreach ($common as $cm) {
                foreach ($candidates as $cn) {
                    if (strtolower($cn) === $cm) return $cn;
                }
            }
            // as a last resort return the first non-id column (not ending with '-id' or '_id')
            foreach ($candidates as $cn) {
                $lcn = strtolower($cn);
                if (!preg_match('/(_|-)?id$/i', $lcn)) return $cn;
            }
            return null;
        };

        // Build emotion map
        if (count($emotion_ids) > 0) {
            // try user's language first, then fallback to 'it'
            $text_col = $find_text_column($emotions_table, $lang);
            if ($text_col === null && $lang !== 'it') {
                $text_col = $find_text_column($emotions_table, 'it');
            }
            if ($text_col !== null) {
                 $ph = implode(',', array_fill(0, count($emotion_ids), '?'));
                 $q = "SELECT `emotion-id`, `" . $text_col . "` AS `text` FROM " . $emotions_table . " WHERE `emotion-id` IN ($ph)";
                 $st = $c->prepare($q);
                 if ($st !== false) {
                     $types = str_repeat('s', count($emotion_ids));
                     $bind_names = array(); $bind_names[] = &$types;
                     for ($i=0;$i<count($emotion_ids);$i++) $bind_names[] = &$emotion_ids[$i];
                     call_user_func_array(array($st,'bind_param'), $bind_names);
                     try {
                         $st->execute();
                         $res = $st->get_result();
                         while ($rr = $res->fetch_assoc()) {
                             $emotion_map[$rr['emotion-id']] = array('text' => $rr['text'] !== null ? $rr['text'] : null);
                         }
                     } catch (mysqli_sql_exception $e) {
                         // ignore
                     }
                     $st->close();
                 }
             }
         }

         // Build entity maps (they all have possible icon-id and localized text column)
         $icons_needed = array();
         $build_entity = function($ids, $table, $id_col, $entity_key) use ($c, $name_db, $find_text_column, $lang, &$entity_maps, &$icons_needed) {
             if (count($ids) === 0) return;
             // try user's language first then fallback to 'it'
             $text_col = $find_text_column($table, $lang);
             if ($text_col === null && $lang !== 'it') $text_col = $find_text_column($table, 'it');
             // try to detect an icon column name (common name 'icon-id' or ends with 'icon')
             $icon_col = null;
             try {
                 $q = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                 $stc = $c->prepare($q);
                 if ($stc !== false) {
                     $stc->bind_param('ss', $name_db, $table);
                     $stc->execute();
                     $rescols = $stc->get_result();
                     while ($rc = $rescols->fetch_assoc()) {
                         $cn = strtolower($rc['COLUMN_NAME']);
                         if ($icon_col === null && (strpos($cn, 'icon') !== false || strpos($cn, 'icon_id') !== false || strpos($cn, 'icon-id') !== false)) {
                             $icon_col = $rc['COLUMN_NAME'];
                             break;
                         }
                     }
                     $stc->close();
                 }
             } catch (mysqli_sql_exception $e) {
                 $icon_col = null;
             }

             $ph = implode(',', array_fill(0, count($ids), '?'));
             $cols = array('`' . $id_col . '`');
             if ($text_col !== null) $cols[] = '`' . $text_col . '` AS `text`';
             if ($icon_col !== null) $cols[] = '`' . $icon_col . '` AS `icon_id`';
             $qsel = "SELECT " . implode(',', $cols) . " FROM " . $table . " WHERE `$id_col` IN ($ph)";
             $st = $c->prepare($qsel);
             if ($st !== false) {
                 $types = str_repeat('s', count($ids));
                 $bind_names = array(); $bind_names[] = &$types;
                 for ($i=0;$i<count($ids);$i++) $bind_names[] = &$ids[$i];
                 call_user_func_array(array($st,'bind_param'), $bind_names);
                 try {
                     $st->execute();
                     $res = $st->get_result();
                     while ($r = $res->fetch_assoc()) {
                         $idv = $r[$id_col];
                         $entity_maps[$entity_key]['map'][$idv] = array(
                             'text' => isset($r['text']) ? $r['text'] : null,
                             'icon_id' => isset($r['icon_id']) ? $r['icon_id'] : null
                         );
                         if (isset($r['icon_id']) && $r['icon_id'] !== null) $icons_needed[] = $r['icon_id'];
                     }
                 } catch (mysqli_sql_exception $e) {
                     // ignore
                 }
                 $st->close();
             }
         };

         // weather
         $build_entity($weather_ids, $weather_table, 'weather-id', 'weather');
         // place
         $build_entity($place_ids, $places_table, 'place-id', 'place');
         // together-with
         $build_entity($together_ids, $together_with_table, 'together-with-id', 'together-with');
         // body-part
         $build_entity($body_part_ids, $body_parts_table, 'body-part-id', 'body-part');

         // Build icons map if needed
         $icons_needed = array_values(array_unique($icons_needed));
         if (count($icons_needed) > 0) {
             $ph = implode(',', array_fill(0, count($icons_needed), '?'));
             $qicons = "SELECT `icon-id`, `icon-url` FROM " . $icons_table . " WHERE `icon-id` IN ($ph)";
             $sticon = $c->prepare($qicons);
             if ($sticon !== false) {
                 $types = str_repeat('s', count($icons_needed));
                 $bind_names = array(); $bind_names[] = &$types;
                 for ($i=0;$i<count($icons_needed);$i++) $bind_names[] = &$icons_needed[$i];
                 call_user_func_array(array($sticon,'bind_param'), $bind_names);
                 try {
                     $sticon->execute();
                     $resco = $sticon->get_result();
                     $icon_map = array();
                     while ($ri = $resco->fetch_assoc()) {
                         $icon_map[$ri['icon-id']] = isset($ri['icon-url']) ? $ri['icon-url'] : null;
                     }
                     // attach icons to entity_maps
                     foreach ($entity_maps as $k => &$v) {
                         $v['icons'] = array();
                         if (isset($v['map']) && is_array($v['map'])) {
                             foreach ($v['map'] as $mid => $mdata) {
                                 $iid = isset($mdata['icon_id']) ? $mdata['icon_id'] : null;
                                 $v['icons'][$iid] = ($iid !== null && isset($icon_map[$iid])) ? $icon_map[$iid] : null;
                             }
                         }
                     }
                 } catch (mysqli_sql_exception $e) {
                     // ignore
                 }
                 $sticon->close();
             }
         }


        // --- PROCESS COLORS: resolve color-hex for color-id used in posts ---
        $color_map = array(); // color-id => hex
        $color_ids = array();
        foreach ($all_rows as $r) {
            if (isset($r['color-id']) && $r['color-id'] !== null && $r['color-id'] !== '') {
                $color_ids[] = $r['color-id'];
            }
        }
        $color_ids = array_values(array_unique($color_ids));
        if (count($color_ids) > 0) {
            // detect hex-like column in Colors table
            $colors_hex_col = null;
            try {
                $q_col_color = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                $st_col_color = $c->prepare($q_col_color);
                if ($st_col_color !== false) {
                    $tbl_colors = $colors_table;
                    $st_col_color->bind_param("ss", $name_db, $tbl_colors);
                    $st_col_color->execute();
                    $res_cols_color = $st_col_color->get_result();
                    while ($col = $res_cols_color->fetch_assoc()) {
                        $cn = strtolower($col['COLUMN_NAME']);
                        if ($colors_hex_col === null && strpos($cn, 'hex') !== false) {
                            $colors_hex_col = $col['COLUMN_NAME'];
                            break;
                        }
                    }
                    $st_col_color->close();
                }
            } catch (mysqli_sql_exception $e) {
                $colors_hex_col = null;
            }

            if ($colors_hex_col !== null) {
                $ph = implode(',', array_fill(0, count($color_ids), '?'));
                $q_colors = "SELECT `color-id`, `" . $colors_hex_col . "` AS `hex` FROM " . $colors_table . " WHERE `color-id` IN ($ph)";
                $st_colors = $c->prepare($q_colors);
                if ($st_colors !== false) {
                    $st_colors->bind_param(str_repeat('s', count($color_ids)), ...$color_ids);
                    try {
                        $st_colors->execute();
                        $res_colors = $st_colors->get_result();
                        while ($r = $res_colors->fetch_assoc()) {
                            $color_map[$r['color-id']] = $r['hex'];
                        }
                    } catch (mysqli_sql_exception $e) {
                        // ignore errors
                    }
                    $st_colors->close();
                }
            }
        }

        // --- PROCESS IMAGES: resolve image-url for image-id used in posts ---
        $image_map = array(); // image-id => url
        $image_ids = array();
        foreach ($all_rows as $r) {
            if (isset($r['image-id']) && $r['image-id'] !== null && $r['image-id'] !== '') {
                $image_ids[] = $r['image-id'];
            }
        }
        $image_ids = array_values(array_unique($image_ids));
        if (count($image_ids) > 0) {
            $ph = implode(',', array_fill(0, count($image_ids), '?'));
            $q_images = "SELECT `image-id`, `image-url` FROM " . $images_table . " WHERE `image-id` IN ($ph)";
            $st_images = $c->prepare($q_images);
            if ($st_images !== false) {
                $st_images->bind_param(str_repeat('s', count($image_ids)), ...$image_ids);
                try {
                    $st_images->execute();
                    $res_images = $st_images->get_result();
                    while ($r = $res_images->fetch_assoc()) {
                        $image_map[$r['image-id']] = $r['image-url'];
                    }
                } catch (mysqli_sql_exception $e) {
                    // ignore errors
                }
                $st_images->close();
            }
        }

        // --- PROCESS POSTS: normalize and enrich post objects ---
        // now build final posts array using processed maps (original output shape preserved)
        $get_posts = array();
        foreach ($all_rows as $row_post) {
            // normalize and add flags
            $row_post['visibility'] = (int)$row_post['visibility'];
            // preserve null for anonymous callers: keep null if present, otherwise cast to boolean
            if (array_key_exists('is-own-post', $row_post) && $row_post['is-own-post'] === null) {
                $row_post['is-own-post'] = null;
            } else {
                $row_post['is-own-post'] = isset($row_post['is-own-post']) ? ($row_post['is-own-post'] == 1) : false;
            }
            $row_post['is-user-followed'] = isset($row_post['is-user-followed']) ? ($row_post['is-user-followed'] == 1) : false;

            // ensure username and profile-image are present and remove user-id
            $row_post['username'] = isset($row_post['username']) ? $row_post['username'] : null;
            $row_post['profile-image'] = isset($row_post['profile-image']) ? $row_post['profile-image'] : null;
            if (isset($row_post['user-id'])) unset($row_post['user-id']);

            // Keep emotion-id as requested and add emotion-text if available
            $emotion_id = isset($row_post['emotion-id']) ? $row_post['emotion-id'] : null;
            if ($emotion_id !== null && isset($emotion_map[$emotion_id])) {
                $row_post['emotion-text'] = $emotion_map[$emotion_id]['text'] !== null ? $emotion_map[$emotion_id]['text'] : null;
            } else {
                $row_post['emotion-text'] = null;
            }
            // set if the emotion is followed by the current user
            if ($emotion_id !== null && isset($emotion_followed_map[$emotion_id]) && $emotion_followed_map[$emotion_id]) {
                $row_post['is-emotion-followed'] = true;
            } else {
                $row_post['is-emotion-followed'] = false;
            }

            // other linked entities: weather/place/together/body-part (reuse entity_maps if present)
            $wid = isset($row_post['weather-id']) ? $row_post['weather-id'] : null;
            if ($wid !== null && isset($entity_maps['weather']['map'][$wid])) {
                $row_post['weather-text'] = $entity_maps['weather']['map'][$wid]['text'] !== null ? $entity_maps['weather']['map'][$wid]['text'] : null;
                $wicon = $entity_maps['weather']['map'][$wid]['icon_id'];
                $row_post['weather-icon'] = ($wicon !== null && isset($entity_maps['weather']['icons'][$wicon])) ? $entity_maps['weather']['icons'][$wicon] : null;
            } else {
                $row_post['weather-text'] = null;
                $row_post['weather-icon'] = null;
            }

            $pid = isset($row_post['place-id']) ? $row_post['place-id'] : null;
            if ($pid !== null && isset($entity_maps['place']['map'][$pid])) {
                $row_post['place-text'] = $entity_maps['place']['map'][$pid]['text'] !== null ? $entity_maps['place']['map'][$pid]['text'] : null;
                $picon = $entity_maps['place']['map'][$pid]['icon_id'];
                $row_post['place-icon'] = ($picon !== null && isset($entity_maps['place']['icons'][$picon])) ? $entity_maps['place']['icons'][$picon] : null;
            } else {
                $row_post['place-text'] = null;
                $row_post['place-icon'] = null;
            }

            $tid = isset($row_post['together-with-id']) ? $row_post['together-with-id'] : null;
            if ($tid !== null && isset($entity_maps['together-with']['map'][$tid])) {
                $row_post['together-with-text'] = $entity_maps['together-with']['map'][$tid]['text'] !== null ? $entity_maps['together-with']['map'][$tid]['text'] : null;
                $ticon = $entity_maps['together-with']['map'][$tid]['icon_id'];
                $row_post['together-with-icon'] = ($ticon !== null && isset($entity_maps['together-with']['icons'][$ticon])) ? $entity_maps['together-with']['icons'][$ticon] : null;
            } else {
                $row_post['together-with-text'] = null;
                $row_post['together-with-icon'] = null;
            }

            $bid = isset($row_post['body-part-id']) ? $row_post['body-part-id'] : null;
            if ($bid !== null && isset($entity_maps['body-part']['map'][$bid])) {
                $row_post['body-part-text'] = $entity_maps['body-part']['map'][$bid]['text'] !== null ? $entity_maps['body-part']['map'][$bid]['text'] : null;
                $bicon = $entity_maps['body-part']['map'][$bid]['icon_id'];
                $row_post['body-part-icon'] = ($bicon !== null && isset($entity_maps['body-part']['icons'][$bicon])) ? $entity_maps['body-part']['icons'][$bicon] : null;
            } else {
                $row_post['body-part-text'] = null;
                $row_post['body-part-icon'] = null;
            }

            // remove legacy fields if present
            if (isset($row_post['emotion-it'])) unset($row_post['emotion-it']);
            if (isset($row_post['emotion-icon-url'])) unset($row_post['emotion-icon-url']);

            // reactions handling: keep original behavior (own posts -> counts, others -> user's reactions)
            if ($row_post['is-own-post']) {
                if ($row_post['visibility'] === 0) {
                    $query_reactions_count = "SELECT `reaction-id`, COUNT(*) AS `count` FROM $reactions_posts_table WHERE `post-id` = ? GROUP BY `reaction-id`";
                    $stmt_reactions = $c->prepare($query_reactions_count);
                    if ($stmt_reactions === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                    $stmt_reactions->bind_param("s", $row_post['post-id']);
                    try {
                        $stmt_reactions->execute();
                        $res_reactions = $stmt_reactions->get_result();
                        $reactions = array();
                        $reaction_ids = array();
                        while ($r = $res_reactions->fetch_assoc()) {
                            $reaction_ids[] = $r['reaction-id'];
                            $reactions[$r['reaction-id']] = array('reaction-id' => (int)$r['reaction-id'], 'count' => (int)$r['count']);
                        }

                        // fetch reaction icons if available
                        $reaction_icon_map = array();
                        if (count($reaction_ids) > 0) {
                            $ph = implode(',', array_fill(0, count($reaction_ids), '?'));
                            $icon_col = null;
                            try {
                                $q_icon = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                                $st_icon = $c->prepare($q_icon);
                                if ($st_icon !== false) {
                                    $st_icon->bind_param("ss", $name_db, $reactions_table);
                                    $st_icon->execute();
                                    $ricc = $st_icon->get_result()->fetch_assoc();
                                    if ($ricc && isset($ricc['COLUMN_NAME']) && $ricc['COLUMN_NAME'] !== '') $icon_col = $ricc['COLUMN_NAME'];
                                    $st_icon->close();
                                }
                            } catch (mysqli_sql_exception $e) {
                                $icon_col = null;
                            }

                            if ($icon_col !== null) {
                                $q_re_icons = "SELECT `reaction-id`, `" . $icon_col . "` AS `icon_id` FROM " . $reactions_table . " WHERE `reaction-id` IN ($ph)";
                                $st_re_icons = $c->prepare($q_re_icons);
                                if ($st_re_icons !== false) {
                                    $types = str_repeat('s', count($reaction_ids));
                                    $bind_names = array();
                                    $bind_names[] = &$types;
                                    for ($i = 0; $i < count($reaction_ids); $i++) $bind_names[] = &$reaction_ids[$i];
                                    call_user_func_array(array($st_re_icons, 'bind_param'), $bind_names);
                                    try {
                                        $st_re_icons->execute();
                                        $res_re_icons = $st_re_icons->get_result();
                                        while ($ri = $res_re_icons->fetch_assoc()) {
                                            $reaction_icon_map[$ri['reaction-id']] = isset($ri['icon_id']) ? $ri['icon_id'] : null;
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        // ignore
                                    }
                                    $st_re_icons->close();
                                }
                            }
                        }

                        $final_reactions = array();
                        foreach ($reactions as $rid => $info) {
                            $final_reactions[] = array(
                                'reaction-id' => (int)$info['reaction-id'],
                                'count' => (int)$info['count'],
                                'reaction-icon-id' => isset($reaction_icon_map[$rid]) ? $reaction_icon_map[$rid] : null
                            );
                        }
                        $row_post['reactions'] = $final_reactions;
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_reactions->close();
                } else {
                    $row_post['reactions'] = array();
                }
            } else {
                // other user's post: if anonymous, skip user-specific reactions (empty), otherwise return reactions by logged user
                if ($user_id === null) {
                    $row_post['reactions'] = array();
                } else {
                    $query_user_reactions = "SELECT `reaction-id` FROM $reactions_posts_table WHERE `post-id` = ? AND `user-id` = ?";
                    $stmt_user_reactions = $c->prepare($query_user_reactions);
                    if ($stmt_user_reactions === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                    $stmt_user_reactions->bind_param("ss", $row_post['post-id'], $user_id);
                    try {
                        $stmt_user_reactions->execute();
                        $res_user_reactions = $stmt_user_reactions->get_result();
                        $user_reaction_ids = array();
                        while ($ur = $res_user_reactions->fetch_assoc()) {
                            $user_reaction_ids[] = $ur['reaction-id'];
                        }

                        $user_reaction_icon_map = array();
                        if (count($user_reaction_ids) > 0) {
                            $ph = implode(',', array_fill(0, count($user_reaction_ids), '?'));
                            $icon_col2 = null;
                            try {
                                $q_icon2 = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                                $st_icon2 = $c->prepare($q_icon2);
                                if ($st_icon2 !== false) {
                                    $st_icon2->bind_param("ss", $name_db, $reactions_table);
                                    $st_icon2->execute();
                                    $ric2 = $st_icon2->get_result()->fetch_assoc();
                                    if ($ric2 && isset($ric2['COLUMN_NAME']) && $ric2['COLUMN_NAME'] !== '') $icon_col2 = $ric2['COLUMN_NAME'];
                                    $st_icon2->close();
                                }
                            } catch (mysqli_sql_exception $e) {
                                $icon_col2 = null;
                            }

                            if ($icon_col2 !== null) {
                                $q_urn_icons = "SELECT `reaction-id`, `" . $icon_col2 . "` AS `icon_id` FROM " . $reactions_table . " WHERE `reaction-id` IN ($ph)";
                                $st_urn_icons = $c->prepare($q_urn_icons);
                                if ($st_urn_icons !== false) {
                                    $types = str_repeat('s', count($user_reaction_ids));
                                    $bind_names = array();
                                    $bind_names[] = &$types;
                                    for ($i = 0; $i < count($user_reaction_ids); $i++) $bind_names[] = &$user_reaction_ids[$i];
                                    call_user_func_array(array($st_urn_icons, 'bind_param'), $bind_names);
                                    try {
                                        $st_urn_icons->execute();
                                        $res_urn_icons = $st_urn_icons->get_result();
                                        while ($ri = $res_urn_icons->fetch_assoc()) {
                                            $user_reaction_icon_map[$ri['reaction-id']] = isset($ri['icon_id']) ? $ri['icon_id'] : null;
                                        }
                                    } catch (mysqli_sql_exception $e) {
                                        // ignore
                                    }
                                    $st_urn_icons->close();
                                }
                            }
                        }

                        $user_reactions_final = array();
                        foreach ($user_reaction_ids as $rid) {
                            $user_reactions_final[] = array(
                                'reaction-id' => (int)$rid,
                                'reaction-icon-id' => isset($user_reaction_icon_map[$rid]) ? $user_reaction_icon_map[$rid] : null
                            );
                        }
                        $row_post['reactions'] = $user_reactions_final;
                    } catch (mysqli_sql_exception $e) {
                        responseError(500, "Database error: " . $e->getMessage());
                    }
                    $stmt_user_reactions->close();
                }
            }

            // Build an ordered post object for predictable output
            $ordered_post = array(
                // identifiers & timestamps
                'post-id' => isset($row_post['post-id']) ? $row_post['post-id'] : null,
                'created' => isset($row_post['created']) ? $row_post['created'] : null,

                // user info
                'username' => isset($row_post['username']) ? $row_post['username'] : null,
                'profile-image' => isset($row_post['profile-image']) ? $row_post['profile-image'] : null,

                // flags and visibility
                'is-own-post' => (array_key_exists('is-own-post', $row_post) && $row_post['is-own-post'] === null) ? null : (isset($row_post['is-own-post']) ? (bool)$row_post['is-own-post'] : false),
                'is-user-followed' => isset($row_post['is-user-followed']) ? (bool)$row_post['is-user-followed'] : false,
                'visibility' => isset($row_post['visibility']) ? (int)$row_post['visibility'] : null,

                // post metadata
                'language' => isset($row_post['language']) ? $row_post['language'] : null,
                'text' => isset($row_post['text']) ? $row_post['text'] : null,
                'color-id' => isset($row_post['color-id']) ? $row_post['color-id'] : null,
                'color-hex' => isset($row_post['color-id']) && isset($color_map[$row_post['color-id']]) ? $color_map[$row_post['color-id']] : null,
                'image' => (isset($row_post['image-id']) && isset($image_map[$row_post['image-id']])) ? array('image-id' => $row_post['image-id'], 'image-url' => $image_map[$row_post['image-id']], 'image-source' => isset($row_post['image-source']) ? $row_post['image-source'] : null) : (isset($row_post['image-id']) ? array('image-id' => $row_post['image-id'], 'image-url' => null, 'image-source' => isset($row_post['image-source']) ? $row_post['image-source'] : null) : null),
                'location' => isset($row_post['location']) ? $row_post['location'] : null,

                // emotion
                'emotion-id' => isset($row_post['emotion-id']) ? $row_post['emotion-id'] : null,
                'emotion-text' => isset($row_post['emotion-text']) ? $row_post['emotion-text'] : null,
                'is-emotion-followed' => isset($row_post['is-emotion-followed']) ? (bool)$row_post['is-emotion-followed'] : false,

                // weather
                'weather-id' => isset($row_post['weather-id']) ? $row_post['weather-id'] : null,
                'weather-text' => isset($row_post['weather-text']) ? $row_post['weather-text'] : null,
                'weather-icon' => isset($row_post['weather-icon']) ? $row_post['weather-icon'] : null,

                // place
                'place-id' => isset($row_post['place-id']) ? $row_post['place-id'] : null,
                'place-text' => isset($row_post['place-text']) ? $row_post['place-text'] : null,
                'place-icon' => isset($row_post['place-icon']) ? $row_post['place-icon'] : null,

                // together-with
                'together-with-id' => isset($row_post['together-with-id']) ? $row_post['together-with-id'] : null,
                'together-with-text' => isset($row_post['together-with-text']) ? $row_post['together-with-text'] : null,
                'together-with-icon' => isset($row_post['together-with-icon']) ? $row_post['together-with-icon'] : null,

                // body-part
                'body-part-id' => isset($row_post['body-part-id']) ? $row_post['body-part-id'] : null,
                'body-part-text' => isset($row_post['body-part-text']) ? $row_post['body-part-text'] : null,
                'body-part-icon' => isset($row_post['body-part-icon']) ? $row_post['body-part-icon'] : null,

                // reactions
                'reactions' => isset($row_post['reactions']) ? $row_post['reactions'] : array(),
            );

            // include any remaining original fields from posts that are not in ordered list
            $preserve_keys = array('post-id', 'created', 'username', 'profile-image', 'is-own-post', 'is-user-followed', 'visibility', 'language', 'text', 'color-id', 'color-hex', 'image', 'location', 'emotion-id', 'emotion-text', 'is-emotion-followed', 'weather-id', 'weather-text', 'weather-icon', 'place-id', 'place-text', 'place-icon', 'together-with-id', 'together-with-text', 'together-with-icon', 'body-part-id', 'body-part-text', 'body-part-icon', 'reactions');
            foreach ($row_post as $k => $v) {
                if (!in_array($k, $preserve_keys, true)) {
                    $ordered_post[$k] = $v;
                }
            }

            array_push($get_posts, $ordered_post);
        }

        $stmt_get_posts->close();

        responseSuccess(200, null, $get_posts);
    } catch (mysqli_sql_exception $e) {
        responseError(500, "Database error: " . $e->getMessage());
    }
} else {
    responseError(500, "Database connection error.");
}

