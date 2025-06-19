<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cartID'])) {
    $cartID = $_POST['cartID'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : null;
    $variation = $_POST['variation'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    // Validate quantity
    if ($quantity !== null && $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
        exit();
    }
    
    // Get current cart item
    $sql = "SELECT c.*, m.itemPrice 
            FROM Cart c
            JOIN Menu m ON c.itemID = m.itemID
            WHERE c.cartID = '$cartID' AND c.userID = '$user_id'";
    
    $result = mysqli_query($conn, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Cart item not found']);
        exit();
    }
    
    $cartItem = mysqli_fetch_assoc($result);
    $newQuantity = $quantity ?? $cartItem['quantity'];
    $newVariation = $variation ?? $cartItem['variation'];
    $newPrice = $newQuantity * $cartItem['itemPrice'];
    
    // Update the cart item
    $sql = "UPDATE Cart 
            SET 
            quantity = $newQuantity, 
            variation = '$newVariation',
            price = $newPrice
            WHERE cartID = '$cartID'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'newPrice' => number_format($newPrice, 2)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating item: ' . mysqli_error($conn)]);
    }
    
    mysqli_close($conn);
} 
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>