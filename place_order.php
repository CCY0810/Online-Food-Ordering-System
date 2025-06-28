<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once("config.php");

// Get payment method from POST
$paymentMethod = $_POST['paymentMethod'] ?? null;

if (!$paymentMethod) {
    echo json_encode(['success' => false, 'message' => 'Payment method not provided']);
    exit();
}

$userID = $_SESSION['user_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Step 1: Calculate grand total
    $cartQuery = "SELECT SUM(c.quantity * m.itemPrice) AS grandTotal
                  FROM Cart c
                  JOIN Menu m ON c.itemID = m.itemID
                  WHERE c.userID = '$userID'";
    $result = mysqli_query($conn, $cartQuery);
    $totalRow = mysqli_fetch_assoc($result);
    $grandTotal = $totalRow['grandTotal'] ?? 0;

    if ($grandTotal <= 0) {
        throw new Exception("Your cart is empty");
    }

    // Step 2: Insert into Orders table
    $orderStatus = 'Pending'; // initial status
    $insertOrder = "INSERT INTO Orders (userID, orderStatus, total, paymentMethod)
                    VALUES ('$userID', '$orderStatus', $grandTotal, '$paymentMethod')";
    
    if (!mysqli_query($conn, $insertOrder)) {
        throw new Exception("Error creating order: " . mysqli_error($conn));
    }
    
    $orderID = mysqli_insert_id($conn);

    // Step 3: Move cart items to OrderDetails
    $cartItemsQuery = "SELECT c.itemID, c.variation, c.quantity, m.itemPrice
                       FROM Cart c
                       JOIN Menu m ON c.itemID = m.itemID
                       WHERE c.userID = '$userID'";
    $cartItems = mysqli_query($conn, $cartItemsQuery);
    
    while ($item = mysqli_fetch_assoc($cartItems)) {
        $itemID = $item['itemID'];
        $variation = mysqli_real_escape_string($conn, $item['variation']);
        $quantity = $item['quantity'];
        $price = $item['itemPrice'] * $quantity;
        
        $insertDetail = "INSERT INTO OrderDetails (orderID, itemID, variation, quantity, price)
                         VALUES ($orderID, '$itemID', '$variation', $quantity, $price)";
        
        if (!mysqli_query($conn, $insertDetail)) {
            throw new Exception("Error adding order details: " . mysqli_error($conn));
        }
    }
    
    // Step 4: Clear the cart
    $clearCart = "DELETE FROM Cart WHERE userID = '$userID'";
    if (!mysqli_query($conn, $clearCart)) {
        throw new Exception("Error clearing cart: " . mysqli_error($conn));
    }

    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'orderID' => $orderID,
        'message' => 'Order placed successfully! Redirecting to orders...'
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    mysqli_close($conn);
}
?>