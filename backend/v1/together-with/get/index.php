<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/credentials.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");
global $localhost_db, $username_db, $password_db, $name_db;
header("Content-Type:application/json");
$post = json_decode(file_get_contents('php://input'), true); //POST request
$get = $_GET; //GET request

$condition = true;
if ($condition) {
    $response = null;

    //Using prepared statements -> it's the safest way for MySQL queries
    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        //global $logins_table, $users_table, $otps_table;
        global $together_with_table, $icons_table;

        $together_with_id = null;
        if (isset($get["together-with-id"]) && checkNumberValidity($get["together-with-id"])) $together_with_id = $get["together-with-id"];

        $stmt = '';
        if ($together_with_id != null) {
            $stmt = $c->prepare("SELECT `together-with`.`together-with-id`, `together-with`.`it`, `together-with`.`icon-id`, `icons`.`icon-url` FROM $together_with_table AS `together-with` LEFT JOIN $icons_table AS  `icons` ON `together-with`.`icon-id` = `icons`.`icon-id` WHERE `together-with-id` = ?");
            $stmt->bind_param("s", $together_with_id);
        } else {
            $stmt = $c->prepare("SELECT `together-with`.`together-with-id`, `together-with`.`it`, `together-with`.`icon-id`, `icons`.`icon-url` FROM $together_with_table AS `together-with` LEFT JOIN $icons_table AS  `icons` ON `together-with`.`icon-id` = `icons`.`icon-id`");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $rows = array();
            while ($row = $result->fetch_assoc()) {
                if (isset($row["together-with-id"]) && isset($row["it"])) {
                    if($row["icon-id"] === null){
                        //remove icon-id and icon-url if icon-id is null
                        unset($row["icon-id"]);
                        unset($row["icon-url"]);
                    }
                    $rows[] = $row;
                }
            }

            responseSuccess(200, null, array_values($rows));
        } else {
            responseError(404, "No together-with found");
        }

        $c->close();
    } else {
        responseError(500);
    }
} else {
    responseError(400);
}

?>