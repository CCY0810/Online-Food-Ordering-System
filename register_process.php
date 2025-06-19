<?php
session_start();
require_once("config.php");

$_SESSION['old_name'] = $_POST['name'] ?? '';
$_SESSION['old_age'] = $_POST['age'] ?? '';
$_SESSION['old_email'] = $_POST['email'] ?? '';
$_SESSION['old_contact'] = $_POST['contact'] ?? '';
$_SESSION['old_address'] = $_POST['address'] ?? '';
$_SESSION['old_userID'] = $_POST['userID'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = mysqli_real_escape_string($conn, $_POST['userID']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $age = intval($_POST['age']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirmPassword']);
    $role = 'customer'; 
    
    // Validate passwords match
    if ($password !== $confirmPassword) {
        $_SESSION['registration_error'] = 'Passwords do not match!';
        header("Location: register.php");
        exit();
    }
    
    // Check if user ID already exists
    $checkUser = mysqli_query($conn, "SELECT * FROM User WHERE userID = '$userID'");
    if (mysqli_num_rows($checkUser) > 0) {
        $_SESSION['registration_error'] = 'User ID already exists. Please choose a different one.';
        header("Location: register.php");
        exit();
    }
    
    // Check if email already exists
    $checkEmail = mysqli_query($conn, "SELECT * FROM User WHERE email = '$email'");
    if (mysqli_num_rows($checkEmail) > 0) {
        $_SESSION['registration_error'] = 'Email address already registered.';
        header("Location: register.php");
        exit();
    }
    
    // Check if contact number already exists
    $checkContact = mysqli_query($conn, "SELECT * FROM User WHERE contactNumber = '$contact'");
    if (mysqli_num_rows($checkContact) > 0) {
        $_SESSION['registration_error'] = 'Contact number already registered.';
        header("Location: register.php");
        exit();
    }
    
    // Insert into User table
    $userSql = "INSERT INTO User (userID, role, name, age, email, contactNumber, address)
                VALUES ('$userID', '$role', '$name', $age, '$email', '$contact', '$address')";
    
    if (mysqli_query($conn, $userSql)) {
        // Insert into Login table
        $loginSql = "INSERT INTO Login (userID, password, userLevel)
                     VALUES ('$userID', '$password', 3)";
        
        if (mysqli_query($conn, $loginSql)) {
            // Clear old form values
            unset($_SESSION['old_name']);
            unset($_SESSION['old_age']);
            unset($_SESSION['old_email']);
            unset($_SESSION['old_contact']);
            unset($_SESSION['old_address']);
            unset($_SESSION['old_userID']);
            
            // Auto-login after successful registration
            $_SESSION['user_id'] = $userID;
            $_SESSION['user_role'] = $role;
            
            // Redirect to main page
            header("Location: mainPage.php");
            exit();
        } 
        else {
            $_SESSION['registration_error'] = 'Error creating login credentials: ' . mysqli_error($conn);
            header("Location: register.php");
            exit();
        }
    } 
    else {
        $_SESSION['registration_error'] = 'Error creating user account: ' . mysqli_error($conn);
        header("Location: register.php");
        exit();
    }
} 
else {
    header("Location: register.php");
    exit();
}
?>