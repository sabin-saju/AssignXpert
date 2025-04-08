<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $id = $_POST['id'] ?? 0;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : null;
    
    if (!$id || $status === null || ($status !== 0 && $status !== 1)) {
        throw new Exception('Invalid parameters');
    }

    $conn = connectDB();

    // Get department ID for security check
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $department_id = $stmt->get_result()->fetch_assoc()['department_id'];

    // Only allow update of workload in HOD's department
    $update_query = "UPDATE designation_workload 
                     SET is_enabled = ? 
                     WHERE id = ? AND department_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iii", $status, $id, $department_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Unable to update workload status or workload not found');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}