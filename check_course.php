<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();
    
    if (isset($_GET['name'])) {
        $name = trim($_GET['name']);
        $stmt = $conn->prepare("SELECT id FROM courses WHERE name = ?");
        $stmt->bind_param("s", $name);
    } elseif (isset($_GET['code'])) {
        $code = trim($_GET['code']);
        $stmt = $conn->prepare("SELECT id FROM courses WHERE code = ?");
        $stmt->bind_param("s", $code);
    } else {
        throw new Exception('Invalid request');
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['exists' => $result->num_rows > 0]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 
?>