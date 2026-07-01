<?php

session_start();

$_SESSION = array();

session_destroy();

header("Location: admin_dashboard.php");
exit();
?>