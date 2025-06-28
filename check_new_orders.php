<?php
require_once "config.php"; 

$since = isset($_GET['since']) ? intval($_GET['since']) : 0;
$sinceDate = date('Y-m-d H:i:s', $since / 1000); 

// Check if any new orders have been placed since that time
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM Orders WHERE orderTime > ?");
$stmt->bind_param("s", $sinceDate);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['newOrder' => $row['count'] > 0]);
?>
