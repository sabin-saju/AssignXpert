<?php
// login.php
session_start();
require_once 'config.php';

// Clear any existing session if accessing login page directly
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['user_id'])) {
    session_destroy();
    session_start();
}

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin_dashboard.php');
            break;
        case 'teacher':
            header('Location: teacher_dashboard.php');
            break;
        case 'hod':
            header('Location: hod_dashboard.php');
            break;
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = connectDB();
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("
            SELECT u.user_id, u.password, u.email, r.role_name, u.is_first_login 
            FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.email = ?
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role_name'];
                $_SESSION['first_login'] = $user['is_first_login'];
                
                // Return JSON response instead of redirecting
                echo json_encode([
                    'success' => true,
                    'firstLogin' => $user['is_first_login'] == 1,
                    'redirect' => $user['is_first_login'] == 1 ? 'change_password.php' : null,
                    'dashboard' => getDashboardUrl($user['role_name'])
                ]);
                exit();
            }
        }
        
        // Invalid credentials
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit();
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ]);
        exit();
    }
}

// Helper function to get dashboard URL
function getDashboardUrl($role) {
    switch ($role) {
        case 'teacher':
            return 'teacher_dashboard.php';
        case 'hod':
            return 'hod_dashboard.php';
        case 'admin':
            return 'admin_dashboard.php';
        default:
            return 'index.html';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Reset and body styling */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
    
        /* Header styles */
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
    
        /* Background styles */
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
    
        /* Login container styles */
        .login-container {
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
    
        .login-container h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
        }
    
        .login-container input {
            width: calc(100% - 20px);
            padding: 12px;
            margin: 15px 0;
            border: 2px solid #ccc;
            border-radius: 6px;
            transition: border 0.3s;
        }
    
        .login-container input:focus {
            border: 2px solid #555;
            outline: none;
        }
    
        .login-container button {
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
    
        .login-container button:hover {
            background: linear-gradient(90deg, #222, #444);
            transform: scale(1.02);
        }
    
        .forgot-password {
            margin-top: 15px;
            font-size: 0.9rem;
        }
    
        .forgot-password a {
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
    
        .forgot-password a:hover {
            color: #555;
        }
    
        .back-home {
            margin-top: 20px;
            font-size: 0.9rem;
        }
    
        .back-home a {
            color: #333;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s;
        }
    
        .back-home a:hover {
            color: #555;
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
        <h1>ASSIGNXPERT</h1>
        <div class="nav-links">
            <a href="index.html">Home</a> 
        </div>
    </header>

    <div class="login-container">
        <h2>Login</h2>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
            <div id="errorMessage" style="color: red; margin-top: 10px; display: none;"></div>
        </form>

        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

        <div class="back-home">
            <a href="index.html">Back to Home</a>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const formData = new FormData(this);
            const errorMessage = document.getElementById('errorMessage');
            const submitButton = this.querySelector('button[type="submit"]');
            
            errorMessage.style.display = 'none';
            submitButton.disabled = true;
            submitButton.textContent = 'Logging in...';
            
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Redirect based on response
                    window.location.href = data.firstLogin ? data.redirect : data.dashboard;
                } else {
                    errorMessage.textContent = data.message || 'Login failed. Please try again.';
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.style.display = 'block';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Login';
            });
        });
    </script>
</body>
</html>