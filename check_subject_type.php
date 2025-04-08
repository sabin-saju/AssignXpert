<?php
session_start();
require_once 'config.php';

// Set headers
header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }
    
    // Get parameters
    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    
    if ($subjectId <= 0) {
        throw new Exception('Invalid subject ID');
    }
    
    $conn = connectDB();
    
    // Direct query to get subject_type and credit_points
    $query = "SELECT subject_type, has_credits, credit_points FROM subjects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Subject not found');
    }
    
    $subject = $result->fetch_assoc();
    
    // Log the exact values from the database
    error_log("Subject ID: $subjectId, Type: {$subject['subject_type']}, Credits: {$subject['credit_points']}");
    
    // Return just the essential info
    echo json_encode([
        'success' => true,
        'subject_type' => $subject['subject_type'],
        'credit_points' => $subject['has_credits'] ? (int)$subject['credit_points'] : 0
    ]);
    
} catch (Exception $e) {
    error_log('Error in check_subject_type.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 