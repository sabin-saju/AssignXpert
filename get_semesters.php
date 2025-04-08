<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $query = "SELECT s.id, s.name, s.start_date, s.end_date, s.is_disabled, 
              c.name as course_name, d.name as department_name 
              FROM semesters s 
              JOIN courses c ON s.course_id = c.id 
              JOIN departments d ON s.department_id = d.id 
              ORDER BY d.name, c.name, s.name";
              
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Error fetching semesters: ' . $conn->error);
    }

    $semesters = [];
    while ($row = $result->fetch_assoc()) {
        $semesters[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $semesters
    ]);

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