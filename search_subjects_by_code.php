<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();
    
    $courseCode = isset($_GET['course_code']) ? trim($_GET['course_code']) : '';
    
    if (empty($courseCode)) {
        // Instead of throwing an exception, return all subjects
        $query = "SELECT s.id, s.name, s.has_credits, s.credit_points, s.is_disabled, 
                         c.name AS course_name, c.code AS course_code,
                         sem.name AS semester_name,
                         d.name AS department_name
                  FROM subjects s
                  JOIN semesters sem ON s.semester_id = sem.id
                  JOIN courses c ON sem.course_id = c.id
                  JOIN departments d ON c.department_id = d.id
                  ORDER BY s.name";
        
        $stmt = $conn->prepare($query);
    } else {
        // Query to get subjects by course code
        $query = "SELECT s.id, s.name, s.has_credits, s.credit_points, s.is_disabled, 
                         c.name AS course_name, c.code AS course_code,
                         sem.name AS semester_name,
                         d.name AS department_name
                  FROM subjects s
                  JOIN semesters sem ON s.semester_id = sem.id
                  JOIN courses c ON sem.course_id = c.id
                  JOIN departments d ON c.department_id = d.id
                  WHERE c.code LIKE ?
                  ORDER BY s.name";
        
        $stmt = $conn->prepare($query);
        $courseCodeParam = "%$courseCode%"; // Add wildcards for partial matching
        $stmt->bind_param("s", $courseCodeParam);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    echo json_encode($subjects);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
