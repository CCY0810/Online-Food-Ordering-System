<?php

require_once("config.php");

// //update category id in menu
// $sql ="UPDATE Menu SET categoryID=001 WHERE category='food' ; ";
// $sql .="UPDATE Menu SET categoryID=002 WHERE category='beverage' ; ";

// if (mysqli_multi_query($conn, $sql)) {
//   echo "Insert successfully";
// } else {
//   echo "Error: " . $sql . "<br>" . mysqli_error($conn);
// }

// //insert value into category
// $sql ="INSERT INTO Category(categoryName, categoryDescription)
// VALUES ('food', 'Variety of delicious food');";

// $sql ="INSERT INTO Category(categoryName, categoryDescription)
// VALUES ('beverage', 'Different type of drinks');";

// if (mysqli_multi_query($conn, $sql)) {
//   echo "Insert successfully";
// } else {
//   echo "Error: " . $sql . "<br>" . mysqli_error($conn);
// }


// Insert categories if not already present
mysqli_query($conn, "INSERT IGNORE INTO Category (categoryName, categoryDescription) VALUES ('food', 'Variety of delicious food')");
mysqli_query($conn, "INSERT IGNORE INTO Category (categoryName, categoryDescription) VALUES ('beverage', 'Different type of drinks')");

// Get the actual category IDs
$foodResult = mysqli_query($conn, "SELECT categoryID FROM Category WHERE categoryName='food'");
$beverageResult = mysqli_query($conn, "SELECT categoryID FROM Category WHERE categoryName='beverage'");
$foodID = mysqli_fetch_assoc($foodResult)['categoryID'];
$beverageID = mysqli_fetch_assoc($beverageResult)['categoryID'];

// Update Menu table with correct category IDs
mysqli_query($conn, "UPDATE Menu SET categoryID=$foodID WHERE category='food'");
mysqli_query($conn, "UPDATE Menu SET categoryID=$beverageID WHERE category='beverage'");

echo "Categories inserted and Menu updated successfully.";



mysqli_close($conn);

?>
