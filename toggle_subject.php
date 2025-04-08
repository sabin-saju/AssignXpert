<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $data = json_decode(file_get_contents('php://input'), true);
    $subject_id = $data['subject_id'];
    $new_status = $data['status'] ? 1 : 0;

    $stmt = $conn->prepare("UPDATE subjects SET is_disabled = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $subject_id);

    $response = ['success' => false];

    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        throw new Exception('Error updating subject: ' . $stmt->error);
    }

    echo json_encode($response);

    $stmt->close();

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