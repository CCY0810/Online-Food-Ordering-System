<?php

require_once("config.php");

$sql = "CREATE TABLE IF NOT EXISTS Menu (
itemID VARCHAR(10) PRIMARY KEY,
userID VARCHAR(10),
category VARCHAR(15) NOT NULL,
itemName VARCHAR(20) NOT NULL,
itemPrice DECIMAL(10,2) NOT NULL,
availability INT(3) NOT NULL,
CONSTRAINT menu_fk_userID FOREIGN KEY (userID) REFERENCES User(userID)
)";

if (mysqli_query($conn, $sql)) {
  echo "Table Menu created successfully";
} else {
  echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>