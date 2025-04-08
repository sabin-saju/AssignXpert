<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get HOD's department_id
    $stmt = $conn->prepare("SELECT department_id FROM hod WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $hod = $stmt->get_result()->fetch_assoc();
    
    if (!$hod) {
        throw new Exception('HOD not found');
    }
    
    // Get faculty emails for the department
    $stmt = $conn->prepare("
        SELECT u.email
        FROM teachers t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.department_id = ?
        ORDER BY u.email
    ");
    
    $stmt->bind_param("i", $hod['department_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $faculty = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $faculty
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
