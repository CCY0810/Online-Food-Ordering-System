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

if (empty($_FILES['profileImage']) || $_FILES['profileImage']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No file uploaded or upload error';
    echo json_encode($response);
    exit();
}

$file = $_FILES['profileImage'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    $response['message'] = 'Invalid file type. Only JPEG, PNG, and GIF are allowed.';
    echo json_encode($response);
    exit();
}

// Generate filename to store the user's profile image
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFilename = $userID . '_' . time() . '.' . $ext;
$targetDir = 'assets/profile_images/';
$targetPath = $targetDir . $newFilename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Update database
    $updateQuery = "UPDATE User SET profileImage = '$targetPath' WHERE userID = '$userID'";
    if (mysqli_query($conn, $updateQuery)) {
        $response['success'] = true;
        $response['filePath'] = $targetPath;
        
        // Delete old image if not default
        $currentImage = mysqli_real_escape_string($conn, $_POST['currentImage']);
        if ($currentImage !== 'assets/user-pic.png' && file_exists($currentImage)) {
            unlink($currentImage);
        }
    } 
    else {
        $response['message'] = 'Database update failed: ' . mysqli_error($conn);
    }
} 
else {
    $response['message'] = 'Failed to save uploaded file.';
}

mysqli_close($conn);
echo json_encode($response);
?>