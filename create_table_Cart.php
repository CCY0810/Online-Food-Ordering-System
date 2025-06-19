<?php

require_once("config.php");

$sql = "CREATE TABLE IF NOT EXISTS Cart (
cartID INT AUTO_INCREMENT PRIMARY KEY,
userID VARCHAR(10) NOT NULL,
itemID VARCHAR(10) NOT NULL,
variation VARCHAR(50) NOT NULL,
quantity INT NOT NULL,
price DECIMAL(10,2) NOT NULL,
CONSTRAINT cart_fk_userID FOREIGN KEY (userID) REFERENCES User(userID),
CONSTRAINT cart_fk_itemID FOREIGN KEY (itemID) REFERENCES Menu(itemID)
)";

if (mysqli_query($conn, $sql)) {
  echo "Table User created successfully";
} 
else {
  echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>