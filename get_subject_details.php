<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    
    if ($subjectId <= 0) {
        throw new Exception('Invalid subject ID');
    }
    
    $conn = connectDB();
    
    $query = "SELECT s.id, s.name, s.has_credits, s.credit_points, s.subject_type,
                     s.semester_id, s.course_id, s.department_id
              FROM subjects s
              WHERE s.id = ? AND s.is_disabled = 0";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Subject not found');
    }
    
    $subject = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'subject' => $subject
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>
