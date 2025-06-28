<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

require_once("config.php");

$userID = $_SESSION['user_id'];

$userQuery = "SELECT * FROM User WHERE userID = '$userID'";
$userResult = mysqli_query($conn, $userQuery);
$user = mysqli_fetch_assoc($userResult);

$loginQuery = "SELECT * FROM Login WHERE userID = '$userID'";
$loginResult = mysqli_query($conn, $loginQuery);
$login = mysqli_fetch_assoc($loginResult);

mysqli_close($conn);

$fullName = htmlspecialchars($user['name']);
$username = htmlspecialchars($user['userID']);
$email = htmlspecialchars($user['email']);
$age = htmlspecialchars($user['age']);
$phone = htmlspecialchars($user['contactNumber']);
$address = htmlspecialchars($user['address']);
$role = htmlspecialchars($user['role']);
$password = htmlspecialchars($login['password']);
$profileImage = htmlspecialchars($user['profileImage']);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Profile</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                padding-top: 70px;
                font-family: 'Segoe UI', Arial, sans-serif;
                background-color: #f5f7fa;
            }
            
            header {
                height: 70px;
            }
            
            nav > a {
                position: relative;
                color: white;
                text-decoration: none;
                font-weight: 500;
            }
            
            nav > a::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 0;
                height: 2px;
                background-color: white;
                transition: width 0.3s ease;
            }
            
            nav > a:hover::after {
                width: 100%;
            }
            
            .header-link {
                color: white;
                text-decoration: none;
                font-weight: 500;
            }
            
            .header-link img {
                transition: transform 0.3s ease;
            }
            
            .header-link:hover img {
                transform: scale(1.2);
            }
            
            .profile-photo-container {
                width: 200px;
                height: 200px;
                margin: 0 auto 20px;
            }
            
            .profile-photo {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .editable-field {
                display: none;
            }
            
            body {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }
            
            main {
                flex: 1;
            }
            
            .button-container {
                display: flex;
                justify-content: center;
                gap: 10px;
                margin-top: 20px;
            }
        </style>
    </head>

    <body class="d-flex flex-column min-vh-100 bg-light">
        <header class="container-fluid bg-dark fixed-top shadow-sm d-flex justify-content-between align-items-center px-4"
            style="height: 70px;">
            <div class="text-white fs-4 fw-bold">CC Food Ordering System</div>
            <nav class="d-flex align-items-center gap-3 gap-lg-5">
                <a href="mainPage.php" class="text-white text-decoration-none fw-medium position-relative">Home</a>
                <?php if($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'staff') { ?>
                    <a href="admin_manage_menu.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                        Menu</a>
                <?php } ?>
                <?php if ($isAdmin): ?>
                    <a href="admin_manage_user.php" class="text-white text-decoration-none fw-medium position-relative">Manage
                        User</a>
                    <a href="admin_sales_report.php" class="text-white text-decoration-none fw-medium position-relative">Sales
                        Report</a>
                    <a href="admin_feedback.php"
                        class="text-white text-decoration-none fw-medium position-relative">Feedback</a>
                <?php endif; ?>
                <?php if (!$isAdmin): ?>
                    <a href="menu.php" class="text-white text-decoration-none fw-medium position-relative">Menu</a>    
                    <a href="redirect_orders.php" class="text-white text-decoration-none fw-medium position-relative">Order</a>
                    <div class="d-flex align-items-center gap-4 ms-3">
                        <a href="cart.php" class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2">
                            <img src="assets/cart1.png" alt="Shopping Cart" class="img-fluid" style="width: 24px; height: 24px;">
                            <span class="d-none d-sm-inline">CART</span>
                        </a>
                <?php endif; ?>
                        <div class="dropdown">
                            <a href="#" class="header-link text-white text-decoration-none fw-medium d-flex align-items-center gap-2 dropdown-toggle"
                            id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="assets/user2.png" alt="Profile" class="img-fluid" style="width: 24px; height: 24px;">
                                <span class="d-none d-sm-inline">Profile</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
            </nav>
        </header>
        
        <main class="py-4">
            <div class="container">
                <div class="row g-4">
                    <!-- User Photo Section -->
                    <div class="col-md-4">
                        <div class="bg-white rounded-4 shadow-sm p-4 text-center h-100">
                            <div class="profile-photo-container rounded-circle overflow-hidden bg-light">
                                <img src="<?php echo $profileImage; ?>" alt="Profile Photo" class="profile-photo" id="profilePhoto">
                                <input type="hidden" id="currentProfileImage" value="<?php echo $profileImage; ?>">
                            </div>
                            <h2 class="mt-3 mb-1" id="userNameDisplay"><?php echo $fullName; ?></h2>
                            <div class="text-muted mb-3"><?php echo ucfirst($role); ?></div>
                            <input type="file" id="photoUpload" accept="image/*" class="d-none">
                            <button class="btn btn-dark px-4 rounded-pill" onclick="document.getElementById('photoUpload').click()">Upload Photo</button>
                        </div>
                    </div>

                    <!-- Profile Info Section -->
                    <div class="col-md-8">
                        <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                            <h2 class="mb-4 pb-2 border-bottom">Information</h2>
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold">Full Name:</label>
                                    <div>
                                        <span class="value-text" id="fullNameText"><?php echo $fullName; ?></span>
                                        <input type="text" class="form-control editable-field" id="fullNameField" value="<?php echo $fullName; ?>">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold">Username:</label>
                                    <div>
                                        <span class="value-text" id="usernameText"><?php echo $username; ?></span>
                                        <input type="text" class="form-control editable-field" id="usernameField" value="<?php echo $username; ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold">Password:</label>
                                    <div>
                                        <span class="value-text" id="passwordText">••••••••</span>
                                        <input type="hidden" id="realPassword" value="<?php echo $password; ?>">
                                        <input type="password" class="form-control editable-field" id="passwordField" value="<?php echo $password; ?>">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold">Email:</label>
                                    <div>
                                        <span class="value-text" id="emailText"><?php echo $email; ?></span>
                                        <input type="email" class="form-control editable-field" id="emailField" value="<?php echo $email; ?>">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold">Age:</label>
                                    <div>
                                        <span class="value-text" id="ageText"><?php echo $age; ?></span>
                                        <input type="number" class="form-control editable-field" id="ageField" value="<?php echo $age; ?>">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-semibold">Phone Number:</label>
                                    <div>
                                        <span class="value-text" id="phoneText"><?php echo $phone; ?></span>
                                        <input type="tel" class="form-control editable-field" id="phoneField" value="<?php echo $phone; ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Address:</label>
                                    <div>
                                        <span class="value-text" id="addressText"><?php echo $address; ?></span>
                                        <input type="text" class="form-control editable-field" id="addressField" value="<?php echo $address; ?>">
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-dark px-4 rounded-pill" id="editButton">Edit Profile</button>                                
                                        <button class="btn btn-success px-4 rounded-pill" id="saveButton" style="display:none">Save Changes</button>
                                        <button class="btn btn-outline-secondary px-4 rounded-pill" id="cancelButton" style="display:none">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-dark text-white py-3 text-center mt-auto">
           <script src="script/footer.js" type="text/javascript"></script>
        </footer>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Profile Edit Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const editButton = document.getElementById('editButton');
                const editControls = document.getElementById('editControls');
                const saveButton = document.getElementById('saveButton');
                const cancelButton = document.getElementById('cancelButton');
                const editableFields = document.querySelectorAll('.editable-field');
                const valueTexts = document.querySelectorAll('.value-text');
                
                function toggleEditMode(enable) {
                    if (enable) {
                        // Enter edit mode
                        editableFields.forEach(field => field.style.display = 'block');
                        valueTexts.forEach(text => text.style.display = 'none');
                        editButton.style.display = 'none';
                        saveButton.style.display = 'flex';
                        cancelButton.style.display = 'flex';
                    } else {
                        // Exit edit mode
                        editableFields.forEach(field => field.style.display = 'none');
                        valueTexts.forEach(text => text.style.display = 'block');
                        editButton.style.display = 'block';
                        saveButton.style.display = 'none';
                        cancelButton.style.display = 'none';
                    }
                }

                // Edit button click
                editButton.addEventListener('click', function() {
                    toggleEditMode(true);
                });
                
                // Cancel button click
                cancelButton.addEventListener('click', function() {
                    toggleEditMode(false);
                    // Reset values to original (optional)
                    editableFields.forEach(field => {
                        const id = field.id;
                        if (id !== 'usernameField') {
                            const originalId = id.replace('Field', 'Text');
                            field.value = document.getElementById(originalId).textContent;
                        }
                    });
                });
                
                // Save button click
                saveButton.addEventListener('click', function() {
                    const formData = new FormData();
                    formData.append('fullName', document.getElementById('fullNameField').value);
                    formData.append('password', document.getElementById('passwordField').value);
                    formData.append('email', document.getElementById('emailField').value);
                    formData.append('age', document.getElementById('ageField').value);
                    formData.append('phone', document.getElementById('phoneField').value);
                    formData.append('address', document.getElementById('addressField').value);
                    
                    fetch('edit_profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('fullNameText').textContent = document.getElementById('fullNameField').value;
                            document.getElementById('emailText').textContent = document.getElementById('emailField').value;
                            document.getElementById('ageText').textContent = document.getElementById('ageField').value;
                            document.getElementById('phoneText').textContent = document.getElementById('phoneField').value;
                            document.getElementById('addressText').textContent = document.getElementById('addressField').value;
                            
                            document.getElementById('userNameDisplay').textContent = document.getElementById('fullNameField').value;
                            
                            // Exit edit mode
                            toggleEditMode(false);
                            
                            alert('Profile updated successfully!');
                        } 
                        else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });

                // Upload profile photo 
                document.getElementById('photoUpload').addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const formData = new FormData();
                        formData.append('profileImage', this.files[0]);
                        formData.append('currentImage', document.getElementById('currentProfileImage').value);

                        fetch('upload_profile_image.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update image preview
                                document.getElementById('profilePhoto').src = data.filePath;
                                // Update current image path
                                document.getElementById('currentProfileImage').value = data.filePath;
                                alert('Profile image updated successfully!');
                            } 
                            else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while uploading the image.');
                        });
                    }
                });
            });
        </script>
    </body>
</html>