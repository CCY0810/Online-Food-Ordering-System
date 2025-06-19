<?php

require_once("config.php");

$sql = "INSERT INTO User (userID, role, name, age, email, contactNumber, address)
VALUES ('ccy', 'admin', 'CCY', 22, 'ccyi2003@gmail.com', '011-3456789', '123, UTM');";
$sql .= "INSERT INTO User (userID, role, name, age, email, contactNumber, address)
VALUES ('chunyi', 'staff', 'Chuah Chun Yi', 22, 'chuahchunyi@graduate.utm.my', '012-3456789', '456, UTM');";
$sql .= "INSERT INTO User (userID, role, name, age, email, contactNumber, address)
VALUES ('junhong', 'customer', 'Chong Jun Hong', 22, 'chongjunhong@graduate.utm.my', '013-3456789', '789, UTM');";
$sql .= "INSERT INTO User (userID, role, name, age, email, contactNumber, address)
VALUES ('yitian', 'customer', 'Tai Yi Tian', 21, 'taitian@graduate.utm.my', '015-3456789', '111, UTM');";
$sql .= "INSERT INTO User (userID, role, name, age, email, contactNumber, address)
VALUES ('nazmi', 'customer', 'Nazmi Haikal Bin Khairul', 21, 'nazmihaikal@graduate.utm.my', '017-3456789', '222, UTM');";
$sql .= "INSERT INTO User (userID, role, name, age, email, contactNumber, address)
VALUES ('huiyi', 'customer', 'Loh Hui Yi', '21', 'lohhuiyi@graduate.utm.my', '019-3456789', '333, UTM')";

if (mysqli_multi_query($conn, $sql)) {
  echo "New records created successfully";
} else {
  echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}

mysqli_close($conn);
?>