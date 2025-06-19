<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
}

require_once("config.php");

// Get POST data
$itemID = $_POST['itemID'] ?? null;
$variation = $_POST['variation'] ?? '';
$quantity = intval($_POST['quantity'] ?? 1);
$userID = $_SESSION['user_id'];

// Validate input
if (!$itemID) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

// Check if item exists
$sql = "SELECT * FROM Menu WHERE itemID = '$itemID'";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit();
}

$item = mysqli_fetch_assoc($result);
$price = $item['itemPrice'] * $quantity;

// Check if item already in cart for this user
$sql = "SELECT * FROM Cart WHERE userID = '$userID' AND itemID = '$itemID' AND variation = '$variation'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    // Update existing cart item
    $row = mysqli_fetch_assoc($result);
    $newQuantity = $row['quantity'] + $quantity;
    $newPrice = $item['itemPrice'] * $newQuantity;
    
    $sql = "UPDATE Cart SET quantity = $newQuantity, price = $newPrice 
            WHERE cartID = {$row['cartID']}";
} else {
    // Insert new cart item
    $sql = "INSERT INTO Cart (userID, itemID, variation, quantity, price)
            VALUES ('$userID', '$itemID', '$variation', $quantity, $price)";
}

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>