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
    
    $stmt = $conn->prepare("
        SELECT h.*, h.name as email, d.name as department_name 
        FROM hod h 
        JOIN departments d ON h.department_id = d.id 
        WHERE h.user_id = ?
    ");
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'HOD profile not found'
        ]);
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
