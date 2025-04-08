<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get POST data
    $full_name = $_POST['full_name'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $qualification = $_POST['qualification'] ?? '';
    $research = $_POST['research'] ?? '';
    $subject_expertise = $_POST['subject_expertise'] ?? '';
    
    // Update HOD profile
    $stmt = $conn->prepare("
        UPDATE hod 
        SET full_name = ?, 
            mobile = ?, 
            gender = ?, 
            qualification = ?, 
            research = ?, 
            subject_expertise = ? 
        WHERE user_id = ?
    ");
    
    $stmt->bind_param("ssssssi", 
        $full_name, 
        $mobile, 
        $gender, 
        $qualification, 
        $research, 
        $subject_expertise, 
        $_SESSION['user_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update profile');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
