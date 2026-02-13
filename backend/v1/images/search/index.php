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

$condition = isset($get["text"]) && checkFieldValidity($get["text"]);
if ($condition) {
    $response = null;

    //Using prepared statements -> it's the safest way for MySQL queries
    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $images_table, $images_tags_table;

        $language = null;
        if (isset($get["language"]) && checkFieldValidity($get["language"])) $language = strtolower(trim($get["language"]));

        $offset = 0;
        if (isset($get["offset"]) && checkNumberValidity($get["offset"])) $offset = intval($get["offset"]);

        $limit = 50;
        if (isset($get["limit"]) && checkNumberValidity($get["limit"])) $limit = min(intval($get["limit"]), $limit); //cap limit to max 50
        $limit = max(1, $limit); //ensure limit is at least 1

        $rawSearch = isset($get["text"]) ? trim($get["text"]) : '';
        $words = array_filter(explode(" ", $rawSearch)); //remove empty words
        //remove duplicates or too short words (min length 3 chars)
        $words = array_unique(array_filter($words, function ($word) {
            return strlen($word) >= 3;
        }));
        $regexPattern = implode('|', array_map('preg_quote', $words)); // preg_quote used to escape special regex characters

        // If the regex pattern is empty, set it to match anything
        if (empty($regexPattern)) {
            $regexPattern = ".*";
        }

        $stmt = '';
        if ($language != null) {
            $stmt = $c->prepare("SELECT `images`.`image-id`, `images`.`image-url`, `images`.`image-source` FROM $images_table AS `images` INNER JOIN (SELECT DISTINCT `image-id` FROM `$images_tags_table` WHERE `language` = ? AND `text` COLLATE utf8mb4_unicode_ci REGEXP ?) AS `tags-result` ON `tags-result`.`image-id` = `images`.`image-id` LIMIT ? OFFSET ?");
            $stmt->bind_param("ssii", $language, $regexPattern, $limit, $offset);
        } else {
            $stmt = $c->prepare("SELECT `images`.`image-id`, `images`.`image-url`, `images`.`image-source` FROM $images_table AS `images` INNER JOIN (SELECT DISTINCT `image-id` FROM `$images_tags_table` WHERE `text` COLLATE utf8mb4_unicode_ci REGEXP ?) AS `tags-result` ON `tags-result`.`image-id` = `images`.`image-id` LIMIT ? OFFSET ?");
            $stmt->bind_param("sii", $regexPattern, $limit, $offset);
        }

        try {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $rows = array();
                while ($row = $result->fetch_assoc()) {
                    if (isset($row["image-id"]) && isset($row["image-url"]) && isset($row["image-source"])) {
                        $rows[] = $row;
                    }
                }

                responseSuccess(200, null, array_values($rows));
            } else {
                responseSuccess(201, "No images found.", []);
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
    $missing_parameters = array();
    if (!isset($get["text"]) || !checkFieldValidity($get["text"])) {
        array_push($missing_parameters, "text");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>