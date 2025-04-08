<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }
    
    if (!isset($_POST['course_id']) || !isset($_POST['status'])) {
        throw new Exception("Missing required parameters");
    }
    
    $course_id = intval($_POST['course_id']);
    $status = $_POST['status'];
    
    if ($course_id <= 0) {
        throw new Exception("Invalid course ID");
    }
    
    if ($status !== 'enable' && $status !== 'disable') {
        throw new Exception("Invalid status value");
    }
    
    $conn = connectDB();
    
    // Set is_disabled based on the requested action
    $is_disabled = ($status === 'disable') ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE courses SET is_disabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_disabled, $course_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Course " . ($is_disabled ? 'disabled' : 'enabled') . " successfully"
            ]);
        } else {
            // Course might not exist or no change needed
            echo json_encode([
                'success' => false,
                'message' => "No changes made. Course might not exist or is already in the requested state."
            ]);
        }
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 