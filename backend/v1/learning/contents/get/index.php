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
$condition = isset($post["emotion-id"]) && checkNumberValidity($post["emotion-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $learning_statistics_table, $learning_contents_table, $learning_sources_table, $learning_images_table;
        global $emotions_table;

        $login_id = null;
        if (isset($post["login-id"]) && checkFieldValidity($post["login-id"])) {
            $login_id = $post["login-id"];
            //if login-id is provided, return also "status" 0|1|2 and "created" timestamp
            //status 0: not started, 1: started (not completed), 2: completed
        }
        $user_id = null;

        $language = "it";
        if (isset($post["language"]) && is_string($post["language"])) {
            // accept language codes like 'it', 'en' (two letters)
            if (preg_match('/^[a-z]{2}$/i', $post["language"])) {
                $language = $post["language"];
            }
        }

        $type = null;
        if (isset($post["type"]) && checkNumberValidity($post["type"])) {
            $type = intval($post["type"]);
        }
        $type2 = null;
        if ($type === 0 && isset($post["type2"]) && checkNumberValidity($post["type2"])) {
            $type2 = intval($post["type2"]);
        }

        // Determine sorting preference. Support both 'sorted' and 'sorted-by-priority' as possible params.
        $sorted_by_priority = true; // default: true
        $sorted_param = null;
        if (isset($post["sorted"])) $sorted_param = $post["sorted"];
        elseif (isset($post["sorted-by-priority"])) $sorted_param = $post["sorted-by-priority"];
        if ($sorted_param !== null) {
            if (is_bool($sorted_param)) {
                $sorted_by_priority = $sorted_param;
            } elseif (is_string($sorted_param)) {
                $lower = strtolower($sorted_param);
                if ($lower === 'false' || $lower === '0') $sorted_by_priority = false;
                elseif ($lower === 'true' || $lower === '1') $sorted_by_priority = true;
            } elseif (is_numeric($sorted_param)) {
                $sorted_by_priority = intval($sorted_param) !== 0;
            }
        }

        try {
            // verify that the passed emotion-id exists
            $emotion_id_check = intval($post["emotion-id"]);
            $query_check_emotion = "SELECT `emotion-id` FROM $emotions_table WHERE `emotion-id` = ?";
            $stmt_check_emotion = $c->prepare($query_check_emotion);
            $stmt_check_emotion->bind_param("s", $emotion_id_check);
            try {
                $stmt_check_emotion->execute();
                $res_check_emotion = $stmt_check_emotion->get_result();
                if ($res_check_emotion->num_rows !== 1) {
                    responseError(404, "Emotion not found.");
                }
            } catch (mysqli_sql_exception $e) {
                responseError(500, "Database error: " . $e->getMessage());
            }
            $stmt_check_emotion->close();

            // Authenticate only if login-id provided
            if ($login_id !== null) {
                $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
                $stmt_get_user_id = $c->prepare($query_get_user_id);
                $stmt_get_user_id->bind_param("s", $login_id);
                $stmt_get_user_id->execute();
                $result = $stmt_get_user_id->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    $user_id = $row["user-id"];
                } else {
                    //unauthorized: login-id not found or expired
                    responseError(440, "Unauthorized: invalid or expired login-id");
                }
                $stmt_get_user_id->close();
            }

            // If we have a user_id, compute overall learning status for the emotion
            $learning_status = null; // will be array with status and created if user authenticated
            if ($user_id !== null) {
                $emotion_id = intval($post["emotion-id"]);
                $query_stats = "SELECT `type`, `created` FROM $learning_statistics_table WHERE `user-id` = ? AND `emotion-id` = ? ORDER BY `created` DESC";
                $stmt_stats = $c->prepare($query_stats);
                $stmt_stats->bind_param("ss", $user_id, $emotion_id);
                $stmt_stats->execute();
                $res_stats = $stmt_stats->get_result();

                // New mapping: 0 = not started, 1 = started (not completed), 2 = completed
                $status_val = 0; // default: not started
                $status_created = null;
                // priority: if any type==1 -> status 2 (completed)
                // else if any type==0 -> status 1 (started but not completed)
                while ($r = $res_stats->fetch_assoc()) {
                    if (intval($r['type']) === 1) {
                        $status_val = 2;
                        $status_created = $r['created'];
                        break; // completed found, best status
                    } elseif (intval($r['type']) === 0 && $status_val !== 1) {
                        $status_val = 1;
                        $status_created = $status_created ?? $r['created'];
                        // don't break; keep searching for a completed (type==1)
                    }
                }
                $stmt_stats->close();

                $learning_status = array("status" => $status_val, "created" => $status_created);
            }

            // Fetch emotion text (use requested language column if available, fallback to 'it')
            $emotion_text = null;
            $emotion_description = null;
            $emotion_banner = null;
            $emotion_id = intval($post["emotion-id"]);
            // determine safe column name for language
            $lang_col = 'it';
            if (isset($language) && preg_match('/^[a-z]{2}$/i', $language)) {
                $lang_col = strtolower($language);
            }
            try {
                // Attempt to get language-specific text and description and banner-url
                $desc_col = 'description-' . $lang_col; // e.g. description-it
                // safe to interpolate $lang_col and $desc_col because validated
                $query_em = "SELECT `$lang_col` AS `emotion-text`, `$desc_col` AS `emotion-description`, `banner-url` AS `emotion-banner-url` FROM $emotions_table WHERE `emotion-id` = ?";
                $stmt_em = $c->prepare($query_em);
                $stmt_em->bind_param("s", $emotion_id);
                $stmt_em->execute();
                $res_em = $stmt_em->get_result();
                if ($res_em && $res_em->num_rows > 0) {
                    $er = $res_em->fetch_assoc();
                    if (isset($er['emotion-text']) && $er['emotion-text'] !== null && $er['emotion-text'] !== '') $emotion_text = $er['emotion-text'];
                    if (isset($er['emotion-description']) && $er['emotion-description'] !== null && $er['emotion-description'] !== '') $emotion_description = $er['emotion-description'];
                    if (isset($er['emotion-banner-url'])) $emotion_banner = $er['emotion-banner-url'];
                }
                $stmt_em->close();

                // Fallback: if emotion-text or description missing, try 'it' explicitly
                if (($emotion_text === null || $emotion_text === '') || ($emotion_description === null || $emotion_description === '')) {
                    $fallback_desc_col = 'description-it';
                    $query_fallback = "SELECT `it` AS `emotion-text`, `$fallback_desc_col` AS `emotion-description`, `banner-url` AS `emotion-banner-url` FROM $emotions_table WHERE `emotion-id` = ?";
                    $stmt_fb = $c->prepare($query_fallback);
                    $stmt_fb->bind_param("s", $emotion_id);
                    $stmt_fb->execute();
                    $res_fb = $stmt_fb->get_result();
                    if ($res_fb && $res_fb->num_rows > 0) {
                        $fr = $res_fb->fetch_assoc();
                        if (($emotion_text === null || $emotion_text === '') && isset($fr['emotion-text'])) $emotion_text = $fr['emotion-text'];
                        if (($emotion_description === null || $emotion_description === '') && isset($fr['emotion-description'])) $emotion_description = $fr['emotion-description'];
                        if ($emotion_banner === null && isset($fr['emotion-banner-url'])) $emotion_banner = $fr['emotion-banner-url'];
                    }
                    $stmt_fb->close();
                }
            } catch (mysqli_sql_exception $e) {
                // ignore, we'll return contents without emotion-text/description/banner if something goes wrong
                $emotion_text = $emotion_text ?? null;
                $emotion_description = $emotion_description ?? null;
                $emotion_banner = $emotion_banner ?? null;
            }

            // Build query to get learning contents
            $params = array();
            $where = array();
            $where[] = "`emotion-id` = ?";
            $params[] = $post["emotion-id"];

            if ($language !== null) {
                $where[] = "`language` = ?";
                $params[] = $language;
            }
            if ($type !== null) {
                $where[] = "`type` = ?";
                $params[] = $type;
                if ($type === 0 && $type2 !== null) {
                    $where[] = "`type-level2` = ?";
                    $params[] = $type2;
                }
            }

            // optional paging: support limit and offset (from POST or GET). Both optional.
            $limit = null;
            $offset = null;
            // prefer POST, then GET
            if (isset($post['limit']) && is_numeric($post['limit'])) {
                $limit = intval($post['limit']);
            } elseif (isset($get['limit']) && is_numeric($get['limit'])) {
                $limit = intval($get['limit']);
            }
            if (isset($post['offset']) && is_numeric($post['offset'])) {
                $offset = intval($post['offset']);
            } elseif (isset($get['offset']) && is_numeric($get['offset'])) {
                $offset = intval($get['offset']);
            }

            $query = "SELECT `learning-id`, `emotion-id`, `language`, `type`, `type-level2`, `sort-priority`, `title`, `text`, `image-id` FROM $learning_contents_table WHERE " . implode(" AND ", $where);
            if ($sorted_by_priority) {
                $query .= " ORDER BY `sort-priority` DESC, `learning-id` ASC";
            } else {
                $query .= " ORDER BY `learning-id` ASC";
            }

            // apply limit/offset if provided
            if ($limit !== null) {
                // use placeholder - will bind as string (mysqli accepts it)
                $query .= " LIMIT ?";
                $params[] = $limit;
            }
            if ($offset !== null) {
                $query .= " OFFSET ?";
                $params[] = $offset;
            }

            $stmt = $c->prepare($query);
            // bind params dynamically
            if (count($params) > 0) {
                // build types string (all as strings 's')
                $types = str_repeat('s', count($params));
                // mysqli_stmt::bind_param requires references
                $bind_names = array();
                $bind_names[] = $types;
                for ($i = 0; $i < count($params); $i++) {
                    $bind_name = 'bind' . $i;
                    $$bind_name = $params[$i];
                    $bind_names[] = &$$bind_name;
                }
                call_user_func_array(array($stmt, 'bind_param'), $bind_names);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $rows = array();
            $image_ids = array();
            $learning_ids = array();
            while ($r = $result->fetch_assoc()) {
                array_push($rows, $r);
                if (isset($r['image-id']) && $r['image-id'] !== null && $r['image-id'] !== '') {
                    $image_ids[] = intval($r['image-id']);
                }
                $learning_ids[] = intval($r['learning-id']);
            }
            $stmt->close();

            // fetch images in bulk
            $images_map = array();
            if (count($image_ids) > 0) {
                $unique_image_ids = array_values(array_unique($image_ids));
                $ids_list = implode(',', array_map('intval', $unique_image_ids));
                $query_images = "SELECT `image-id`, `image-url`, `image-source` FROM $learning_images_table WHERE `image-id` IN ($ids_list)";
                $res_images = $c->query($query_images);
                if ($res_images) {
                    while ($ir = $res_images->fetch_assoc()) {
                        $images_map[intval($ir['image-id'])] = $ir;
                    }
                    $res_images->close();
                }
            }

            // fetch sources in bulk
            $sources_map = array();
            if (count($learning_ids) > 0) {
                $unique_learning_ids = array_values(array_unique($learning_ids));
                $ids_list = implode(',', array_map('intval', $unique_learning_ids));
                $query_sources = "SELECT `source-id`, `source-name`, `source-link`, `learning-id` FROM $learning_sources_table WHERE `learning-id` IN ($ids_list)";
                $res_sources = $c->query($query_sources);
                if ($res_sources) {
                    while ($sr = $res_sources->fetch_assoc()) {
                        $lid = intval($sr['learning-id']);
                        if (!isset($sources_map[$lid])) $sources_map[$lid] = array();
                        // map source-name -> source-text as requested
                        $source_obj = array(
                            'source-id' => $sr['source-id'],
                            'source-text' => $sr['source-name'],
                            'source-link' => $sr['source-link']
                        );
                        array_push($sources_map[$lid], $source_obj);
                    }
                    $res_sources->close();
                }
            }

            // assemble final contents
            $contents = array();
            foreach ($rows as $r) {
                $learning_id = intval($r['learning-id']);
                $item = array(
                    'learning-id' => $learning_id,
                    // language is at group level; contents already filtered by language
                    'type' => intval($r['type']),
                    'type-level2' => isset($r['type-level2']) ? $r['type-level2'] : null,
                    'sort-priority' => isset($r['sort-priority']) ? intval($r['sort-priority']) : null,
                    'title' => $r['title'],
                    'text' => $r['text']
                );
                // attach image if available
                if (isset($r['image-id']) && $r['image-id'] !== null && $r['image-id'] !== '' && isset($images_map[intval($r['image-id'])])) {
                    $img = $images_map[intval($r['image-id'])];
                    $item['image'] = array(
                        'image-id' => $img['image-id'],
                        'image-url' => $img['image-url'],
                        'image-source' => $img['image-source']
                    );
                } else {
                    $item['image'] = null;
                }

                // attach sources if any
                if (isset($sources_map[$learning_id])) {
                    $item['sources'] = $sources_map[$learning_id];
                } else {
                    $item['sources'] = array();
                }

                array_push($contents, $item);
            }

            // build grouped response by emotion-id
            $group = array(
                'emotion-id' => intval($post['emotion-id']),
                'language' => $lang_col,
                'emotion-text' => $emotion_text,
                'emotion-description' => $emotion_description,
                'emotion-banner-url' => $emotion_banner,
                'contents' => array_values($contents)
            );
            if ($learning_status !== null) {
                $group['status'] = $learning_status['status'];
                $group['created'] = $learning_status['created'];
            }

            $data = array($group);

            responseSuccess(200, null, $data);

        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }

        $c->close();
    } else {
        responseError(500);
    }
} else {
    //bad request: missing parameters
    $missing_parameters = array();
    if (!isset($post["emotion-id"]) || !checkNumberValidity($post["emotion-id"])) {
        array_push($missing_parameters, "emotion-id");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>