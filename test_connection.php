<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!$conn) {
        throw new Exception("Connection failed");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'conn_info' => [
            'host_info' => $conn->host_info,
            'server_info' => $conn->server_info,
            'client_info' => $conn->client_info
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $conn->connect_error ?? "Unknown error"
    ]);
}
?> 