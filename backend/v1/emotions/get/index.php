<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
global $localhost_db, $username_db, $password_db, $name_db;
header("Content-Type:application/json");
$post = json_decode(file_get_contents('php://input'), true); //POST request
$get = $_GET; //GET request

$condition = true; //default condition
if ($condition) {
    $response = null;

    //Using prepared statements -> it's the safest way for MySQL queries
    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        //global $logins_table, $users_table, $otps_table;
        global $emotions_table;
        //$login_id = $post["login-id"];

        $emotion_id = null;
        if (isset($get["emotion-id"])) $emotion_id = $get["emotion-id"];

        $stmt = '';
        if ($emotion_id != null) {
            $stmt = $c->prepare("SELECT `emotion-id`, `it` FROM $emotions_table WHERE `emotion-id` = ?");
            $stmt->bind_param("s", $emotion_id);
        } else {
            $stmt = $c->prepare("SELECT `emotion-id`, `it` FROM $emotions_table");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

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

        $c->close();
    } else {
        responseError(500);
    }
} else {
    responseError(400);
}

?>