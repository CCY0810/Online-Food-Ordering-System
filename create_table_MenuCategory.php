<?php

require_once("config.php");

$sql = "CREATE TABLE Category (
    categoryID INT AUTO_INCREMENT PRIMARY KEY,
    categoryName VARCHAR(100) NOT NULL UNIQUE,
    categoryDescription TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table Category created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

// Add categoryID to Menu and set up foreign key
$sql = "ALTER TABLE Menu
ADD COLUMN categoryID INT,
ADD FOREIGN KEY (categoryID) REFERENCES Category(categoryID)
";

if (mysqli_query($conn, $sql)) {
    echo "Menu table altered successfully";
} else {
    echo "Error altering Menu table: " . mysqli_error($conn);
}


mysqli_close($conn);
?>
