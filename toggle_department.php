<?php
require_once 'config.php';

$conn = connectDB();

$data = json_decode(file_get_contents('php://input'), true);
$department_id = $data['department_id'];
$new_status = $data['status'] ? 1 : 0;

$stmt = $conn->prepare("UPDATE departments SET is_disabled = ? WHERE id = ?");
$stmt->bind_param("ii", $new_status, $department_id);

$response = ['success' => false];

if ($stmt->execute()) {
    $response['success'] = true;
}

echo json_encode($response);

$stmt->close();
$conn->close(); 
?>