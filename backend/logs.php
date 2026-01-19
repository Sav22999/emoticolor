<?php
/**
 * Add a log entry to the logs table.
 * @param $localhost_db string Database host
 * @param $username_db string Database username
 * @param $password_db string Database password
 * @param $name_db string Database name
 * @param $logs_table string Logs table name
 * @param $user_id string User ID
 * @param $action string Action description
 * @param $ip_address string IP address of the user
 * @return void
 */
function addLog(string $localhost_db, string $username_db, string $password_db, string $name_db, string $logs_table, string $user_id, string $action, string $ip_address = null): void
{
    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        $query_insert_log = "INSERT INTO $logs_table (`log-id`, `user-id`, `action`, `created`, `ip-address`) VALUES (NULL, ?, ?, CURRENT_TIMESTAMP,?)";
        $stmt_insert_log = $c->prepare($query_insert_log);
        $stmt_insert_log->bind_param("sss", $user_id, $action, $ip_address);
        try {
            $stmt_insert_log->execute();
            $stmt_insert_log->close();
        } catch (Exception $e) {
            //handle exception if needed
        }

        $c->close();
    }
}

?>