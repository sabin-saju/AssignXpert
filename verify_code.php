<?php
session_start();
// Check if reset email is set in session
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - AssignXpert</title>
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
        
        .verification-container {
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

        .verification-container h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
        }

        .verification-container p {
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

        .verification-container input {
            width: calc(100% - 20px);
            padding: 12px;
            margin: 5px 0;
            border: 2px solid #ccc;
            border-radius: 6px;
            transition: border 0.3s;
            font-size: 16px;
        }

        .verification-container input:focus {
            border: 2px solid #555;
            outline: none;
        }

        .verification-container button {
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

        .verification-container button:hover {
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
    </style>
</head>
<body>
    <header>
        <h1>Teacher Subject Allocation Management System</h1>
        <div class="nav-links">
            <a href="index.html">Home</a>
        </div>
    </header>

    <div class="verification-container">
        <h2>Verify Code</h2>
        <p>Enter the verification code sent to <strong><?php echo $_SESSION['reset_email']; ?></strong></p>
        
        <form id="verifyForm">
            <div class="form-group">
                <label for="code">Verification Code</label>
                <input type="text" id="code" name="code" required placeholder="Enter 6-digit code">
            </div>
            <button type="submit">Verify Code</button>
        </form>

        <div id="successMessage" class="success-message">
            Code verification successful.
        </div>

        <div class="back-to-login">
            <a href="forgot_password.php">Back to Forgot Password</a>
        </div>
    </div>

    <script>
        document.getElementById('verifyForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const code = document.getElementById('code').value;
            const successMessage = document.getElementById('successMessage');
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable the button during processing
            submitButton.disabled = true;
            submitButton.textContent = 'Verifying...';
            
            // Make AJAX call to verify_code_process.php
            fetch('verify_code_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'code=' + encodeURIComponent(code)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data); // For debugging
                
                if (data.success) {
                    successMessage.textContent = data.message;
                    successMessage.style.backgroundColor = '#d4edda';
                    successMessage.style.color = '#155724';
                    successMessage.style.display = 'block';
                    
                    // Redirect to reset password page
                    setTimeout(() => {
                        window.location.href = 'reset_password.php';
                    }, 1500);
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
                submitButton.textContent = 'Verify Code';
            });
        });
    </script>
</body>
</html> 