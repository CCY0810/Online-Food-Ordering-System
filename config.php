<?php
// Connect to MySQL database

    $db_host='localhost';
    $db_user='root';
    $db_pass='';
    $db_name='db_food_ordering';

    // login to MySQL Server from PHP
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

?>