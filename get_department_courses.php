<?php
require_once 'config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_GET['department_id']) || empty($_GET['department_id'])) {
        throw new Exception("Department ID is required");
    }
    
    $department_id = intval($_GET['department_id']);
    
    if ($department_id <= 0) {
        throw new Exception("Invalid department ID");
    }
    
    $conn = connectDB();
    
    // Modified query to include disabled courses
    $stmt = $conn->prepare("
        SELECT c.id, c.name, c.code, c.num_semesters, c.is_disabled, d.name as department_name 
        FROM courses c
        JOIN departments d ON c.department_id = d.id
        WHERE c.department_id = ?
        ORDER BY c.name
    ");
    
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'num_semesters' => $row['num_semesters'],
            'is_disabled' => $row['is_disabled'],
            'department_name' => $row['department_name']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $courses]);
    
} catch (Exception $e) {
    error_log("Error in get_department_courses.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>