<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $query = "SELECT s.id, s.name, s.has_credits, s.credit_points, s.is_disabled,
                     c.name AS course_name, c.code AS course_code,
                     sem.name AS semester_name,
                     d.name AS department_name
              FROM subjects s
              JOIN semesters sem ON s.semester_id = sem.id
              JOIN courses c ON sem.course_id = c.id
              JOIN departments d ON c.department_id = d.id
              ORDER BY s.name";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Error fetching subjects: ' . $conn->error);
    }

    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }

    echo json_encode($subjects);

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