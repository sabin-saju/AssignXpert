<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $sql = "SELECT 
                c.id,
                c.name, 
                c.code, 
                d.name as department, 
                c.num_semesters,
                c.is_disabled
            FROM courses c 
            LEFT JOIN departments d ON c.department_id = d.id 
            ORDER BY d.name, c.name";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed");
    }

    $courses = [];
    while($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $courses
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