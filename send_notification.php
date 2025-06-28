<?php
session_start();
require_once ("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderID = $_POST['orderID'];
    $message = trim($_POST['message']);

    if (!empty($orderID) && !empty($message)) {
        // Prepare and execute the insert
        $stmt = $conn->prepare("INSERT INTO OrderNotifications (orderID, message) VALUES (?, ?)");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("is", $orderID, $message);
        if ($stmt->execute()) {
            // Success
            header("Location: order_list.php?notify=sent");
            exit();
        } else {
            // SQL error
            die("Execute failed: " . $stmt->error);
        }
    } else {
        die("Order ID or message is empty.");
    }
} else {
    die("Invalid request.");
}
