<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $designation = $_POST['designation'] ?? '';
    $weekly_hours = $_POST['weekly_hours'] ?? 0;

    if (empty($designation) || $weekly_hours <= 0) {
        throw new Exception('Please provide valid designation and weekly hours');
    }

    // Establish database connection
    $conn = connectDB();

    // Get department ID
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $department_id = $row['department_id'];

    // Validate maximum hours
    $max_hours = [
        'Junior Assistant Professor' => 22,
        'Senior Assistant Professor' => 20,
        'Associate Professor' => 19,
        'HOD' => 17
    ];

    if (!isset($max_hours[$designation])) {
        throw new Exception('Invalid designation selected');
    }

    // Get current total hours for this designation
    $current_hours_query = "SELECT COALESCE(SUM(weekly_hours), 0) as total 
                           FROM designation_workload 
                           WHERE designation = ? AND department_id = ?";
    $stmt = $conn->prepare($current_hours_query);
    $stmt->bind_param("si", $designation, $department_id);
    $stmt->execute();
    $current_total = $stmt->get_result()->fetch_assoc()['total'];

    // Check if adding new hours would exceed maximum
    if (($current_total + $weekly_hours) > $max_hours[$designation]) {
        throw new Exception('Adding these hours would exceed maximum allowed for this designation');
    }

    // Insert new workload without subject or course
    $insert_query = "INSERT INTO designation_workload 
                    (designation, department_id, weekly_hours) 
                    VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sii", $designation, $department_id, $weekly_hours);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error saving workload');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
