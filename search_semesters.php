<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $conn = connectDB();
    
    // Search by department ID
    if (isset($_GET['department_id']) && !empty($_GET['department_id'])) {
        $departmentId = (int)$_GET['department_id'];
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
        $stmt->bind_param("i", $departmentId);
    }
    // Search by course name
    else if (isset($_GET['course_name']) && !empty($_GET['course_name'])) {
        $courseName = '%' . $_GET['course_name'] . '%';
        $stmt = $conn->prepare("
            SELECT s.id, s.name, s.start_date, s.end_date, s.is_disabled, 
                   c.name as course_name, c.code as course_code,
                   d.name as department_name
            FROM semesters s
            JOIN courses c ON s.course_id = c.id
            JOIN departments d ON c.department_id = d.id
            WHERE c.name LIKE ?
            ORDER BY d.name, c.name, s.name
        ");
        $stmt->bind_param("s", $courseName);
    }
    // Search by course code
    else if (isset($_GET['course_code']) && !empty($_GET['course_code'])) {
        $courseCode = '%' . $_GET['course_code'] . '%';
        $stmt = $conn->prepare("
            SELECT s.id, s.name, s.start_date, s.end_date, s.is_disabled, 
                   c.name as course_name, c.code as course_code,
                   d.name as department_name
            FROM semesters s
            JOIN courses c ON s.course_id = c.id
            JOIN departments d ON c.department_id = d.id
            WHERE c.code LIKE ?
            ORDER BY d.name, c.name, s.name
        ");
        $stmt->bind_param("s", $courseCode);
    }
    // Search by semester name
    else if (isset($_GET['semester_name']) && !empty($_GET['semester_name'])) {
        $semesterName = '%' . $_GET['semester_name'] . '%';
        $stmt = $conn->prepare("
            SELECT s.id, s.name, s.start_date, s.end_date, s.is_disabled, 
                   c.name as course_name, c.code as course_code,
                   d.name as department_name
            FROM semesters s
            JOIN courses c ON s.course_id = c.id
            JOIN departments d ON c.department_id = d.id
            WHERE s.name LIKE ?
            ORDER BY d.name, c.name, s.name
        ");
        $stmt->bind_param("s", $semesterName);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid search parameters']);
        exit;
    }
    
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