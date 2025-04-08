<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $id = $_POST['id'] ?? 0;
    $weekly_hours = isset($_POST['weekly_hours']) ? (int)$_POST['weekly_hours'] : 0;
    
    if (!$id || !$weekly_hours) {
        throw new Exception('Invalid parameters');
    }
    
    if ($weekly_hours != 5) {
        throw new Exception('Weekly hours must be 5 for all subjects');
    }

    $conn = connectDB();

    // Get department ID for security check
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $department_id = $stmt->get_result()->fetch_assoc()['department_id'];

    // Only allow update of workload in HOD's department
    $update_query = "UPDATE subject_workload 
                     SET weekly_hours = ? 
                     WHERE id = ? AND department_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iii", $weekly_hours, $id, $department_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Unable to update workload or workload not found');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 