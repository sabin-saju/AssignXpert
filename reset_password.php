<?php
session_start();
// Check if code is verified
if (!isset($_SESSION['code_verified']) || $_SESSION['code_verified'] !== true) {
    header('Location: forgot_password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - AssignXpert</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f5f5f5;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: linear-gradient(90deg, rgba(51, 51, 51, 0.9), rgba(51, 51, 51, 0.7));
            color: white;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        header h1 {
            font-size: 1.8rem;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #555;
        }
        
        .reset-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .reset-container h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
        }

        .reset-container p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .reset-container input {
            width: calc(100% - 20px);
            padding: 12px;
            margin: 5px 0;
            border: 2px solid #ccc;
            border-radius: 6px;
            transition: border 0.3s;
        }

        .reset-container input:focus {
            border: 2px solid #555;
            outline: none;
        }

        .reset-container button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #333, #555);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .reset-container button:hover {
            background: linear-gradient(90deg, #222, #444);
            transform: scale(1.02);
        }

        .back-to-login {
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .back-to-login a {
            color: #333;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-to-login a:hover {
            color: #555;
        }

        .success-message {
            display: none;
            color: #28a745;
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            background-color: #d4edda;
        }
        
        .password-requirements {
            margin-top: 10px;
            font-size: 0.8rem;
            color: #666;
            text-align: left;
        }
    </style>
</head>
<body>
    <header>
        <h1>Teacher Subject Allocation Management System</h1>
        <div class="nav-links">
            <a href="index.html">Home</a>
        </div>
    </header>

    <div class="reset-container">
        <h2>Reset Your Password</h2>
        <p>Please enter a new password for your account</p>
        
        <form id="resetPasswordForm">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
                <div class="password-requirements">
                    Password must be at least 8 characters long and include a mix of letters and numbers.
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">Reset Password</button>
        </form>

        <div id="successMessage" class="success-message">
            Password reset successful.
        </div>

        <div class="back-to-login">
            <a href="login.html">Back to Login</a>
        </div>
    </div>

    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const successMessage = document.getElementById('successMessage');
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Basic password validation
            if (password.length < 8) {
                successMessage.textContent = 'Password must be at least 8 characters long.';
                successMessage.style.backgroundColor = '#f8d7da';
                successMessage.style.color = '#721c24';
                successMessage.style.display = 'block';
                return;
            }
            
            // Check if passwords match
            if (password !== confirmPassword) {
                successMessage.textContent = 'Passwords do not match.';
                successMessage.style.backgroundColor = '#f8d7da';
                successMessage.style.color = '#721c24';
                successMessage.style.display = 'block';
                return;
            }
            
            // Disable the button during processing
            submitButton.disabled = true;
            submitButton.textContent = 'Resetting...';
            
            // Make AJAX call to reset_password_process.php
            fetch('reset_password_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'password=' + encodeURIComponent(password) + 
                      '&confirm_password=' + encodeURIComponent(confirmPassword)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data); // For debugging
                
                if (data.success) {
                    successMessage.textContent = data.message;
                    successMessage.style.backgroundColor = '#d4edda';
                    successMessage.style.color = '#155724';
                    successMessage.style.display = 'block';
                    
                    // Redirect to login page after password reset
                    setTimeout(() => {
                        window.location.href = 'Login.php';
                    }, 3000);
                } else {
                    successMessage.textContent = data.message;
                    successMessage.style.backgroundColor = '#f8d7da';
                    successMessage.style.color = '#721c24';
                    successMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                successMessage.textContent = 'An error occurred. Please try again.';
                successMessage.style.backgroundColor = '#f8d7da';
                successMessage.style.color = '#721c24';
                successMessage.style.display = 'block';
            })
            .finally(() => {
                // Re-enable the button
                submitButton.disabled = false;
                submitButton.textContent = 'Reset Password';
            });
        });
    </script>
</body>
</html> 