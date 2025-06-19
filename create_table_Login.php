<?php

require_once("config.php");

$sql = "CREATE TABLE IF NOT EXISTS Login (
userID VARCHAR(10) UNIQUE,
password VARCHAR(20) NOT NULL,
userLevel INT(1) NOT NULL,
CONSTRAINT login_fk_userID FOREIGN KEY (userID) REFERENCES User(userID)
)";

if (mysqli_query($conn, $sql)) {
  echo "Table Login created successfully";
} else {
  echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>