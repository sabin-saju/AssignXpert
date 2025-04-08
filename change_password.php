<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if it's a teacher or HOD
if (!in_array($_SESSION['role'], ['teacher', 'hod'])) {
    header('Location: ' . $_SESSION['role'] . '_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = connectDB();
        
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit();
        }
        
        // Update password and first login status
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE users 
            SET password = ?, is_first_login = 0 
            WHERE user_id = ?
        ");
        
        $stmt->bind_param("si", $hashedPassword, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'redirect' => $_SESSION['role'] . '_dashboard.php'
            ]);
        } else {
            throw new Exception('Failed to update password');
        }
        
    } catch (Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ]);
    }
    exit();
}

// Example of updating teacher profile
function updateTeacherProfile($conn, $userId, $data) {
    $stmt = $conn->prepare("
        UPDATE teachers t
        JOIN users u ON t.user_id = u.user_id
        SET t.name = ?, t.mobile = ?, t.qualification = ?
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("sssi", $data['name'], $data['mobile'], $data['qualification'], $userId);
    return $stmt->execute();
}

// Example of updating HOD profile
function updateHodProfile($conn, $userId, $data) {
    $stmt = $conn->prepare("
        UPDATE hod h
        JOIN users u ON h.user_id = u.user_id
        SET h.name = ?, h.mobile = ?, h.qualification = ?
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("sssi", $data['name'], $data['mobile'], $data['qualification'], $userId);
    return $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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
    
        /* Change Password container styles */
        .change-password-container {
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
    
        .change-password-container h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 20px;
        }
    
        .change-password-container input {
            width: calc(100% - 20px);
            padding: 12px;
            margin: 15px 0;
            border: 2px solid #ccc;
            border-radius: 6px;
            transition: border 0.3s;
        }
    
        .change-password-container input:focus {
            border: 2px solid #555;
            outline: none;
        }
    
        .change-password-container button {
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
    
        .change-password-container button:hover {
            background: linear-gradient(90deg, #222, #444);
            transform: scale(1.02);
        }

        .password-requirements {
            margin-top: 20px;
            text-align: left;
            padding: 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 6px;
        }

        .requirement {
            margin: 8px 0;
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement i {
            font-size: 12px;
        }

        .valid {
            color: #27ae60;
        }

        .invalid {
            color: #e74c3c;
        }

        #errorMessage {
            color: #e74c3c;
            margin-top: 10px;
            display: none;
            font-size: 0.9rem;
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

    <div class="change-password-container">
        <h2>Change Password</h2>
        <form id="changePasswordForm">
            <div class="form-group">
                <input type="password" id="new_password" name="new_password" placeholder="New Password" required>
            </div>
            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <div id="errorMessage"></div>
            <div class="password-requirements">
                <p style="margin-bottom: 10px; color: #333;">Password must contain:</p>
                <div class="requirement" id="length">
                    <i class="fas fa-circle"></i>
                    At least 8 characters
                </div>
                <div class="requirement" id="uppercase">
                    <i class="fas fa-circle"></i>
                    One uppercase letter
                </div>
                <div class="requirement" id="lowercase">
                    <i class="fas fa-circle"></i>
                    One lowercase letter
                </div>
                <div class="requirement" id="number">
                    <i class="fas fa-circle"></i>
                    One number
                </div>
            </div>
            <button type="submit">Change Password</button>
        </form>
    </div>

    <script>
        const form = document.getElementById('changePasswordForm');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const errorMessage = document.getElementById('errorMessage');

        // Password validation requirements (removed special character requirement)
        const requirements = {
            length: /.{8,}/,
            uppercase: /[A-Z]/,
            lowercase: /[a-z]/,
            number: /[0-9]/
        };

        function validatePassword(password) {
            for (const [key, regex] of Object.entries(requirements)) {
                const element = document.getElementById(key);
                if (regex.test(password)) {
                    element.classList.add('valid');
                    element.classList.remove('invalid');
                    element.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    element.classList.add('invalid');
                    element.classList.remove('valid');
                    element.querySelector('i').className = 'fas fa-times-circle';
                }
            }
        }

        newPassword.addEventListener('input', () => {
            validatePassword(newPassword.value);
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate password requirements
            const password = newPassword.value;
            let isValid = true;
            
            for (const [key, regex] of Object.entries(requirements)) {
                if (!regex.test(password)) {
                    isValid = false;
                    break;
                }
            }
            
            if (!isValid) {
                errorMessage.textContent = 'Please meet all password requirements';
                errorMessage.style.display = 'block';
                return;
            }
            
            if (newPassword.value !== confirmPassword.value) {
                errorMessage.textContent = 'Passwords do not match';
                errorMessage.style.display = 'block';
                return;
            }

            const formData = new FormData(this);
            
            fetch('change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    errorMessage.textContent = data.message;
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.style.display = 'block';
            });
        });
    </script>
</body>
</html>
