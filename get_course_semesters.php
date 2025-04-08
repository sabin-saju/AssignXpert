<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['course_id'])) {
        throw new Exception('Course ID is required');
    }

    $conn = connectDB();
    $course_id = intval($_GET['course_id']);

    $query = "SELECT id, name 
             FROM semesters 
             WHERE course_id = ? AND is_disabled = 0 
             ORDER BY name";
             
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $semesters = [];
    while ($row = $result->fetch_assoc()) {
        $semesters[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
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