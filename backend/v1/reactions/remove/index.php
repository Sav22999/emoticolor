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


$condition = isset($post["login-id"]) && checkFieldValidity($post["login-id"]) && isset($post["post-id"]) && checkFieldValidity($post["post-id"]) && isset($post["reaction-id"]) && checkNumberValidity($post["reaction-id"]);
if ($condition) {
    $response = null;

    if ($c = new mysqli($localhost_db, $username_db, $password_db, $name_db)) {
        $c->set_charset("utf8mb4");

        global $logins_table, $users_table, $otps_table, $posts_table, $reactions_table, $reactions_posts_table;

        $login_id = $post["login-id"];
        $post_id = $post["post-id"];
        $reaction_id = $post["reaction-id"];
        $user_id = null;

        $action = null;

        $query_added_reactions = "SELECT `reactions-posts-added`.`post-id`, `reactions-posts-added`.`user-id`, `reactions-posts-added`.`reaction-id` FROM $reactions_posts_table AS `reactions-posts-added` INNER JOIN (SELECT `users`.`user-id` AS `user-id`, `users`.`status` AS `status` FROM $users_table AS `users` INNER JOIN (SELECT `logins`.`login-id` AS `login-id`, `logins`.`once-time` AS `once-time`, `logins`.`user-id` AS `user-id`, `otps`.`otp-id` AS `otp-id`, `otps`.`code` AS `code`, `otps`.`action` AS `action` FROM $logins_table AS `logins` INNER JOIN $otps_table AS `otps` ON `logins`.`otp-id` = `otps`.`otp-id` WHERE (`logins`.`valid-until` >= CURRENT_TIMESTAMP OR `logins`.`valid-until` IS NULL) AND (`logins`.`once-time` = 0) AND `logins`.`login-id` = ?) AS `logins-otps` ON `users`.`user-id` = `logins-otps`.`user-id` WHERE `users`.`status` = 1) AS `users` ON `reactions-posts-added`.`user-id` = `users`.`user-id` WHERE `post-id` = ? AND `reaction-id` = ?";
        $stmt_get_added_reactions = $c->prepare($query_added_reactions);
        $stmt_get_added_reactions->bind_param("sss", $login_id, $post_id, $reaction_id);

        try {
            $stmt_get_added_reactions->execute();
            $result = $stmt_get_added_reactions->get_result();


            if ($result->num_rows === 1) {
                //reaction already added

                //check if the "reaction-id" exists
                $query_check_reaction = "SELECT `reaction-id` FROM $reactions_table WHERE `reaction-id` = ?";
                $stmt_check_reaction = $c->prepare($query_check_reaction);
                $stmt_check_reaction->bind_param("s", $reaction_id);

                try {
                    $stmt_check_reaction->execute();

                    $result_check_reaction = $stmt_check_reaction->get_result();
                    if ($result_check_reaction->num_rows === 1) {
                        //reaction exists, get user id

                        //get user id from logins
                        $query_get_user_id = "SELECT `logins`.`user-id` AS `user-id` FROM $logins_table AS `logins` WHERE `logins`.`login-id` = ?";
                        $stmt_get_user_id = $c->prepare($query_get_user_id);
                        $stmt_get_user_id->bind_param("s", $login_id);
                        try {
                            $stmt_get_user_id->execute();
                            $result_get_user_id = $stmt_get_user_id->get_result();
                            if ($result_get_user_id->num_rows === 1) {
                                $row_user = $result_get_user_id->fetch_assoc();
                                $user_id = $row_user["user-id"];

                                //check if the "post-id" exists and it's not own post
                                $query_check_post = "SELECT `post-id` FROM $posts_table WHERE `post-id` = ? AND `visibility` = 0 AND `user-id` != ?";
                                $stmt_check_post = $c->prepare($query_check_post);
                                $stmt_check_post->bind_param("ss", $post_id, $user_id);
                                try {
                                    $stmt_check_post->execute();

                                    $result_check_post = $stmt_check_post->get_result();
                                    if ($result_check_post->num_rows === 1) {
                                        //post exists

                                        //remove reaction to post
                                        $query_remove_reaction = "DELETE FROM $reactions_posts_table WHERE `post-id` = ? AND `user-id` = ? AND `reaction-id` = ?";
                                        $stmt_remove_reaction = $c->prepare($query_remove_reaction);
                                        $stmt_remove_reaction->bind_param("ssi", $post_id, $user_id, $reaction_id);

                                        try {
                                            $stmt_remove_reaction->execute();

                                            responseSuccess(204, null, null);
                                        } catch (mysqli_sql_exception $e) {
                                            responseError(500, "Database error: " . $e->getMessage());
                                        }
                                        $stmt_remove_reaction->close();
                                    } else {
                                        //post doesn't exist, not visible to the user or own post
                                        responseError(404, "Post not found.");
                                    }
                                } catch (mysqli_sql_exception $e) {
                                    responseError(500, "Database error: " . $e->getMessage());
                                }
                                $stmt_check_post->close();
                            } else {
                                //user not found
                                responseError(404, "User not found.");
                            }
                        } catch (mysqli_sql_exception $e) {
                            responseError(500, "Database error: " . $e->getMessage());
                        }
                        $stmt_get_user_id->close();
                    } else {
                        //reaction doesn't exist
                        responseError(404, "Reaction not found.");
                    }
                } catch (mysqli_sql_exception $e) {
                    responseError(500, "Database error: " . $e->getMessage());
                }
                $stmt_check_reaction->close();
            } else {
                //reaction not added
                responseError(404, "Reaction not added.");
            }
        } catch (mysqli_sql_exception $e) {
            responseError(500, "Database error: " . $e->getMessage());
        }
        $stmt_get_added_reactions->close();

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
    if (!isset($post["post-id"]) || !checkFieldValidity($post["post-id"])) {
        array_push($missing_parameters, "post-id");
    }
    if (!isset($post["reaction-id"]) || !checkNumberValidity($post["reaction-id"])) {
        array_push($missing_parameters, "reaction-id");
    }
    responseError(400, "Missing or wrong parameters: " . implode(", ", $missing_parameters));
}

?>