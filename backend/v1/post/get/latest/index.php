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

// Latest feed: REQUIRE login-id
if (!isset($post["login-id"]) || !checkFieldValidity($post["login-id"])) {
    responseError(400, "Missing or wrong parameters: login-id");
}
$login_id = $post["login-id"];

// limit: default 10, min 1, max 50
$limit = 10;
if (isset($post["limit"]) && checkNumberValidity($post["limit"])) {
    $limit = min(50, intval($post["limit"]));
}
$limit = max(1, $limit);

// offset is fixed to 0 for this endpoint
$offset = 0;

// language: optional (use user's language if available, otherwise default 'it')
$language = 'it';

$response = null;
if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
    $c->set_charset("utf8mb4");

    global $logins_table, $users_table, $otps_table;
    global $posts_table, $emotions_table, $emotions_followed_table, $users_followed_table, $reactions_table, $reactions_posts_table;
    global $icons_table, $weather_table, $places_table, $together_with_table, $body_parts_table, $colors_table, $images_table;

    // Resolve login-id and obtain user-id + language. For this endpoint login must be valid.
    $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`language` AS `language` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
    $stmt_get_user_id = $c->prepare($query_get_user_id);
    if ($stmt_get_user_id === false) responseError(500, "Database error: " . $c->error);
    $stmt_get_user_id->bind_param("s", $login_id);
    try {
        $stmt_get_user_id->execute();
        $result = $stmt_get_user_id->get_result();
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $user_id = $row["user-id"];
            if (!empty($row["language"])) $language = $row["language"];
        } else {
            $stmt_get_user_id->close();
            responseError(401, "Invalid or expired login-id");
        }
    } catch (mysqli_sql_exception $e) {
        responseError(500, "Database error: " . $e->getMessage());
    }
    $stmt_get_user_id->close();

    // Fetch list of followed users and followed emotions for flags
    $followed_user_ids = array();
    $followed_emotion_ids = array();

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
            // ignore
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

    try {
        // Build query to fetch latest posts from anyone (own private posts included)
        $query_get_posts = "SELECT `posts`.*, `u`.`username` AS `username`, `u`.`profile-image` AS `profile-image`, `icons_w`.`icon-url` AS `weather-icon-url`, `icons_p`.`icon-url` AS `place-icon-url`, `icons_t`.`icon-url` AS `together-with-icon-url`, `icons_bp`.`icon-url` AS `body-part-icon-url`, CASE WHEN `posts`.`user-id` = ? THEN 1 ELSE 0 END AS `is-own-post`, CASE WHEN `uf`.`followed-user-id` IS NOT NULL THEN 1 ELSE 0 END AS `is-user-followed` FROM (SELECT * FROM $posts_table WHERE (`visibility` = 0 OR `user-id` = ?)) AS `posts` LEFT JOIN (SELECT `followed-user-id` FROM $users_followed_table WHERE `user-id` = ?) AS `uf` ON `uf`.`followed-user-id` = `posts`.`user-id` LEFT JOIN $users_table AS `u` ON `u`.`user-id` = `posts`.`user-id` LEFT JOIN $emotions_table AS `e` ON `e`.`emotion-id` = `posts`.`emotion-id` LEFT JOIN $weather_table AS `w` ON `w`.`weather-id` = `posts`.`weather-id` LEFT JOIN $icons_table AS `icons_w` ON `icons_w`.`icon-id` = `w`.`icon-id` LEFT JOIN $places_table AS `p` ON `p`.`place-id` = `posts`.`place-id` LEFT JOIN $icons_table AS `icons_p` ON `icons_p`.`icon-id` = `p`.`icon-id` LEFT JOIN $together_with_table AS `t` ON `t`.`together-with-id` = `posts`.`together-with-id` LEFT JOIN $icons_table AS `icons_t` ON `icons_t`.`icon-id` = `t`.`icon-id` LEFT JOIN $body_parts_table AS `bp` ON `bp`.`body-part-id` = `posts`.`body-part-id` LEFT JOIN $icons_table AS `icons_bp` ON `icons_bp`.`icon-id` = `bp`.`icon-id` WHERE `u`.`status` = 1 ORDER BY `posts`.`created` DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt_get_posts = $c->prepare($query_get_posts);
        if ($stmt_get_posts === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);

        // Bind user id three times for CASE WHEN, WHERE subquery and uf join
        $stmt_get_posts->bind_param('sss', $user_id, $user_id, $user_id);

        $debug_mode = (isset($post['debug']) && $post['debug']);
        if ($debug_mode) {
            $final_query = isset($query_get_posts) ? $query_get_posts : '';
            header('X-Debug-SQL: ' . base64_encode($final_query));
            header('X-Debug-Limit: ' . intval($limit));
            header('X-Debug-Offset: ' . intval($offset));
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
            responseSuccess(201, null, array());
        }

        $all_rows = $result_posts->fetch_all(MYSQLI_ASSOC);

        // Build lookup maps for followed flags
        if (!isset($followed_user_ids) || !is_array($followed_user_ids)) $followed_user_ids = array();
        if (!isset($followed_emotion_ids) || !is_array($followed_emotion_ids)) $followed_emotion_ids = array();
        $followed_user_map = array(); foreach ($followed_user_ids as $fu) $followed_user_map[$fu] = true;
        $emotion_followed_map = array(); foreach ($followed_emotion_ids as $fe) $emotion_followed_map[$fe] = true;

        // Normalize flags per row
        for ($i = 0; $i < count($all_rows); $i++) {
            $row = $all_rows[$i];
            $all_rows[$i]['is-own-post'] = (isset($row['user-id']) && $row['user-id'] === $user_id) ? 1 : 0;
            $all_rows[$i]['is-user-followed'] = (isset($row['user-id']) && isset($followed_user_map[$row['user-id']])) ? 1 : 0;
            $all_rows[$i]['is-emotion-followed'] = (isset($row['emotion-id']) && isset($emotion_followed_map[$row['emotion-id']])) ? 1 : 0;
        }

        // sanitize language code
        $lang = 'it';
        if (isset($language) && preg_match('/^[a-z]{2}$/', $language)) {
            $lang = $language;
        }

        // --- BUILD emotion translations and localized entity maps (weather/place/together-with/body-part) ---
        $emotion_map = array();
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

        $find_text_column = function ($table, $preferLang = null) use ($c, $name_db, $lang) {
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
                // ignore
            }
            if (count($candidates) === 0) return null;
            $tryLangs = array();
            if ($preferLang !== null) $tryLangs[] = strtolower($preferLang);
            if (isset($lang)) $tryLangs[] = strtolower($lang);
            if (!in_array('it', $tryLangs)) $tryLangs[] = 'it';
            $lowerMap = array();
            foreach ($candidates as $cn) $lowerMap[strtolower($cn)] = $cn;
            foreach ($tryLangs as $tlang) {
                if (isset($lowerMap[$tlang])) return $lowerMap[$tlang];
                foreach ($lowerMap as $lcn => $orig) {
                    if (preg_match('/^' . preg_quote($tlang, '/') . '([_-]|$)/i', $lcn)) return $orig;
                }
                foreach ($lowerMap as $lcn => $orig) {
                    if (preg_match('/([_-])' . preg_quote($tlang, '/') . '$/i', $lcn)) return $orig;
                }
                foreach ($lowerMap as $lcn => $orig) {
                    if (preg_match('/(^|[_-])' . preg_quote($tlang, '/') . '($|[_-])/i', $lcn)) return $orig;
                }
                foreach ($lowerMap as $lcn => $orig) {
                    if (strpos($lcn, $tlang) !== false) return $orig;
                }
            }
            $common = array('text', 'name', 'title', 'label');
            foreach ($common as $cm) {
                foreach ($candidates as $cn) {
                    if (strtolower($cn) === $cm) return $cn;
                }
            }
            foreach ($candidates as $cn) {
                $lcn = strtolower($cn);
                if (!preg_match('/(_|-)?id$/i', $lcn)) return $cn;
            }
            return null;
        };

        // Build emotion map
        if (count($emotion_ids) > 0) {
            $text_col = $find_text_column($emotions_table, $lang);
            if ($text_col === null && $lang !== 'it') $text_col = $find_text_column($emotions_table, 'it');
            if ($text_col !== null) {
                $ph = implode(',', array_fill(0, count($emotion_ids), '?'));
                $q = "SELECT `emotion-id`, `" . $text_col . "` AS `text` FROM " . $emotions_table . " WHERE `emotion-id` IN ($ph) AND `to-show` = '1'";
                $st = $c->prepare($q);
                if ($st !== false) {
                    $types = str_repeat('s', count($emotion_ids));
                    $bind_names = array();
                    $bind_names[] = &$types;
                    for ($i = 0; $i < count($emotion_ids); $i++) $bind_names[] = &$emotion_ids[$i];
                    call_user_func_array(array($st, 'bind_param'), $bind_names);
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

        // Build entity maps
        $icons_needed = array();
        $build_entity = function ($ids, $table, $id_col, $entity_key) use ($c, $name_db, $find_text_column, $lang, &$entity_maps, &$icons_needed) {
            if (count($ids) === 0) return;
            $text_col = $find_text_column($table, $lang);
            if ($text_col === null && $lang !== 'it') $text_col = $find_text_column($table, 'it');
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
                $bind_names = array();
                $bind_names[] = &$types;
                for ($i = 0; $i < count($ids); $i++) $bind_names[] = &$ids[$i];
                call_user_func_array(array($st, 'bind_param'), $bind_names);
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

        $build_entity($weather_ids, $weather_table, 'weather-id', 'weather');
        $build_entity($place_ids, $places_table, 'place-id', 'place');
        $build_entity($together_ids, $together_with_table, 'together-with-id', 'together-with');
        $build_entity($body_part_ids, $body_parts_table, 'body-part-id', 'body-part');

        // Build icons map if needed
        $icons_needed = array_values(array_unique($icons_needed));
        if (count($icons_needed) > 0) {
            $ph = implode(',', array_fill(0, count($icons_needed), '?'));
            $qicons = "SELECT `icon-id`, `icon-url` FROM " . $icons_table . " WHERE `icon-id` IN ($ph)";
            $sticon = $c->prepare($qicons);
            if ($sticon !== false) {
                $types = str_repeat('s', count($icons_needed));
                $bind_names = array();
                $bind_names[] = &$types;
                for ($i = 0; $i < count($icons_needed); $i++) $bind_names[] = &$icons_needed[$i];
                call_user_func_array(array($sticon, 'bind_param'), $bind_names);
                try {
                    $sticon->execute();
                    $resco = $sticon->get_result();
                    $icon_map = array();
                    while ($ri = $resco->fetch_assoc()) {
                        $icon_map[$ri['icon-id']] = isset($ri['icon-url']) ? $ri['icon-url'] : null;
                    }
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

        // Colors
        $color_map = array();
        $color_ids = array();
        foreach ($all_rows as $r) {
            if (isset($r['color-id']) && $r['color-id'] !== null && $r['color-id'] !== '') {
                $color_ids[] = $r['color-id'];
            }
        }
        $color_ids = array_values(array_unique($color_ids));
        if (count($color_ids) > 0 && isset($colors_table) && $colors_table !== null) {
            $ph = implode(',', array_fill(0, count($color_ids), '?'));
            $qcols_direct = "SELECT `color-id`, `hex` AS `hex` FROM " . $colors_table . " WHERE `color-id` IN ($ph)";
            $stcols = $c->prepare($qcols_direct);
            if ($stcols !== false) {
                $types = str_repeat('s', count($color_ids));
                $bind_names = array();
                $bind_names[] = &$types;
                for ($i = 0; $i < count($color_ids); $i++) $bind_names[] = &$color_ids[$i];
                call_user_func_array(array($stcols, 'bind_param'), $bind_names);
                try {
                    $stcols->execute();
                    $resc = $stcols->get_result();
                    while ($r = $resc->fetch_assoc()) {
                        $color_map[$r['color-id']] = isset($r['hex']) ? $r['hex'] : null;
                    }
                } catch (mysqli_sql_exception $e) {
                    // ignore
                }
                $stcols->close();
            }
            $missing_ids = array();
            foreach ($color_ids as $cid) if (!isset($color_map[$cid])) $missing_ids[] = $cid;
            if (count($missing_ids) > 0) {
                $hex_col = null;
                try {
                    $q_cols = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                    $stc = $c->prepare($q_cols);
                    if ($stc !== false) {
                        $stc->bind_param('ss', $name_db, $colors_table);
                        $stc->execute();
                        $resc = $stc->get_result();
                        while ($rc = $resc->fetch_assoc()) {
                            $cn = strtolower($rc['COLUMN_NAME']);
                            if ($hex_col === null && ($cn === 'hex' || $cn === 'color_hex' || $cn === 'color-hex' || $cn === 'hex_code' || $cn === 'hexcode' || $cn === 'value')) {
                                $hex_col = $rc['COLUMN_NAME'];
                                break;
                            }
                            if ($hex_col === null && (strpos($cn, 'hex') !== false || strpos($cn, 'color') !== false)) {
                                $hex_col = $rc['COLUMN_NAME'];
                            }
                        }
                        $stc->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    $hex_col = null;
                }
                $ph2 = implode(',', array_fill(0, count($missing_ids), '?'));
                if ($hex_col !== null) {
                    $qcols = "SELECT `color-id`, `" . $hex_col . "` AS `hex` FROM " . $colors_table . " WHERE `color-id` IN ($ph2)";
                } else {
                    $qcols = "SELECT `color-id` FROM " . $colors_table . " WHERE `color-id` IN ($ph2)";
                }
                $stcols2 = $c->prepare($qcols);
                if ($stcols2 !== false) {
                    $types = str_repeat('s', count($missing_ids));
                    $bind_names = array();
                    $bind_names[] = &$types;
                    for ($i = 0; $i < count($missing_ids); $i++) $bind_names[] = &$missing_ids[$i];
                    call_user_func_array(array($stcols2, 'bind_param'), $bind_names);
                    try {
                        $stcols2->execute();
                        $resc = $stcols2->get_result();
                        while ($r = $resc->fetch_assoc()) {
                            if (isset($r['hex'])) {
                                $color_map[$r['color-id']] = $r['hex'];
                            } else {
                                $color_map[$r['color-id']] = null;
                            }
                        }
                    } catch (mysqli_sql_exception $e) {
                        // ignore
                    }
                    $stcols2->close();
                }
            }
        }

        // Images
        $image_map = array();
        $image_ids = array();
        foreach ($all_rows as $r) {
            if (isset($r['image-id']) && $r['image-id'] !== null && $r['image-id'] !== '') $image_ids[] = $r['image-id'];
        }
        $image_ids = array_values(array_unique($image_ids));
        if (count($image_ids) > 0) {
            $ph = implode(',', array_fill(0, count($image_ids), '?'));
            $q_images_direct = "SELECT `image-id`, `image-url` AS `image-url`, `image-source` AS `image-source` FROM " . $images_table . " WHERE `image-id` IN ($ph)";
            $st_images = $c->prepare($q_images_direct);
            if ($st_images !== false) {
                $types = str_repeat('s', count($image_ids));
                $bind_names = array();
                $bind_names[] = &$types;
                for ($i = 0; $i < count($image_ids); $i++) $bind_names[] = &$image_ids[$i];
                call_user_func_array(array($st_images, 'bind_param'), $bind_names);
                try {
                    $st_images->execute();
                    $res_images = $st_images->get_result();
                    while ($r = $res_images->fetch_assoc()) {
                        $image_map[$r['image-id']] = array(
                            'image-url' => isset($r['image-url']) ? $r['image-url'] : null,
                            'image-source' => isset($r['image-source']) ? $r['image-source'] : null
                        );
                    }
                } catch (mysqli_sql_exception $e) {
                    // ignore
                }
                $st_images->close();
            }
            $missing_imgs = array();
            foreach ($image_ids as $iid) if (!isset($image_map[$iid])) $missing_imgs[] = $iid;
            if (count($missing_imgs) > 0) {
                $ph2 = implode(',', array_fill(0, count($missing_imgs), '?'));
                $img_url_col = null; $img_source_col = null;
                try {
                    $q_img_cols = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
                    $st_img_cols = $c->prepare($q_img_cols);
                    if ($st_img_cols !== false) {
                        $st_img_cols->bind_param('ss', $name_db, $images_table);
                        $st_img_cols->execute();
                        $res_cols = $st_img_cols->get_result();
                        while ($rc = $res_cols->fetch_assoc()) {
                            $cn = strtolower($rc['COLUMN_NAME']);
                            if ($img_url_col === null && ($cn === 'image-url' || $cn === 'image_url' || $cn === 'imageurl' || $cn === 'image' || $cn === 'url' || $cn === 'src')) $img_url_col = $rc['COLUMN_NAME'];
                            if ($img_source_col === null && ($cn === 'image-source' || $cn === 'image_source' || $cn === 'source' || $cn === 'source_url' || $cn === 'source-url' || $cn === 'attribution' || $cn === 'credit' || $cn === 'origin' || $cn === 'provider')) $img_source_col = $rc['COLUMN_NAME'];
                            if ($img_url_col !== null && $img_source_col !== null) break;
                        }
                        $st_img_cols->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    $img_source_col = null; $img_url_col = null;
                }
                $select_cols = array('`image-id`');
                if ($img_url_col !== null) $select_cols[] = "`" . $img_url_col . "` AS `image-url`"; else $select_cols[] = "NULL AS `image-url`";
                if ($img_source_col !== null) $select_cols[] = "`" . $img_source_col . "` AS `image-source`"; else $select_cols[] = "NULL AS `image-source`";
                $q_images2 = "SELECT " . implode(',', $select_cols) . " FROM " . $images_table . " WHERE `image-id` IN ($ph2)";
                $st_images2 = $c->prepare($q_images2);
                if ($st_images2 !== false) {
                    $types = str_repeat('s', count($missing_imgs));
                    $bind_names = array();
                    $bind_names[] = &$types;
                    for ($i = 0; $i < count($missing_imgs); $i++) $bind_names[] = &$missing_imgs[$i];
                    call_user_func_array(array($st_images2, 'bind_param'), $bind_names);
                    try {
                        $st_images2->execute();
                        $res_images2 = $st_images2->get_result();
                        while ($r = $res_images2->fetch_assoc()) {
                            $image_map[$r['image-id']] = array(
                                'image-url' => isset($r['image-url']) ? $r['image-url'] : null,
                                'image-source' => isset($r['image-source']) ? $r['image-source'] : null
                            );
                        }
                    } catch (mysqli_sql_exception $e) {
                        // ignore
                    }
                    $st_images2->close();
                }
            }
        }

        // Reactions helper (same as before)
        function buildReactionsLikeReactionsGet($c, $postId, $userId, $isOwnPost, $visibility, $reactions_table, $reactions_posts_table, $name_db)
        {
            $out = array();
            $reactions_list = array();
            $icon_col = null;
            try {
                $q_icon = "SELECT `COLUMN_NAME` FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND LOWER(`COLUMN_NAME`) LIKE 'icon%' LIMIT 1";
                $st_icon = $c->prepare($q_icon);
                if ($st_icon !== false) {
                    $tbln = $reactions_table;
                    $st_icon->bind_param("ss", $name_db, $tbln);
                    $st_icon->execute();
                    $res_ic = $st_icon->get_result();
                    $ric = $res_ic->fetch_assoc();
                    if ($ric && isset($ric['COLUMN_NAME']) && $ric['COLUMN_NAME'] !== '') $icon_col = $ric['COLUMN_NAME'];
                    $st_icon->close();
                }
            } catch (mysqli_sql_exception $e) {
                $icon_col = null;
            }
            if ($icon_col !== null) {
                $q_reacts = "SELECT `reaction-id`, `" . $icon_col . "` AS `icon_id` FROM " . $reactions_table . " ORDER BY `reaction-id` ASC";
            } else {
                $q_reacts = "SELECT `reaction-id`, NULL AS `icon_id` FROM " . $reactions_table . " ORDER BY `reaction-id` ASC";
            }
            $st_reacts = $c->prepare($q_reacts);
            if ($st_reacts === false) return array();
            try {
                $st_reacts->execute();
                $res_reacts = $st_reacts->get_result();
                while ($r = $res_reacts->fetch_assoc()) {
                    $reactions_list[$r['reaction-id']] = array('reaction-id' => (int)$r['reaction-id'], 'reaction-icon-id' => isset($r['icon_id']) ? $r['icon_id'] : null);
                }
            } catch (mysqli_sql_exception $e) {
                return array();
            }
            $st_reacts->close();
            if ($postId === null) {
                foreach ($reactions_list as $rid => $info) {
                    $out[] = array(
                        'reaction-id' => (int)$info['reaction-id'],
                        'reaction-icon-id' => isset($info['reaction-icon-id']) ? $info['reaction-icon-id'] : null,
                        'is-inserted' => null,
                        'count' => null
                    );
                }
                return $out;
            }
            if ($isOwnPost) {
                if ($visibility !== 0) return array();
                $counts_map = array();
                try {
                    $q_counts = "SELECT `reaction-id`, COUNT(*) AS `cnt` FROM " . $reactions_posts_table . " WHERE `post-id` = ? GROUP BY `reaction-id`";
                    $st_counts = $c->prepare($q_counts);
                    if ($st_counts !== false) {
                        $st_counts->bind_param("s", $postId);
                        $st_counts->execute();
                        $res_counts = $st_counts->get_result();
                        while ($cr = $res_counts->fetch_assoc()) {
                            $counts_map[$cr['reaction-id']] = (int)$cr['cnt'];
                        }
                        $st_counts->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                foreach ($reactions_list as $rid => $info) {
                    $out[] = array(
                        'reaction-id' => (int)$info['reaction-id'],
                        'reaction-icon-id' => isset($info['reaction-icon-id']) ? $info['reaction-icon-id'] : null,
                        'is-inserted' => null,
                        'count' => isset($counts_map[$rid]) ? (int)$counts_map[$rid] : 0
                    );
                }
                return $out;
            }
            $user_inserted = array();
            if ($userId !== null) {
                try {
                    $q_user_ins = "SELECT `reaction-id` FROM " . $reactions_posts_table . " WHERE `post-id` = ? AND `user-id` = ?";
                    $st_user_ins = $c->prepare($q_user_ins);
                    if ($st_user_ins !== false) {
                        $st_user_ins->bind_param("ss", $postId, $userId);
                        $st_user_ins->execute();
                        $res_ui = $st_user_ins->get_result();
                        while ($ur = $res_ui->fetch_assoc()) {
                            $user_inserted[$ur['reaction-id']] = true;
                        }
                        $st_user_ins->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
            }
            foreach ($reactions_list as $rid => $info) {
                $out[] = array(
                    'reaction-id' => (int)$info['reaction-id'],
                    'reaction-icon-id' => isset($info['reaction-icon-id']) ? $info['reaction-icon-id'] : null,
                    'is-inserted' => ($userId === null) ? null : (isset($user_inserted[$rid]) ? true : false),
                    'count' => null
                );
            }
            return $out;
        }

        // Build final posts array
        $get_posts = array();
        foreach ($all_rows as $row_post) {
            $row_post['visibility'] = (int)$row_post['visibility'];
            $row_post['is-own-post'] = (isset($row_post['is-own-post']) && $row_post['is-own-post'] == 1) ? true : false;
            $row_post['is-user-followed'] = isset($row_post['is-user-followed']) ? ($row_post['is-user-followed'] == 1) : false;

            $row_post['username'] = isset($row_post['username']) ? $row_post['username'] : null;
            $row_post['profile-image'] = isset($row_post['profile-image']) ? $row_post['profile-image'] : null;
            if (isset($row_post['user-id'])) unset($row_post['user-id']);

            $emotion_id = isset($row_post['emotion-id']) ? $row_post['emotion-id'] : null;
            if ($emotion_id !== null && isset($emotion_map[$emotion_id])) {
                $row_post['emotion-text'] = $emotion_map[$emotion_id]['text'] !== null ? $emotion_map[$emotion_id]['text'] : null;
            } else {
                $row_post['emotion-text'] = null;
            }
            if ($emotion_id !== null && isset($emotion_followed_map[$emotion_id]) && $emotion_followed_map[$emotion_id]) $row_post['is-emotion-followed'] = true; else $row_post['is-emotion-followed'] = false;

            $wid = isset($row_post['weather-id']) ? $row_post['weather-id'] : null;
            if ($wid !== null && isset($entity_maps['weather']['map'][$wid])) {
                $row_post['weather-text'] = $entity_maps['weather']['map'][$wid]['text'] !== null ? $entity_maps['weather']['map'][$wid]['text'] : null;
                $wicon = $entity_maps['weather']['map'][$wid]['icon_id'];
                $row_post['weather-icon'] = ($wicon !== null && isset($entity_maps['weather']['icons'][$wicon])) ? $entity_maps['weather']['icons'][$wicon] : null;
            } else { $row_post['weather-text'] = null; $row_post['weather-icon'] = null; }

            $pid = isset($row_post['place-id']) ? $row_post['place-id'] : null;
            if ($pid !== null && isset($entity_maps['place']['map'][$pid])) {
                $row_post['place-text'] = $entity_maps['place']['map'][$pid]['text'] !== null ? $entity_maps['place']['map'][$pid]['text'] : null;
                $picon = $entity_maps['place']['map'][$pid]['icon_id'];
                $row_post['place-icon'] = ($picon !== null && isset($entity_maps['place']['icons'][$picon])) ? $entity_maps['place']['icons'][$picon] : null;
            } else { $row_post['place-text'] = null; $row_post['place-icon'] = null; }

            $tid = isset($row_post['together-with-id']) ? $row_post['together-with-id'] : null;
            if ($tid !== null && isset($entity_maps['together-with']['map'][$tid])) {
                $row_post['together-with-text'] = $entity_maps['together-with']['map'][$tid]['text'] !== null ? $entity_maps['together-with']['map'][$tid]['text'] : null;
                $ticon = $entity_maps['together-with']['map'][$tid]['icon_id'];
                $row_post['together-with-icon'] = ($ticon !== null && isset($entity_maps['together-with']['icons'][$ticon])) ? $entity_maps['together-with']['icons'][$ticon] : null;
            } else { $row_post['together-with-text'] = null; $row_post['together-with-icon'] = null; }

            $bid = isset($row_post['body-part-id']) ? $row_post['body-part-id'] : null;
            if ($bid !== null && isset($entity_maps['body-part']['map'][$bid])) {
                $row_post['body-part-text'] = $entity_maps['body-part']['map'][$bid]['text'] !== null ? $entity_maps['body-part']['map'][$bid]['text'] : null;
                $bicon = $entity_maps['body-part']['map'][$bid]['icon_id'];
                $row_post['body-part-icon'] = ($bicon !== null && isset($entity_maps['body-part']['icons'][$bicon])) ? $entity_maps['body-part']['icons'][$bicon] : null;
            } else { $row_post['body-part-text'] = null; $row_post['body-part-icon'] = null; }

            if (isset($row_post['emotion-it'])) unset($row_post['emotion-it']);
            if (isset($row_post['emotion-icon-url'])) unset($row_post['emotion-icon-url']);

            $row_post['reactions'] = buildReactionsLikeReactionsGet(
                $c,
                isset($row_post['post-id']) ? $row_post['post-id'] : null,
                $user_id,
                isset($row_post['is-own-post']) ? $row_post['is-own-post'] : false,
                isset($row_post['visibility']) ? $row_post['visibility'] : null,
                $reactions_table,
                $reactions_posts_table,
                $name_db
            );

            $ordered_post = array(
                'post-id' => isset($row_post['post-id']) ? $row_post['post-id'] : null,
                'created' => isset($row_post['created']) ? $row_post['created'] : null,
                'username' => isset($row_post['username']) ? $row_post['username'] : null,
                'profile-image' => isset($row_post['profile-image']) ? $row_post['profile-image'] : null,
                'is-own-post' => isset($row_post['is-own-post']) ? (bool)$row_post['is-own-post'] : false,
                'is-user-followed' => isset($row_post['is-user-followed']) ? (bool)$row_post['is-user-followed'] : false,
                'visibility' => isset($row_post['visibility']) ? (int)$row_post['visibility'] : null,
                'language' => isset($row_post['language']) ? $row_post['language'] : null,
                'text' => isset($row_post['text']) ? $row_post['text'] : null,
                'color-id' => isset($row_post['color-id']) ? $row_post['color-id'] : null,
                'color-hex' => isset($row_post['color-id']) ? (isset($color_map[$row_post['color-id']]) && $color_map[$row_post['color-id']] !== null ? $color_map[$row_post['color-id']] : '#000000') : null,
                'image' => (isset($row_post['image-id']) && isset($image_map[$row_post['image-id']])) ? array('image-id' => $row_post['image-id'], 'image-url' => $image_map[$row_post['image-id']]['image-url'], 'image-source' => $image_map[$row_post['image-id']]['image-source']) : (isset($row_post['image-id']) ? array('image-id' => $row_post['image-id'], 'image-url' => null, 'image-source' => null) : null),
                'location' => isset($row_post['location']) ? $row_post['location'] : null,
                'emotion-id' => isset($row_post['emotion-id']) ? $row_post['emotion-id'] : null,
                'emotion-text' => isset($row_post['emotion-text']) ? $row_post['emotion-text'] : null,
                'is-emotion-followed' => isset($row_post['is-emotion-followed']) ? (bool)$row_post['is-emotion-followed'] : false,
                'weather-id' => isset($row_post['weather-id']) ? $row_post['weather-id'] : null,
                'weather-text' => isset($row_post['weather-text']) ? $row_post['weather-text'] : null,
                'weather-icon' => isset($row_post['weather-icon']) ? $row_post['weather-icon'] : null,
                'place-id' => isset($row_post['place-id']) ? $row_post['place-id'] : null,
                'place-text' => isset($row_post['place-text']) ? $row_post['place-text'] : null,
                'place-icon' => isset($row_post['place-icon']) ? $row_post['place-icon'] : null,
                'together-with-id' => isset($row_post['together-with-id']) ? $row_post['together-with-id'] : null,
                'together-with-text' => isset($row_post['together-with-text']) ? $row_post['together-with-text'] : null,
                'together-with-icon' => isset($row_post['together-with-icon']) ? $row_post['together-with-icon'] : null,
                'body-part-id' => isset($row_post['body-part-id']) ? $row_post['body-part-id'] : null,
                'body-part-text' => isset($row_post['body-part-text']) ? $row_post['body-part-text'] : null,
                'body-part-icon' => isset($row_post['body-part-icon']) ? $row_post['body-part-icon'] : null,
                'reactions' => isset($row_post['reactions']) ? $row_post['reactions'] : array(),
            );

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

