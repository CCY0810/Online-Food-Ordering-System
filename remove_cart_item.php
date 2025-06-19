<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
}

require_once("config.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cartID'])) {
    $cartID = $_POST['cartID'];
    $user_id = $_SESSION['user_id'];
    
    // Delete the cart item
    $sql = "DELETE FROM Cart WHERE cartID = '$cartID' AND userID = '$user_id'";
    
    if (mysqli_query($conn, $sql)) {
        if (mysqli_affected_rows($conn) > 0) {
            echo json_encode(['success' => true]);
        } 
        else {
            echo json_encode(['success' => false, 'message' => 'Item not found or already removed']);
        }
    } 
    else {
        echo json_encode(['success' => false, 'message' => 'Error deleting item: ' . mysqli_error($conn)]);
    }
    
    mysqli_close($conn);
} 
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>