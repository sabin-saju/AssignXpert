<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = $data['course_id'];
    $new_status = $data['status'];

    $stmt = $conn->prepare("UPDATE courses SET is_disabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $course_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to update course status");
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