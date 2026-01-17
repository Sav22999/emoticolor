<?php
//the following credentials are used for: mysqli(localhost, username, password, database)
include_once($_SERVER['DOCUMENT_ROOT'] . "/api/emoticolor/api-functions.php");

$email = $_GET['username'] ?? ``;
echo checkUsernameValidity($email) !== null ? 'true' : 'false';
?>