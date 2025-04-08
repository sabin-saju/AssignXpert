<?php
require_once 'config.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Use the connectDB() function instead of expecting $conn to exist globally
    $conn = connectDB();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Very simple query
    $result = $conn->query("SELECT 1 as test");
    if (!$result) {
        throw new Exception("Basic query test failed: " . $conn->error);
    }
    
    // Now try the actual query with error handling
    $sql = "SELECT id, name, code FROM departments WHERE is_disabled = 0";
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Department query failed: " . $conn->error);
    }
    
    $departments = [];
    while($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $departments,
        'count' => count($departments),
        'query' => $sql
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__,
        'error' => isset($conn) ? $conn->error : "Connection not established" 
    ]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?> 