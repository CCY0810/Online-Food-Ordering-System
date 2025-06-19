<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: mainPage.php");
}

$error = '';
if (isset($_SESSION['registration_error'])) {
    $error = $_SESSION['registration_error'];
    unset($_SESSION['registration_error']);
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Register</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .main-structure {
                display: grid;
                grid-template-rows: auto 1fr auto;
                min-height: 100vh;
                font-size: 1.1rem;
            }
            
            header, footer {
                background-color: #2c3e50;
            }
            
            main {
                background: url("assets/login-bg3.jpeg") center/cover no-repeat;
            }
            
            .registration-container {
                background-color: rgba(255, 255, 255, 0.95);
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .register-button {
                background-color: #3498db;
                border-radius: 20px;
                transition: all 0.3s;
                font-size: 1.1rem;
            }
            
            .register-button:hover {
                background-color: #2980b9;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .register-button:active {
                background-color: #1a5276;
                transform: scale(0.98);
            }
            
            .input-label {
                font-size: 1rem;
                margin-bottom: 0.5rem;
                font-weight: 500;
            }
            
            .form-control {
                margin-bottom: 1rem;
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            
            .login-link {
                color: #3498db;
                text-decoration: none;
                font-size: 1rem;
            }
            
            .login-link:hover {
                text-decoration: underline;
            }
            
            .password-message {
                font-size: 0.9rem;
                margin-top: -0.5rem;
                margin-bottom: 1rem;
                display: none;
            }
            
            .password-match {
                color: green;
            }
            
            .password-mismatch {
                color: red;
            }
            
            h2 {
                font-size: 1.8rem;
                margin-bottom: 1.5rem !important;
            }
            
            .small {
                font-size: 0.95rem !important;
            }
            
            .alert-danger {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
        </style>
    </head>

    <body>
        <div class="main-structure">
            <header class="d-flex justify-content-center align-items-center py-3 shadow-sm">
                <h1 class="system-title text-white m-0 fs-2 fw-semibold">CC Food Ordering System</h1>
            </header>

            <main class="d-flex justify-content-center align-items-center p-4">
                <div class="registration-container p-5 w-100" style="max-width: 500px">
                    <h2 class="text-center mb-4">Create Your Account</h2>
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form id="registrationForm" action="register_process.php" method="POST">
                        <h3 class="input-label text-dark">Name</h3>
                        <input type="text" class="form-control py-2 px-3" id="name" name="name" placeholder="Enter your name" required
                               value="<?php echo isset($_SESSION['old_name']) ? htmlspecialchars($_SESSION['old_name']) : ''; ?>">
                        
                        <h3 class="input-label text-dark">Age</h3>
                        <input type="number" class="form-control py-2 px-3" id="age" name="age" placeholder="Enter your age" required
                               value="<?php echo isset($_SESSION['old_age']) ? htmlspecialchars($_SESSION['old_age']) : ''; ?>">

                        <h3 class="input-label text-dark">Email</h3>
                        <input type="email" class="form-control py-2 px-3" id="email" name="email" placeholder="Enter your email" required
                               value="<?php echo isset($_SESSION['old_email']) ? htmlspecialchars($_SESSION['old_email']) : ''; ?>">
                        
                        <h3 class="input-label text-dark">Contact Number</h3>
                        <input type="tel" class="form-control py-2 px-3" id="contact" name="contact" placeholder="Enter contact number" required
                               value="<?php echo isset($_SESSION['old_contact']) ? htmlspecialchars($_SESSION['old_contact']) : ''; ?>">
                        
                        <h3 class="input-label text-dark">Address</h3>
                        <input type="text" class="form-control py-2 px-3" id="address" name="address" placeholder="Enter your address" required
                               value="<?php echo isset($_SESSION['old_address']) ? htmlspecialchars($_SESSION['old_address']) : ''; ?>">
                        
                        <h3 class="input-label text-dark">User ID</h3>
                        <input type="text" class="form-control py-2 px-3" id="userID" name="userID" placeholder="Create user ID" required
                               value="<?php echo isset($_SESSION['old_userID']) ? htmlspecialchars($_SESSION['old_userID']) : ''; ?>">
                        
                        <h3 class="input-label text-dark">Password</h3>
                        <input type="password" class="form-control py-2 px-3" id="password" name="password" placeholder="Create password" required>
                        
                        <h3 class="input-label text-dark">Confirm Password</h3>
                        <input type="password" class="form-control py-2 px-3" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                        <div id="passwordMessage" class="password-message"></div>
                        
                        <button type="submit" class="register-button btn btn-primary w-100 py-3 mb-3" id="registerBtn">Sign Up</button>
                        
                        <div class="text-center">
                            <span class="small">Already have an account? <a href="login.php" class="login-link">Sign In</a></span>
                        </div>
                    </form>
                </div>
            </main>

            <footer class="bg-dark text-white py-3 text-center mt-auto">
                <script src="script/footer.js" type="text/javascript"></script>
            </footer>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const passwordInput = document.getElementById('password');
                const confirmPasswordInput = document.getElementById('confirmPassword');
                const passwordMessage = document.getElementById('passwordMessage');
                const form = document.getElementById('registrationForm');
                
                function checkPasswordMatch() {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (password === '' || confirmPassword === '') {
                        passwordMessage.style.display = 'none';
                        return false;
                    }
                    
                    if (password === confirmPassword) {
                        passwordMessage.textContent = 'Passwords match!';
                        passwordMessage.className = 'password-message password-match';
                        passwordMessage.style.display = 'block';
                        return true;
                    } else {
                        passwordMessage.textContent = 'Passwords do not match!';
                        passwordMessage.className = 'password-message password-mismatch';
                        passwordMessage.style.display = 'block';
                        return false;
                    }
                }
                
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
                passwordInput.addEventListener('input', checkPasswordMatch);
                
                form.addEventListener('submit', function(e) {
                    if (!checkPasswordMatch()) {
                        e.preventDefault();
                        alert('Please make sure your passwords match!');
                        return;
                    }
                    
                    // Check password length
                    if (passwordInput.value.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        return;
                    }
                    
                    // Check all required fields
                    const inputs = form.querySelectorAll('input[required]');
                    let allFilled = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            allFilled = false;
                            input.classList.add('is-invalid');
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!allFilled) {
                        e.preventDefault();
                        alert('Please fill in all required fields!');
                    }
                });
            });
        </script>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>