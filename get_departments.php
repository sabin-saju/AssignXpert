<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();

    $query = "SELECT id, name, code, is_disabled FROM departments ORDER BY name";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Error fetching departments');
    }

    $departments = [];
    while ($row = $result->fetch_assoc()) {
        $departments[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'is_disabled' => (bool)$row['is_disabled']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $departments
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