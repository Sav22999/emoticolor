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

        //global $logins_table, $users_table, $otps_table;
        global $posts;
        //$login_id = $post["login-id"];

        $username = strtolower(trim($get["username"]));
        $login_id = null;
        if (isset($post["login-id"]) && checkFieldValidity($post["login-id"])) {
            $login_id = $post["login-id"];
        }

        //TODO: get user-id from username, then check if it's the same of login-id ("own profile")
        //TODO: if own profile, return all data (username, bio, profile picture) and also: number of followers, number of followings, number of emotions-followed
        //TODO: if other profile, return only public data (bio, profile picture, username) and also "is-following" (true/false)
        //TODO: if not login-id provided or is invalid, return only public data (so, treat as "other profile")
        /*$stmt = $c->prepare("SELECT `posts`.* FROM $posts AS `posts` WHERE `post-id` = ?");
        $stmt->bind_param("s", $emotion_id);

        try {
            $stmt->execute();

            //TODO
            if ($result->num_rows > 0) {
                $rows = array();
                while ($row = $result->fetch_assoc()) {
                    if (isset($row["emotion-id"]) && isset($row["it"])) {
                        $rows[] = $row;
                    }
                }

                responseSuccess(200, null, array_values($rows));
            } else {
                responseError(404, "No emotions found");
            }
        } catch (Exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $result = $stmt->get_result();
        $stmt->close();*/

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