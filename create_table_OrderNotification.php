<?php

require_once("config.php");

$sql = "CREATE TABLE IF NOT EXISTS OrderNotifications (
notificationID INT AUTO_INCREMENT PRIMARY KEY,
orderID INT NOT NULL,
message TEXT NOT NULL,
createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (orderID) REFERENCES Orders(orderID)
)";

if (mysqli_query($conn, $sql)) {
  echo "Table OrderDetails created successfully";
} else {
  echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
