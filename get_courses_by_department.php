<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['department_id'])) {
        throw new Exception('Department ID is required');
    }

    $conn = connectDB();
    $dept_id = intval($_GET['department_id']);

    $query = "SELECT id, name, code FROM courses 
             WHERE department_id = ? AND is_disabled = 0 
             ORDER BY name";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code']
        ];
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