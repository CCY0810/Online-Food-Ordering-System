<?php

require_once("config.php");

$sql = "INSERT INTO Menu (itemID, userID, category, itemName, itemPrice, availability)
VALUES ('F001', 'ccy', 'food', 'Steak', 30.00, 99);";
$sql .= "INSERT INTO Menu (itemID, userID, category, itemName, itemPrice, availability)
VALUES ('F002', 'ccy', 'food', 'Spaghetti', 15.00, 99);";
$sql .= "INSERT INTO Menu (itemID, userID, category, itemName, itemPrice, availability)
VALUES ('F003', 'ccy', 'food', 'Burger', 15.00, 99);";
$sql .= "INSERT INTO Menu (itemID, userID, category, itemName, itemPrice, availability)
VALUES ('B001', 'ccy', 'beverage', 'Orange Juice', 8.00, 99);";
$sql .= "INSERT INTO Menu (itemID, userID, category, itemName, itemPrice, availability)
VALUES ('B002', 'ccy', 'beverage', 'Coffee', 8.00, 99);";
$sql .= "INSERT INTO Menu (itemID, userID, category, itemName, itemPrice, availability)
VALUES ('B003', 'ccy', 'beverage', 'Tea', 8.00, 99)";

if (mysqli_multi_query($conn, $sql)) {
  echo "New records created successfully";
} else {
  echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}

mysqli_close($conn);
?>