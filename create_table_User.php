<?php

require_once("config.php");

$sql = "CREATE TABLE IF NOT EXISTS User (
userID VARCHAR(10) PRIMARY KEY,
role VARCHAR(10) NOT NULL,
name VARCHAR(30) NOT NULL,
age INT(3) NOT NULL,
email VARCHAR(30) UNIQUE NOT NULL,
contactNumber VARCHAR(20) UNIQUE NOT NULL,
address VARCHAR(30) NOT NULL,
profileImage VARCHAR(255) DEFAULT 'assets/user-pic.png'
)";

if (mysqli_query($conn, $sql)) {
  echo "Table User created successfully";
} else {
  echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>