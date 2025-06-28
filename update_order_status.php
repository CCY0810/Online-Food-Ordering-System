<?php
session_start();
require_once ("config.php"); 

if($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff') {
    header("Location: oder.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $orderID = $_POST['orderID'];
    $newStatus = $_POST['newStatus'];

    // Validate allowed transitions
    $validStatuses = ['Pending', 'Accepted', 'Preparing', 'Ready', 'Completed', 'Cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        die("Invalid status");
    }

    $stmt = $conn->prepare("UPDATE Orders SET orderStatus = ? WHERE orderID = ?");
    $stmt->bind_param("si", $newStatus, $orderID);

    if ($stmt->execute()) {
        echo "Order updated to $newStatus";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
