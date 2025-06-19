<?php

require_once("config.php");

$sql = "INSERT INTO Login (userID, password, userLevel)
VALUES ('ccy', 'ccy123', 1);";
$sql .= "INSERT INTO Login (userID, password, userLevel)
VALUES ('chunyi', 'chunyi123', 2);";
$sql .= "INSERT INTO Login (userID, password, userLevel)
VALUES ('junhong', 'junhong123', 3);";
$sql .= "INSERT INTO Login (userID, password, userLevel)
VALUES ('yitian', 'yitian123', 3);";
$sql .= "INSERT INTO Login (userID, password, userLevel)
VALUES ('nazmi', 'nazmi123', 3);";
$sql .= "INSERT INTO Login (userID, password, userLevel)
VALUES ('huiyi', 'huiyi123', 3)";

if (mysqli_multi_query($conn, $sql)) {
  echo "New records created successfully";
} else {
  echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}

mysqli_close($conn);
?>