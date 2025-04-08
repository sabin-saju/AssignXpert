<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $query = "SELECT id, name, code FROM departments WHERE is_disabled = 0 ORDER BY name";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Error fetching departments: ' . $conn->error);
    }

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }

    echo json_encode($departments);

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