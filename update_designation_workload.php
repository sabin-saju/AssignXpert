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
    
    if (!$id || $weekly_hours <= 0) {
        throw new Exception('Invalid parameters');
    }

    $conn = connectDB();

    // Get department ID for security check
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $department_id = $stmt->get_result()->fetch_assoc()['department_id'];

    // Get the designation to validate max hours
    $get_designation = "SELECT designation FROM designation_workload WHERE id = ? AND department_id = ?";
    $stmt = $conn->prepare($get_designation);
    $stmt->bind_param("ii", $id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception('Workload not found or not in your department');
    }
    
    $row = $result->fetch_assoc();
    $designation = $row['designation'];
    
    // Check max hours based on designation
    $max_hours = 22; // Default for Junior Assistant Professor
    
    if ($designation === 'Senior Assistant Professor') {
        $max_hours = 20;
    } else if ($designation === 'Associate Professor') {
        $max_hours = 18;
    } else if ($designation === 'HOD') {
        $max_hours = 16;
    }
    
    if ($weekly_hours > $max_hours) {
        throw new Exception("Weekly hours cannot exceed $max_hours for $designation");
    }

    // Only allow update of workload in HOD's department
    $update_query = "UPDATE designation_workload 
                     SET weekly_hours = ? 
                     WHERE id = ? AND department_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iii", $weekly_hours, $id, $department_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Unable to update workload or no changes made');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 