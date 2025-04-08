<?php
require_once 'config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = connectDB();
    
    $sql = "SELECT id, name FROM departments WHERE is_disabled = FALSE ORDER BY name";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed");
    }

    $departments = [];
    while($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $departments
    ]);

} catch (Exception $e) {
    error_log("Error in get_departments_for_semester.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($result)) {
        $result->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>