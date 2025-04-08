<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    if (!isset($_GET['department_id']) || empty($_GET['department_id'])) {
        throw new Exception("Department ID is required");
    }
    
    $department_id = intval($_GET['department_id']);
    
    if ($department_id <= 0) {
        throw new Exception("Invalid department ID");
    }
    
    $conn = connectDB();
    
    // Query to get all semesters for courses in a department
    $stmt = $conn->prepare("
        SELECT s.id, s.name, s.start_date, s.end_date, s.is_disabled, 
               c.name as course_name, c.code as course_code,
               d.name as department_name
        FROM semesters s
        JOIN courses c ON s.course_id = c.id
        JOIN departments d ON c.department_id = d.id
        WHERE d.id = ?
        ORDER BY c.name, s.name
    ");
    
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $semesters = [];
    while ($row = $result->fetch_assoc()) {
        $semesters[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'is_disabled' => $row['is_disabled'],
            'course_name' => $row['course_name'],
            'course_code' => $row['course_code'],
            'department_name' => $row['department_name']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $semesters]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 