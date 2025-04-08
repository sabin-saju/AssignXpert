<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AssignXpert</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
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

        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .background img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            animation: fade 18s infinite;
        }

        .background img:nth-child(1) { animation-delay: 0s; }
        .background img:nth-child(2) { animation-delay: 6s; }
        .background img:nth-child(3) { animation-delay: 12s; }

        @keyframes fade {
            0%, 100% { opacity: 0; }
            33%, 66% { opacity: 1; }
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
    </style>
</head>
<body>
    <div class="background">
        <img src="pexels-fauxels-3184328 (1).jpg" alt="Background 1">
        <img src="taylor-flowe-4nKOEAQaTgA-unsplash.jpg" alt="Background 2">
        <img src="pexels-ekrulila-2292837.jpg" alt="Background 3">
    </div>

    <header>
        <h1>Teacher Subject Allocation Management System</h1>
        <div class="nav-links">
            <a href="index.html">Home</a>
        </div>
    </header>

    <div class="reset-container">
        <h2>Reset Password</h2>
        <p>Enter your email address below and we'll send you verification code to reset your password.</p>
        
        <form id="resetForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit">Send Code</button>
        </form>

        <div id="successMessage" class="success-message">
            Password reset instructions have been sent to your email address.
        </div>

        <div class="back-to-login">
            <a href="Login.php">Back to Login</a>
        </div>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            const successMessage = document.getElementById('successMessage');
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Disable the button during processing
            submitButton.disabled = true;
            submitButton.textContent = 'Sending...';
            
            // Make AJAX call to process_forgot_password.php
            fetch('process_forgot_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data); // For debugging
                
                if (data.success) {
                    successMessage.textContent = data.message;
                    successMessage.style.backgroundColor = '#d4edda';
                    successMessage.style.color = '#155724';
                    successMessage.style.display = 'block';
                    
                    // Clear the form
                    document.getElementById('email').value = '';
                    
                    // Redirect to verification page
                    setTimeout(() => {
                        window.location.href = 'verify_code.php';
                    }, 2000);
                } else {
                    successMessage.textContent = data.message;
                    successMessage.style.backgroundColor = '#f8d7da';
                    successMessage.style.color = '#721c24';
                    successMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Try to get more details about the error
                if (error.response) {
                    console.error('Response status:', error.response.status);
                    error.response.text().then(text => console.error('Response text:', text));
                }
                successMessage.textContent = 'An error occurred. Please try again.';
                successMessage.style.backgroundColor = '#f8d7da';
                successMessage.style.color = '#721c24';
                successMessage.style.display = 'block';
            })
            .finally(() => {
                // Re-enable the button
                submitButton.disabled = false;
                submitButton.textContent = 'Send Code';
                
                // Hide the message after 5 seconds
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 5000);
            });
        });
    </script>
</body>
</html>


