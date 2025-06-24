<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once("config.php");

$userRole = $_SESSION['user_role'];

if ($userRole == 'admin'  || $userRole == 'staff') {
    header("Location: order_list.php");
    exit();
} elseif ($userRole == 'customer') {
    header("Location: order.php");
    exit();
} else {
    echo "Invalid user role.";
}
?>
