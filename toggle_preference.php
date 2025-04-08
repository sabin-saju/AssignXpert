<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $conn = connectDB();

    $data = json_decode(file_get_contents('php://input'), true);
    $preference_id = $data['preference_id'] ?? null;
    $new_status = $data['status'] ?? null;
    
    if ($preference_id === null || $new_status === null) {
        throw new Exception("Missing required parameters");
    }

    // Get teacher id from the session user_id
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Teacher not found");
    }
    
    $teacher = $result->fetch_assoc();
    $teacher_id = $teacher['id'];

    // Verify the preference belongs to the current teacher
    $stmt = $conn->prepare("SELECT * FROM teacher_preferences WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $preference_id, $teacher_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Unauthorized access to preference");
    }

    $stmt = $conn->prepare("UPDATE teacher_preferences SET is_disabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $preference_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to update preference status");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
