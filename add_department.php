<?php
require_once 'config.php';

$conn = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['department-name'];
    $code = $_POST['department-code'];
    
    // Check if department code already exists
    $stmt = $conn->prepare("SELECT id FROM departments WHERE code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Department code already exists']);
        exit;
    }
    
    // Insert new department
    $stmt = $conn->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $code);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
}

$conn->close(); 
?>