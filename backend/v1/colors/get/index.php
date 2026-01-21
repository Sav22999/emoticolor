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
        global $colors_table;
        //$login_id = $post["login-id"];

        $color_id = null;
        if (isset($get["color-id"]) && checkFieldValidity($get["color-id"])) $color_id = strtolower(trim($get["color-id"]));

        $stmt = '';
        if ($color_id != null) {
            $stmt = $c->prepare("SELECT `color-id`, `hex`, `on-hex`, `it` FROM $colors_table WHERE `color-id` = ?");
            $stmt->bind_param("s", $color_id);
        } else {
            $stmt = $c->prepare("SELECT `color-id`, `hex`, `on-hex`, `it` FROM $colors_table");
        }

        try {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $rows = array();
                while ($row = $result->fetch_assoc()) {
                    if (isset($row["color-id"]) && isset($row["hex"]) && isset($row["on-hex"]) && isset($row["it"])) {
                        $row["hex"] = "#" . $row["hex"];
                        $row["on-hex"] = $row["on-hex"] === 1 ? "#000000" : "#FFFFFF";
                        $rows[] = $row;
                    }
                }

                responseSuccess(200, null, array_values($rows));
            } else {
                responseError(404, "No colors found.");
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
    responseError(400);
}

?>