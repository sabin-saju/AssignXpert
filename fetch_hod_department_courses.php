<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $conn = connectDB();
    
    // First, get the department_id for the logged-in HOD
    $hodId = $_SESSION['user_id'];
    $deptQuery = "SELECT department_id FROM hod WHERE user_id = ?";
    $deptStmt = $conn->prepare($deptQuery);
    $deptStmt->bind_param("i", $hodId);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    
    if ($deptResult->num_rows === 0) {
        throw new Exception('HOD department not found');
    }
    
    $hodDept = $deptResult->fetch_assoc();
    $departmentId = $hodDept['department_id'];
    
    // Store department_id in session for future use
    $_SESSION['department_id'] = $departmentId;
    
    // Now get courses for this department
    $query = "SELECT id, name FROM courses 
              WHERE department_id = ? AND is_disabled = 0 
              ORDER BY name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'courses' => $courses,
        'department_id' => $departmentId
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_hod_department_courses.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => $e->getTraceAsString()
    ]);
} finally {
    if (isset($deptStmt)) $deptStmt->close();
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>
