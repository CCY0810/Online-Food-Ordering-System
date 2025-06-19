<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: mainPage.php");
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .main-structure {
                display: grid;
                grid-template-rows: auto 1fr auto;
                min-height: 100vh;
            }
            
            header, footer {
                background-color: #2c3e50;
            }
            
            main {
                background: url("assets/login-bg3.jpeg") center/cover no-repeat;
            }
            
            .login-container {
                background-color: rgba(255, 255, 255, 0.95);
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .role-box {
                margin: 5px;
                width: 100px;
                background-color: #3498db;
                color: white;
                border-radius: 25px;
                cursor: pointer;
                transition: all 0.3s;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .role-box:hover {
                background-color: #2980b9;
                transform: translateY(-2px);
            }
            
            .role-box.active {
                background-color: #1a5276;
                transform: scale(1.05);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .login-button {
                background-color: #3498db;
                border-radius: 20px;
                transition: all 0.3s;
            }
            
            .login-button:hover {
                background-color: #2980b9;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .login-button:active {
                background-color: #1a5276;
                transform: scale(0.98);
            }
            
            /* Added smaller label size */
            .input-label {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }
        </style>
    </head>

    <body>
        <div class="main-structure">
            <header class="bg-dark d-flex justify-content-center align-items-center py-3 shadow-sm">
                <h1 class="system-title text-white m-0 fs-2 fw-semibold">CC Food Ordering System</h1>
            </header>

            <main class="d-flex justify-content-center align-items-center p-4">
                <div class="login-container p-5 w-100" style="max-width: 400px">
                    <div id="error-message" class="alert alert-danger d-none"></div>

                    <form id="loginForm">
                        <div class="role-section text-center mb-4">
                            <div class="role-title mb-3 fs-5 fw-medium text-dark">Select Your Role</div>
                            <div class="role-options d-flex justify-content-around">
                                <div class="role-box d-flex justify-content-center align-items-center py-2" data-role="admin" onclick="selectRole(this)">Admin</div>
                                <div class="role-box d-flex justify-content-center align-items-center py-2" data-role="staff" onclick="selectRole(this)">Staff</div>
                                <div class="role-box d-flex justify-content-center align-items-center py-2" data-role="customer" onclick="selectRole(this)">Customer</div>
                            </div>
                            <input type="hidden" id="selectedRole" name="role">
                        </div>
                    
                        <div class="input-section mb-3">
                            <h3 class="input-label text-center text-dark fw-medium">User ID</h3>
                            <input type="text" id="userID" name="userID" class="form-control py-2 px-3" placeholder="Enter your user ID" required>
                        </div>
                        
                        <div class="input-section mb-4">
                            <h3 class="input-label text-center text-dark fw-medium">Password</h3>
                            <input type="password" id="password" name="password" class="form-control py-2 px-3" placeholder="Enter your password" required>
                        </div>
                        
                        <button type="submit" class="login-button btn btn-primary w-100 py-3">Sign In</button>
                        <div class="register-link mt-3">
                            New customer? <a href="register.php">Register Here</a>
                        </div>
                    </form>
                </div>
            </main>

            <footer class="bg-dark text-white py-3 text-center mt-auto">
                <script src="script/footer.js" type="text/javascript"></script>
        </footer>
        </div>

        <script>
            let selectedRole = '';
            
            function selectRole(element) {
                const roleBoxes = document.querySelectorAll(".role-box");
                roleBoxes.forEach(box => box.classList.remove('active'));
                element.classList.add('active');
                selectedRole = element.getAttribute('data-role');
                document.getElementById('selectedRole').value = selectedRole;
            }

            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!selectedRole) {
                    showError('Please select your role');
                    return;
                }
                
                const formData = new FormData(this);
                
                fetch('login_validation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'mainPage.php';
                    } else {
                        showError(data.message || 'Invalid login credentials');
                    }
                })
                .catch(error => {
                    showError('An error occurred. Please try again.');
                });
            });
            
            function showError(message) {
                const errorDiv = document.getElementById('error-message');
                errorDiv.textContent = message;
                errorDiv.classList.remove('d-none');
            }

        </script>
    </body>
</html>