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


//use the following code of example for AUTHENTICATED requests
$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $posts_table, $emotions_table, $emotions_followed_table, $users_followed_table, $reactions_table, $reactions_posts_table;
        // tables used for localized linked entities and icons
        global $icons_table, $weather_table, $places_table, $together_with_table, $body_parts_table, $colors_table, $images_table;

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
        // accept offset only from POST
        if (isset($post["offset"]) && checkNumberValidity($post["offset"])) {
            $offset = max(0, intval($post["offset"]));
        }

        $limit = 50;
        // accept limit only from POST (cap to max 50)
        if (isset($post["limit"]) && checkNumberValidity($post["limit"])) $limit = min(intval($post["limit"]), $limit);
        $limit = max(1, $limit); //ensure limit is at least 1


        $user_id = null;

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`language` AS `language` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
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
                    // First, check if the username exists at all (and is active)
                    $query_check_user = "SELECT `user-id` FROM $users_table WHERE `username` = ? AND `status` = 1 LIMIT 1";
                    $stmt_check_user = $c->prepare($query_check_user);
                    if ($stmt_check_user === false) {
                        throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                    }
                    $stmt_check_user->bind_param("s", $username);
                    try {
                        $stmt_check_user->execute();
                        $res_check_user = $stmt_check_user->get_result();
                        if ($res_check_user->num_rows === 1) {
                            $r = $res_check_user->fetch_assoc();
                            $target_user_id = $r['user-id'];
                        } else {
                            // username truly doesn't exist
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
                    if ($post_id !== null) {
                        // single post: must be visible (visibility=0) OR own post
                        $query_get_post = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `post-id` = ? AND (`visibility` = 0 OR `user-id` = ?)) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id`";
                        $stmt_get_posts = $c->prepare($query_get_post);
                        if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                        $stmt_get_posts->bind_param("ssss", $user_id, $post_id, $user_id, $user_id);
                    } else if ($target_user_id !== null) {
                        // posts of a specific user
                        if ($target_user_id === $user_id) {
                            // own posts: include all visibilities
                            $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, 1 AS `is-own-post`, 0 AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `user-id` = ?) AS `posts` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` ORDER BY `posts`.`created` DESC";
                            // append safe, sanitized LIMIT/OFFSET directly to SQL (avoid binding LIMIT as param)
                            $query_get_posts .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
                            $stmt_get_posts = $c->prepare($query_get_posts);
                            if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            // bind: user-id only (single placeholder in this query)
                            $stmt_get_posts->bind_param("s", $user_id);
                        } else {
                            // other user's posts: only visible ones
                            $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE `user-id` = ? AND `visibility` = 0) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` ORDER BY `posts`.`created` DESC";
                            $query_get_posts .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
                            $stmt_get_posts = $c->prepare($query_get_posts);
                            if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            // bind: user_id (for CASE), target_user_id (filter), user_id (for uf)
                            $stmt_get_posts->bind_param("sss", $user_id, $target_user_id, $user_id);
                        }
                    } else {
                        // feed: posts from followed users or followed emotions, only visible OR own posts regardless of visibility, same language
                        $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM $posts_table AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` WHERE ((`posts`.`visibility` = 0) OR (`posts`.`user-id` = ?)) AND `posts`.`language` = ? AND (`posts`.`user-id` = ? OR `posts`.`emotion-id` IN (SELECT `emotion-id` FROM $emotions_followed_table WHERE `user-id` = ?)) ORDER BY `posts`.`created` DESC";
                        $query_get_posts .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
                        $stmt_get_posts = $c->prepare($query_get_posts);
                        if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                        // bind order: CASE WHEN (is-own-post) placeholder, uf subquery user-id, owner check in WHERE, language, posts.user-id in OR, emotions subquery user-id
                        $stmt_get_posts->bind_param("ssssss", $user_id, $user_id, $user_id, $language, $user_id, $user_id);
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
                        responseSuccess(200, null, array());
                    }

                    // Fetch all rows first so we can batch-query related emotion translations/icons
                    $all_rows = $result_posts->fetch_all(MYSQLI_ASSOC);

                    // sanitize language code and check if column exists in linked tables
                    // fallback to 'it' if invalid or not present
                    $lang = 'it';
                    if (isset($language) && preg_match('/^[a-z]{2}$/', $language)) {
                        $lang = $language;
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
                                $types = str_repeat('s', count($color_ids));
                                $bind_names = array();
                                $bind_names[] = &$types;
                                for ($i = 0; $i < count($color_ids); $i++) $bind_names[] = &$color_ids[$i];
                                call_user_func_array(array($st_colors, 'bind_param'), $bind_names);
                                try {
                                    $st_colors->execute();
                                    $res_colors = $st_colors->get_result();
                                    while ($cr = $res_colors->fetch_assoc()) {
                                        $color_map[$cr['color-id']] = $cr['hex'];
                                    }
                                } catch (mysqli_sql_exception $e) {
                                    // ignore and leave color_map empty
                                }
                                $st_colors->close();
                            }
                        }
                    }

                    // --- PROCESS IMAGES: resolve image-url and image-source for image-ids used in posts ---
                    $image_map = array(); // image-id => ['image-url'=>..., 'image-source'=>...]
                    $image_ids = array();
                    foreach ($all_rows as $r) {
                        if (isset($r['image-id']) && $r['image-id'] !== null && $r['image-id'] !== '') {
                            $image_ids[] = $r['image-id'];
                        }
                    }
                    $image_ids = array_values(array_unique($image_ids));
                    if (count($image_ids) > 0) {
                        // detect likely columns for url and source in Images table
                        $img_url_col = null;
                        $img_source_col = null;
                        try {
                            $q_cols_img = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                            $st_cols_img = $c->prepare($q_cols_img);
                            if ($st_cols_img !== false) {
                                $tbl_imgs = $images_table;
                                $st_cols_img->bind_param("ss", $name_db, $tbl_imgs);
                                $st_cols_img->execute();
                                $res_cols_img = $st_cols_img->get_result();
                                while ($col = $res_cols_img->fetch_assoc()) {
                                    $cn = strtolower($col['COLUMN_NAME']);
                                    if ($img_url_col === null && (strpos($cn, 'url') !== false || strpos($cn, 'image_url') !== false || strpos($cn, 'image-url') !== false)) {
                                        $img_url_col = $col['COLUMN_NAME'];
                                    }
                                    if ($img_source_col === null && (strpos($cn, 'source') !== false || strpos($cn, 'image_source') !== false || strpos($cn, 'image-source') !== false)) {
                                        $img_source_col = $col['COLUMN_NAME'];
                                    }
                                    if ($img_url_col !== null && $img_source_col !== null) break;
                                }
                                $st_cols_img->close();
                            }
                        } catch (mysqli_sql_exception $e) {
                            $img_url_col = null;
                            $img_source_col = null;
                        }

                        // build select only if we found at least the url column; source can be null
                        if ($img_url_col !== null) {
                            $ph = implode(',', array_fill(0, count($image_ids), '?'));
                            $selectCols = "`image-id`, `" . $img_url_col . "` AS `image_url`";
                            if ($img_source_col !== null) $selectCols .= ", `" . $img_source_col . "` AS `image_source`";
                            else $selectCols .= ", NULL AS `image_source`";

                            $q_images = "SELECT " . $selectCols . " FROM " . $images_table . " WHERE `image-id` IN ($ph)";
                            $st_imgs = $c->prepare($q_images);
                            if ($st_imgs !== false) {
                                $types = str_repeat('s', count($image_ids));
                                $bind_names = array();
                                $bind_names[] = &$types;
                                for ($i = 0; $i < count($image_ids); $i++) $bind_names[] = &$image_ids[$i];
                                call_user_func_array(array($st_imgs, 'bind_param'), $bind_names);
                                try {
                                    $st_imgs->execute();
                                    $res_imgs = $st_imgs->get_result();
                                    while ($ir = $res_imgs->fetch_assoc()) {
                                        $image_map[$ir['image-id']] = array('image-url' => isset($ir['image_url']) ? $ir['image_url'] : null, 'image-source' => isset($ir['image_source']) ? $ir['image_source'] : null);
                                    }
                                } catch (mysqli_sql_exception $e) {
                                    // ignore and leave image_map empty
                                }
                                $st_imgs->close();
                            }
                        }
                    }

                    // helper: fetch icon url map for a list of icon ids (detects icons table column names dynamically)
                    function fetch_icons_map($c, $icons_table, $name_db, $icons_to_fetch)
                    {
                        $map = array();
                        $icons_to_fetch = array_values(array_unique($icons_to_fetch));
                        if (count($icons_to_fetch) === 0) return $map;
                        $ph = implode(',', array_fill(0, count($icons_to_fetch), '?'));

                        // detect actual icon id and url column names in the icons table
                        $icons_id_col = 'icon-id';
                        $icons_url_col = 'icon-url';
                        try {
                            $q_cols = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                            $st_cols = $c->prepare($q_cols);
                            if ($st_cols !== false) {
                                $tbl_icons = $icons_table;
                                $st_cols->bind_param("ss", $name_db, $tbl_icons);
                                $st_cols->execute();
                                $res_cols = $st_cols->get_result();
                                while ($col = $res_cols->fetch_assoc()) {
                                    $cn = strtolower($col['COLUMN_NAME']);
                                    if ($icons_id_col === 'icon-id' && strpos($cn, 'icon') !== false && strpos($cn, 'id') !== false) {
                                        $icons_id_col = $col['COLUMN_NAME'];
                                    }
                                    if ($icons_url_col === 'icon-url' && strpos($cn, 'url') !== false) {
                                        $icons_url_col = $col['COLUMN_NAME'];
                                    }
                                }
                                $st_cols->close();
                            }
                        } catch (mysqli_sql_exception $e) {
                            // leave defaults
                        }

                        $query_icons = "SELECT `" . $icons_id_col . "` AS `icon_id`, `" . $icons_url_col . "` AS `icon_url` FROM $icons_table WHERE `" . $icons_id_col . "` IN ($ph)";
                        $stmt_ic = $c->prepare($query_icons);
                        if ($stmt_ic !== false) {
                            $types = str_repeat('s', count($icons_to_fetch));
                            $bind_names = array();
                            $bind_names[] = &$types;
                            for ($i = 0; $i < count($icons_to_fetch); $i++) {
                                $bind_names[] = &$icons_to_fetch[$i];
                            }
                            call_user_func_array(array($stmt_ic, 'bind_param'), $bind_names);
                            try {
                                $stmt_ic->execute();
                                $res_ic = $stmt_ic->get_result();
                                while ($ir = $res_ic->fetch_assoc()) {
                                    $map[$ir['icon_id']] = $ir['icon_url'];
                                }
                            } catch (mysqli_sql_exception $e) {
                                // ignore
                            }
                            $stmt_ic->close();
                        }
                        return $map;
                    }

                    // --- PROCESS EMOTIONS (existing logic, but use fetch_icons_map) ---
                    // check existence of column in information_schema for emotions language
                    $emotion_lang_exists = false;
                    try {
                        $query_check_col = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
                        $stmt_check_col = $c->prepare($query_check_col);
                        if ($stmt_check_col !== false) {
                            $tbl = $emotions_table;
                            $stmt_check_col->bind_param("sss", $name_db, $tbl, $lang);
                            $stmt_check_col->execute();
                            $res_check = $stmt_check_col->get_result();
                            $rcheck = $res_check->fetch_assoc();
                            if ($rcheck && isset($rcheck['cnt']) && intval($rcheck['cnt']) > 0) $emotion_lang_exists = true;
                            $stmt_check_col->close();
                        }
                    } catch (mysqli_sql_exception $e) {
                        $emotion_lang_exists = false;
                    }

                    // check if any 'icon*' column exists in Emotions table
                    $emotion_icon_col = null;
                    try {
                        $query_check_icon = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                        $stmt_check_icon = $c->prepare($query_check_icon);
                        if ($stmt_check_icon !== false) {
                            $tbl2 = $emotions_table;
                            $stmt_check_icon->bind_param("ss", $name_db, $tbl2);
                            $stmt_check_icon->execute();
                            $res_check_icon = $stmt_check_icon->get_result();
                            $ric = $res_check_icon->fetch_assoc();
                            if ($ric && isset($ric['COLUMN_NAME']) && $ric['COLUMN_NAME'] !== '') {
                                $emotion_icon_col = $ric['COLUMN_NAME'];
                            }
                            $stmt_check_icon->close();
                        }
                    } catch (mysqli_sql_exception $e) {
                        $emotion_icon_col = null;
                    }

                    // collect distinct emotion-ids from posts
                    $emotion_ids = array();
                    foreach ($all_rows as $r) {
                        if (isset($r['emotion-id']) && $r['emotion-id'] !== null && $r['emotion-id'] !== '') {
                            $emotion_ids[] = $r['emotion-id'];
                        }
                    }
                    $emotion_ids = array_values(array_unique($emotion_ids));

                    $emotion_map = array(); // emotion-id => ['text'=>..., 'icon_id' => ...]
                    $icons_to_fetch = array();

                    if (count($emotion_ids) > 0) {
                        $placeholders = implode(',', array_fill(0, count($emotion_ids), '?'));
                        $textExpr = $emotion_lang_exists ? "`$lang`" : "NULL";
                        if ($emotion_icon_col !== null) {
                            $iconExpr = "`" . $emotion_icon_col . "` AS `icon_id`";
                        } else {
                            $iconExpr = "NULL AS `icon_id`";
                        }
                        $query_emotions = "SELECT `emotion-id`, " . $textExpr . " AS `text`, " . $iconExpr . " FROM $emotions_table WHERE `emotion-id` IN ($placeholders)";
                        $stmt_em = $c->prepare($query_emotions);
                        if ($stmt_em !== false) {
                            $types = str_repeat('s', count($emotion_ids));
                            $bind_names = array();
                            $bind_names[] = &$types;
                            for ($i = 0; $i < count($emotion_ids); $i++) {
                                $bind_names[] = &$emotion_ids[$i];
                            }
                            call_user_func_array(array($stmt_em, 'bind_param'), $bind_names);
                            try {
                                $stmt_em->execute();
                                $res_em = $stmt_em->get_result();
                                while ($er = $res_em->fetch_assoc()) {
                                    $emotion_map[$er['emotion-id']] = array('text' => $er['text'], 'icon_id' => isset($er['icon_id']) ? $er['icon_id'] : null);
                                    if (isset($er['icon_id']) && $er['icon_id'] !== null) $icons_to_fetch[] = $er['icon_id'];
                                }
                            } catch (mysqli_sql_exception $e) {
                                // ignore and leave map empty
                            }
                            $stmt_em->close();
                        }
                    }

                    // resolve icons for emotions
                    $icons_map = fetch_icons_map($c, $icons_table, $name_db, $icons_to_fetch);

                    // --- PROCESS EMOTION FOLLOWED FLAGS: determine if emotion-id used in posts is followed by current user ---
                    $emotion_followed_map = array();
                    if (count($emotion_ids) > 0) {
                        try {
                            $ph = implode(',', array_fill(0, count($emotion_ids), '?'));
                            $q_em_follow = "SELECT `emotion-id` FROM " . $emotions_followed_table . " WHERE `user-id` = ? AND `emotion-id` IN ($ph)";
                            $stmt_emf = $c->prepare($q_em_follow);
                            if ($stmt_emf !== false) {
                                // bind user-id first, then emotion ids
                                $types = 's' . str_repeat('s', count($emotion_ids));
                                $bind_names = array();
                                $bind_names[] = &$types;
                                $bind_names[] = &$user_id;
                                for ($i = 0; $i < count($emotion_ids); $i++) $bind_names[] = &$emotion_ids[$i];
                                call_user_func_array(array($stmt_emf, 'bind_param'), $bind_names);
                                try {
                                    $stmt_emf->execute();
                                    $res_emf = $stmt_emf->get_result();
                                    while ($ef = $res_emf->fetch_assoc()) {
                                        $emotion_followed_map[$ef['emotion-id']] = true;
                                    }
                                } catch (mysqli_sql_exception $e) {
                                    // ignore and leave map empty
                                }
                                $stmt_emf->close();
                            }
                        } catch (mysqli_sql_exception $e) {
                            // ignore
                        }
                    }

                    // --- PROCESS OTHER LINKED ENTITIES: weather, place, together, body-part ---
                    $linked_entities = array(
                        array('table' => $weather_table, 'idcol' => 'weather-id', 'prefix' => 'weather'),
                        array('table' => $places_table, 'idcol' => 'place-id', 'prefix' => 'place'),
                        // prefix must match the naming convention used in output keys: 'together-with'
                        array('table' => $together_with_table, 'idcol' => 'together-with-id', 'prefix' => 'together-with'),
                        array('table' => $body_parts_table, 'idcol' => 'body-part-id', 'prefix' => 'body-part')
                    );

                    $entity_maps = array(); // prefix => map(id => ['text'=>..., 'icon_id'=>...])

                    foreach ($linked_entities as $ent) {
                        $ids = array();
                        foreach ($all_rows as $r) {
                            if (isset($r[$ent['idcol']]) && $r[$ent['idcol']] !== null && $r[$ent['idcol']] !== '') {
                                $ids[] = $r[$ent['idcol']];
                            }
                        }
                        $ids = array_values(array_unique($ids));
                        $emap = array();
                        $icons_needed = array();
                        if (count($ids) > 0) {
                            // check language column exists for this entity
                            $lang_exists = false;
                            try {
                                $q_check = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
                                $stc = $c->prepare($q_check);
                                if ($stc !== false) {
                                    $tbln = $ent['table'];
                                    $stc->bind_param("sss", $name_db, $tbln, $lang);
                                    $stc->execute();
                                    $resc = $stc->get_result();
                                    $rc = $resc->fetch_assoc();
                                    if ($rc && isset($rc['cnt']) && intval($rc['cnt']) > 0) $lang_exists = true;
                                    $stc->close();
                                }
                            } catch (mysqli_sql_exception $e) {
                                $lang_exists = false;
                            }

                            // check icon column for this entity
                            $icon_col = null;
                            try {
                                $q_icon = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                                $st_icon = $c->prepare($q_icon);
                                if ($st_icon !== false) {
                                    $tbln2 = $ent['table'];
                                    $st_icon->bind_param("ss", $name_db, $tbln2);
                                    $st_icon->execute();
                                    $ricc = $st_icon->get_result()->fetch_assoc();
                                    if ($ricc && isset($ricc['COLUMN_NAME']) && $ricc['COLUMN_NAME'] !== '') $icon_col = $ricc['COLUMN_NAME'];
                                    $st_icon->close();
                                }
                            } catch (mysqli_sql_exception $e) {
                                $icon_col = null;
                            }

                            // fetch rows
                            $ph = implode(',', array_fill(0, count($ids), '?'));
                            $textExpr = $lang_exists ? "`$lang`" : "NULL";
                            if ($icon_col !== null) {
                                $iconExpr = "`" . $icon_col . "` AS `icon_id`";
                            } else {
                                $iconExpr = "NULL AS `icon_id`";
                            }
                            $q_ent = "SELECT `" . $ent['idcol'] . "`, " . $textExpr . " AS `text`, " . $iconExpr . " FROM " . $ent['table'] . " WHERE `" . $ent['idcol'] . "` IN ($ph)";
                            $st_ent = $c->prepare($q_ent);
                            if ($st_ent !== false) {
                                $types = str_repeat('s', count($ids));
                                $bind_names = array();
                                $bind_names[] = &$types;
                                for ($i = 0; $i < count($ids); $i++) $bind_names[] = &$ids[$i];
                                call_user_func_array(array($st_ent, 'bind_param'), $bind_names);
                                try {
                                    $st_ent->execute();
                                    $res_ent = $st_ent->get_result();
                                    while ($er = $res_ent->fetch_assoc()) {
                                        $emap[$er[$ent['idcol']]] = array('text' => $er['text'], 'icon_id' => isset($er['icon_id']) ? $er['icon_id'] : null);
                                        if (isset($er['icon_id']) && $er['icon_id'] !== null) $icons_needed[] = $er['icon_id'];
                                    }
                                } catch (mysqli_sql_exception $e) {
                                    // ignore
                                }
                                $st_ent->close();
                            }
                        }

                        // resolve icons for this entity
                        $icons_map_ent = fetch_icons_map($c, $icons_table, $name_db, $icons_needed);

                        // store map and icons_map for later use when building posts
                        $entity_maps[$ent['prefix']] = array('map' => $emap, 'icons' => $icons_map_ent);
                    }

                    // now build final posts array using processed maps
                    $get_posts = array();
                    foreach ($all_rows as $row_post) {
                        // normalize and add flags
                        $row_post['visibility'] = (int)$row_post['visibility'];
                        $row_post['is-own-post'] = ($row_post['is-own-post'] == 1);
                        $row_post['is-user-followed'] = ($row_post['is-user-followed'] == 1);

                        // ensure username and profile-image are present and remove user-id
                        $row_post['username'] = isset($row_post['username']) ? $row_post['username'] : null;
                        $row_post['profile-image'] = isset($row_post['profile-image']) ? $row_post['profile-image'] : null;
                        if (isset($row_post['user-id'])) unset($row_post['user-id']);

                        // Keep emotion-id as requested and add emotion-text and emotion-icon
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

                        // Add text/icon for other linked entities (weather/place/together/body-part)
                        // weather
                        $wid = isset($row_post['weather-id']) ? $row_post['weather-id'] : null;
                        if ($wid !== null && isset($entity_maps['weather']['map'][$wid])) {
                            $row_post['weather-text'] = $entity_maps['weather']['map'][$wid]['text'] !== null ? $entity_maps['weather']['map'][$wid]['text'] : null;
                            $wicon = $entity_maps['weather']['map'][$wid]['icon_id'];
                            $row_post['weather-icon'] = ($wicon !== null && isset($entity_maps['weather']['icons'][$wicon])) ? $entity_maps['weather']['icons'][$wicon] : null;
                        } else {
                            $row_post['weather-text'] = null;
                            $row_post['weather-icon'] = null;
                        }

                        // place
                        $pid = isset($row_post['place-id']) ? $row_post['place-id'] : null;
                        if ($pid !== null && isset($entity_maps['place']['map'][$pid])) {
                            $row_post['place-text'] = $entity_maps['place']['map'][$pid]['text'] !== null ? $entity_maps['place']['map'][$pid]['text'] : null;
                            $picon = $entity_maps['place']['map'][$pid]['icon_id'];
                            $row_post['place-icon'] = ($picon !== null && isset($entity_maps['place']['icons'][$picon])) ? $entity_maps['place']['icons'][$picon] : null;
                        } else {
                            $row_post['place-text'] = null;
                            $row_post['place-icon'] = null;
                        }

                        // together
                        $tid = isset($row_post['together-with-id']) ? $row_post['together-with-id'] : null;
                        // lookup in entity_maps under the 'together-with' prefix
                        if ($tid !== null && isset($entity_maps['together-with']['map'][$tid])) {
                            $row_post['together-with-text'] = $entity_maps['together-with']['map'][$tid]['text'] !== null ? $entity_maps['together-with']['map'][$tid]['text'] : null;
                            $ticon = $entity_maps['together-with']['map'][$tid]['icon_id'];
                            $row_post['together-with-icon'] = ($ticon !== null && isset($entity_maps['together-with']['icons'][$ticon])) ? $entity_maps['together-with']['icons'][$ticon] : null;
                        } else {
                            $row_post['together-with-text'] = null;
                            $row_post['together-with-icon'] = null;
                        }

                        // body-part
                        $bid = isset($row_post['body-part-id']) ? $row_post['body-part-id'] : null;
                        if ($bid !== null && isset($entity_maps['body-part']['map'][$bid])) {
                            $row_post['body-part-text'] = $entity_maps['body-part']['map'][$bid]['text'] !== null ? $entity_maps['body-part']['map'][$bid]['text'] : null;
                            $bicon = $entity_maps['body-part']['map'][$bid]['icon_id'];
                            $row_post['body-part-icon'] = ($bicon !== null && isset($entity_maps['body-part']['icons'][$bicon])) ? $entity_maps['body-part']['icons'][$bicon] : null;
                        } else {
                            $row_post['body-part-text'] = null;
                            $row_post['body-part-icon'] = null;
                        }

                        // remove any previously selected emotion-it/icon-url fields if present
                        if (isset($row_post['emotion-it'])) unset($row_post['emotion-it']);
                        if (isset($row_post['emotion-icon-url'])) unset($row_post['emotion-icon-url']);

                        // reactions handling (same as before)
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
                                    $reaction_ids = array();
                                    while ($r = $res_reactions->fetch_assoc()) {
                                        // collect ids and counts
                                        $reaction_ids[] = $r['reaction-id'];
                                        $reactions[$r['reaction-id']] = array('reaction-id' => (int)$r['reaction-id'], 'count' => (int)$r['count']);
                                    }

                                    // fetch reaction -> icon-id mapping if we have reaction ids
                                    $reaction_icon_map = array(); // reaction-id => icon-id
                                    if (count($reaction_ids) > 0) {
                                        $ph = implode(',', array_fill(0, count($reaction_ids), '?'));
                                        // detect icon-like column in reactions table
                                        $icon_col = null;
                                        try {
                                            $q_icon = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                                            $st_icon = $c->prepare($q_icon);
                                            if ($st_icon !== false) {
                                                $tbln2 = $reactions_table;
                                                $st_icon->bind_param("ss", $name_db, $tbln2);
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
                                                    // ignore and leave map empty
                                                }
                                                $st_re_icons->close();
                                            }
                                        }
                                    }

                                    // build final reactions array preserving counts + icon ids
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
                            // other user's post: return reactions inserted by login-id's user (if any)
                            $query_user_reactions = "SELECT `reaction-id` FROM $reactions_posts_table WHERE `post-id` = ? AND `user-id` = ?";
                            $stmt_user_reactions = $c->prepare($query_user_reactions);
                            if ($stmt_user_reactions === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                            $stmt_user_reactions->bind_param("ss", $row_post['post-id'], $user_id);
                            try {
                                $stmt_user_reactions->execute();
                                $res_user_reactions = $stmt_user_reactions->get_result();
                                $user_reactions = array();
                                $user_reaction_ids = array();
                                while ($ur = $res_user_reactions->fetch_assoc()) {
                                    $user_reaction_ids[] = $ur['reaction-id'];
                                }

                                // fetch reaction -> icon-id mapping for these reaction ids
                                $user_reaction_icon_map = array();
                                if (count($user_reaction_ids) > 0) {
                                    $ph = implode(',', array_fill(0, count($user_reaction_ids), '?'));
                                    // detect icon-like column in reactions table
                                    $icon_col2 = null;
                                    try {
                                        $q_icon2 = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                                        $st_icon2 = $c->prepare($q_icon2);
                                        if ($st_icon2 !== false) {
                                            $tbln3 = $reactions_table;
                                            $st_icon2->bind_param("ss", $name_db, $tbln3);
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

                                // build final array of objects
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

                        // Build an ordered post object for predictable output
                        $ordered_post = array(
                            // identifiers & timestamps
                            'post-id' => isset($row_post['post-id']) ? $row_post['post-id'] : null,
                            'created' => isset($row_post['created']) ? $row_post['created'] : null,

                            // user info
                            'username' => isset($row_post['username']) ? $row_post['username'] : null,
                            'profile-image' => isset($row_post['profile-image']) ? $row_post['profile-image'] : null,

                            // flags and visibility
                            'is-own-post' => isset($row_post['is-own-post']) ? (bool)$row_post['is-own-post'] : false,
                            'is-user-followed' => isset($row_post['is-user-followed']) ? (bool)$row_post['is-user-followed'] : false,
                            'visibility' => isset($row_post['visibility']) ? (int)$row_post['visibility'] : null,

                            // post metadata
                            'language' => isset($row_post['language']) ? $row_post['language'] : null,
                            'text' => isset($row_post['text']) ? $row_post['text'] : null,
                            'color-id' => isset($row_post['color-id']) ? $row_post['color-id'] : null,
                            'color-hex' => isset($row_post['color-id']) && isset($color_map[$row_post['color-id']]) ? $color_map[$row_post['color-id']] : null,
                            'image' => (isset($row_post['image-id']) && isset($image_map[$row_post['image-id']])) ? array('image-id' => $row_post['image-id'], 'image-url' => $image_map[$row_post['image-id']]['image-url'], 'image-source' => $image_map[$row_post['image-id']]['image-source']) : (isset($row_post['image-id']) ? array('image-id' => $row_post['image-id'], 'image-url' => null, 'image-source' => null) : null),
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

