<?php

require_once("config.php");

$sql = "CREATE TABLE IF NOT EXISTS OrderDetails (
orderDetailID INT AUTO_INCREMENT PRIMARY KEY,
orderID INT NOT NULL,
itemID VARCHAR(10) NOT NULL,
variation VARCHAR(50) NOT NULL,
quantity INT NOT NULL,
price DECIMAL(10,2) NOT NULL,
CONSTRAINT orderDetails_fk_orderID FOREIGN KEY (orderID) REFERENCES Orders(orderID),
CONSTRAINT orderDetails_fk_itemID FOREIGN KEY (itemID) REFERENCES Menu(itemID)
)";

if (mysqli_query($conn, $sql)) {
  echo "Table OrderDetails created successfully";
} else {
  echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>