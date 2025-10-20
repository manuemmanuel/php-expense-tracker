<?php
require_once 'db_connect.php';

// Destroy session and redirect to login
session_destroy();
header('Location: index.php');
exit();
?>
