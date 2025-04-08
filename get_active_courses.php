<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $query = "SELECT id, name, code FROM courses WHERE is_disabled = 0 ORDER BY name";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Error fetching courses: ' . $conn->error);
    }

    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }

    echo json_encode($courses);

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