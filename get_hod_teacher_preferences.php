<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get the HOD's department ID
    $stmt = $conn->prepare("SELECT department_id FROM hod WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([]);
        exit;
    }
    
    $hod = $result->fetch_assoc();
    $departmentId = $hod['department_id'];
    
    // Check the structure of the teachers table first
    $columnsQuery = $conn->query("SHOW COLUMNS FROM teachers");
    $teacherColumns = [];
    while ($column = $columnsQuery->fetch_assoc()) {
        $teacherColumns[] = $column['Field'];
    }
    
    // Based on the columns available, adjust the query
    if (in_array('first_name', $teacherColumns) && in_array('last_name', $teacherColumns)) {
        // If separate first_name and last_name columns exist
        $nameSelection = "CONCAT(t.first_name, ' ', t.last_name) as teacher_name";
        $orderBy = "ORDER BY t.last_name, t.first_name";
    } elseif (in_array('name', $teacherColumns)) {
        // If there's a single name column
        $nameSelection = "t.name as teacher_name";
        $orderBy = "ORDER BY t.name";
    } else {
        // Fallback using user data if teacher name isn't available
        $nameSelection = "u.email as teacher_name";
        $orderBy = "ORDER BY u.email";
    }
    
    // Get teacher preferences for this department with teacher details
    $query = "
        SELECT 
            tp.id,
            tp.is_disabled,
            $nameSelection,
            u.email as teacher_email,
            d.name as department_name,
            c.name as course_name,
            s.name as semester_name,
            sub.name as subject_name
        FROM teacher_preferences tp
        JOIN teachers t ON tp.teacher_id = t.id
        JOIN users u ON t.user_id = u.user_id
        JOIN departments d ON tp.department_id = d.id
        JOIN courses c ON tp.course_id = c.id
        JOIN semesters s ON tp.semester_id = s.id
        JOIN subjects sub ON tp.subject_id = sub.id
        WHERE tp.department_id = ?
        $orderBy
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $preferences = $result->fetch_all(MYSQLI_ASSOC);
    
    // Always return an array, even if empty
    echo json_encode($preferences);
    
} catch (Exception $e) {
    // In case of error, still return an array structure with error details
    echo json_encode(['error' => $e->getMessage()]);
}
?>
