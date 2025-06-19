<?php

require_once("config.php");

$sql = "CREATE TABLE IF NOT EXISTS Orders (
orderID INT AUTO_INCREMENT PRIMARY KEY,
userID VARCHAR(10) NOT NULL,
orderTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
orderStatus VARCHAR(20) NOT NULL,
total DECIMAL(10,2) NOT NULL,
paymentMethod VARCHAR(30) NOT NULL,
rating INT DEFAULT 0,
CONSTRAINT orders_fk_userID FOREIGN KEY (userID) REFERENCES User(userID)
)";

if (mysqli_query($conn, $sql)) {
  echo "Table Orders created successfully";
} else {
  echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>