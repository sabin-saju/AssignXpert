<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['course_id'])) {
        throw new Exception('Course ID is required');
    }

    $courseId = intval($_GET['course_id']);
    $conn = connectDB();

    $query = "SELECT 
        d.id as department_id,
        d.name as department_name,
        d.code as department_code
    FROM courses c
    INNER JOIN departments d ON c.department_id = d.id
    WHERE c.id = ? AND c.is_disabled = 0";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $row
        ]);
    } else {
        throw new Exception('Department not found');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 