<?php
session_start();
require_once("config.php");

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = mysqli_real_escape_string($conn, $_POST['userID']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $sql = "SELECT Login.userID, Login.password, User.role 
            FROM Login 
            INNER JOIN User ON Login.userID = User.userID 
            WHERE Login.userID = '$userID' 
            AND User.role = '$role'";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {

        while($row = mysqli_fetch_assoc($result))
        {
            if ($password === $row['password']) {
                $_SESSION['user_id'] = $row['userID'];
                $_SESSION['user_role'] = $row['role'];
                $response['success'] = true;
            } 
            else {
                $response['message'] = 'Invalid password';
            }
        }   
    } 
    else {
        $response['message'] = 'User not found or role mismatch';
    }
} 
else {
    $response['message'] = 'Invalid request method';
}

mysqli_close($conn);
header('Content-Type: application/json');
echo json_encode($response);
?>