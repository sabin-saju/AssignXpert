<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $conn = connectDB();
    $response = ['success' => false, 'data' => [], 'message' => ''];
    
    // Search by name
    if (isset($_GET['name']) && !empty($_GET['name'])) {
        $name = '%' . $_GET['name'] . '%';
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.code, c.num_semesters, c.is_disabled, d.name as department_name
            FROM courses c
            JOIN departments d ON c.department_id = d.id
            WHERE c.name LIKE ?
            ORDER BY c.name
        ");
        $stmt->bind_param("s", $name);
    } 
    // Search by code
    else if (isset($_GET['code']) && !empty($_GET['code'])) {
        $code = '%' . $_GET['code'] . '%';
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.code, c.num_semesters, c.is_disabled, d.name as department_name
            FROM courses c
            JOIN departments d ON c.department_id = d.id
            WHERE c.code LIKE ?
            ORDER BY c.code
        ");
        $stmt->bind_param("s", $code);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid search parameters']);
        exit;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'num_semesters' => $row['num_semesters'],
            'is_disabled' => $row['is_disabled'],
            'department_name' => $row['department_name']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $courses]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 