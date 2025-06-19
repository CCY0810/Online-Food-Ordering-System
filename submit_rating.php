<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once("config.php");

$orderID = $_POST['orderID'] ?? null;
$rating = $_POST['ratingValue'] ?? null;

if (!$orderID || !$rating) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit();
}

$userID = $_SESSION['user_id'];

// Verify order belongs to user and is ready for rating
$checkQuery = "SELECT orderID FROM Orders 
               WHERE orderID = ? AND userID = ? AND orderStatus = 'Completed'";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("is", $orderID, $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not eligible for rating']);
    exit();
}

// Update rating
$updateQuery = "UPDATE Orders SET rating = ? WHERE orderID = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("ii", $rating, $orderID);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$updateStmt->close();
$conn->close();
?>