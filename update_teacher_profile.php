<?php
header('Content-Type: application/json');
session_start();
require_once 'config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]));
}

// Validate required fields
$requiredFields = ['name', 'mobile', 'gender', 'qualification', 'subject', 'research', 'teacher_id'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        die(json_encode([
            'success' => false,
            'message' => 'All fields are required'
        ]));
    }
}

// Only allow teachers to update their own profile
if ($_SESSION['user_id'] != $_POST['teacher_id']) {
    die(json_encode([
        'success' => false,
        'message' => 'You can only update your own profile'
    ]));
}

try {
    // Establish database connection
    $conn = connectDB();
    
    // Sanitize input data
    $teacherId = $_SESSION['user_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $mobile = $conn->real_escape_string($_POST['mobile']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $qualification = $conn->real_escape_string($_POST['qualification']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $research = $conn->real_escape_string($_POST['research']);
    
    // Update teacher profile
    $stmt = $conn->prepare("
        UPDATE teachers 
        SET name = ?, mobile = ?, gender = ?, qualification = ?, subject = ?, research = ?
        WHERE user_id = ?
    ");
    
    $stmt->bind_param("ssssssi", $name, $mobile, $gender, $qualification, $subject, $research, $teacherId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
