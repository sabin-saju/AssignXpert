<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }
    
    if (!isset($_POST['semester_id']) || !isset($_POST['status'])) {
        throw new Exception("Missing required parameters");
    }
    
    $semester_id = intval($_POST['semester_id']);
    $status = $_POST['status'];
    
    if ($semester_id <= 0) {
        throw new Exception("Invalid semester ID");
    }
    
    if ($status !== 'enable' && $status !== 'disable') {
        throw new Exception("Invalid status value");
    }
    
    $conn = connectDB();
    
    // Set is_disabled based on the requested action
    $is_disabled = ($status === 'disable') ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE semesters SET is_disabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_disabled, $semester_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Semester " . ($is_disabled ? 'disabled' : 'enabled') . " successfully"
            ]);
        } else {
            // Semester might not exist or no change needed
            echo json_encode([
                'success' => false,
                'message' => "No changes made. Semester might not exist or is already in the requested state."
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