<?php
$db = new mysqli($db_host, $db_user, $db_pass, $db_database);
if ($db->connect_errno) {
    die('Failed to connect to MySQL: (' . $db->connect_errno . ') ' . $db->connect_error);
}
?>
