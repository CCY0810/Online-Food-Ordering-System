<?php
session_start();
require_once("config.php");

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Not logged in';
    echo json_encode($response);
    exit();
}

$userID = $_SESSION['user_id'];

$fullName = mysqli_real_escape_string($conn, $_POST['fullName']);
$password = mysqli_real_escape_string($conn, $_POST['password']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$age = intval($_POST['age']);
$phone = mysqli_real_escape_string($conn, $_POST['phone']);
$address = mysqli_real_escape_string($conn, $_POST['address']);

// Check if email already exists
$emailCheck = mysqli_query($conn, "SELECT userID FROM User WHERE email = '$email' AND userID != '$userID'");
if (mysqli_num_rows($emailCheck) > 0) {
    $response['message'] = 'Email already exists';
    echo json_encode($response);
    exit();
}

// Check if contact number already exists
$phoneCheck = mysqli_query($conn, "SELECT userID FROM User WHERE contactNumber = '$phone' AND userID != '$userID'");
if (mysqli_num_rows($phoneCheck) > 0) {
    $response['message'] = 'Contact number already exists';
    echo json_encode($response);
    exit();
}

// Update User table
$userUpdate = "UPDATE User 
               SET name = '$fullName', 
                   email = '$email', 
                   age = $age, 
                   contactNumber = '$phone', 
                   address = '$address' 
               WHERE userID = '$userID'";

if (mysqli_query($conn, $userUpdate)) {
    // Update Login table
    $loginUpdate = "UPDATE Login 
                    SET password = '$password' 
                    WHERE userID = '$userID'";
    
    if (mysqli_query($conn, $loginUpdate)) {
        $response['success'] = true;
    } 
    else {
        $response['message'] = 'Password update failed: ' . mysqli_error($conn);
    }
} 
else {
    $response['message'] = 'Profile update failed: ' . mysqli_error($conn);
}

mysqli_close($conn);
echo json_encode($response);
?>