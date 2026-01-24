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
$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["notification-id"]) && checkNumberValidity($post["notification-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table;
        global $notifications_table, $notifications_read_table;

        $login_id = $post["login-id"];
        $notification_id = trim($post["notification-id"]);
        $user_id = null;

        $action = null;

        $query_get_user_id = "SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1";
        $stmt_get_user_id = $c->prepare($query_get_user_id);
        $stmt_get_user_id->bind_param("s", $login_id);

        try {
            $stmt_get_user_id->execute();
            $result = $stmt_get_user_id->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                $user_id = $row["user-id"];

                // --- MARK NOTIFICATION AS READ: insert into notifications_read_table if not already present ---
                $q_check = "SELECT COUNT(*) AS cnt FROM " . $notifications_read_table . " WHERE `notification-id` = ? AND `user-id` = ?";
                $st_check = $c->prepare($q_check);
                if ($st_check === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                $st_check->bind_param("ss", $notification_id, $user_id);
                try {
                    // check existing
                    $st_check->execute();
                    $res_check = $st_check->get_result();
                    $rc = $res_check->fetch_assoc();

                    if (!($rc && isset($rc['cnt']) && intval($rc['cnt']) > 0)) {
                        // not present -> insert
                        $q_ins = "INSERT INTO " . $notifications_read_table . " (`notification-id`, `user-id`, `created`) VALUES (?, ?, CURRENT_TIMESTAMP)";
                        $st_ins = $c->prepare($q_ins);
                        if ($st_ins === false) throw new mysqli_sql_exception('Prepare failed: ' . $c->error);
                        $st_ins->bind_param("ss", $notification_id, $user_id);
                        try {
                            $st_ins->execute();
                            responseSuccess(204, null, null);
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $st_ins->close();
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $st_check->close();
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
    if (!isset($post["notification-id"]) || !checkNumberValidity($post["notification-id"])) {
        array_push($missing_parameters, "notification-id");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>