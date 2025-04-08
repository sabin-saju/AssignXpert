<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['semester_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    $semester_id = (int)$data['semester_id'];
    $new_status = $data['status'] ? 1 : 0;

    // Check if semester exists
    $stmt = $conn->prepare("SELECT id FROM semesters WHERE id = ?");
    $stmt->bind_param("i", $semester_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Semester not found');
    }
    $stmt->close();

    // Update semester status
    $stmt = $conn->prepare("UPDATE semesters SET is_disabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $semester_id);

    if (!$stmt->execute()) {
        throw new Exception('Error updating semester: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Semester status updated successfully',
        'new_status' => $new_status
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