<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $subject_id = $_POST['subject_id'] ?? 0;
    $course_id = $_POST['course_id'] ?? 0;
    // Set weekly hours to a fixed 5 hours
    $weekly_hours = 5;

    if (!$subject_id || !$course_id) {
        throw new Exception('Please provide all required fields');
    }

    $conn = connectDB();

    // Get department ID
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $department_id = $stmt->get_result()->fetch_assoc()['department_id'];

    // Verify subject belongs to this course and department
    $subject_query = "SELECT id FROM subjects 
                      WHERE id = ? AND course_id = ? AND department_id = ?";
    $stmt = $conn->prepare($subject_query);
    $stmt->bind_param("iii", $subject_id, $course_id, $department_id);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();

    if (!$subject) {
        throw new Exception('Invalid subject selected');
    }

    // Check if workload already exists for this subject
    $check_query = "SELECT id FROM subject_workload 
                    WHERE subject_id = ? AND course_id = ? AND department_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("iii", $subject_id, $course_id, $department_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Workload already assigned for this subject');
    }

    // Insert new subject workload with fixed 5 hours
    $insert_query = "INSERT INTO subject_workload 
                    (subject_id, course_id, department_id, weekly_hours) 
                    VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiii", $subject_id, $course_id, $department_id, $weekly_hours);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error saving subject workload');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
