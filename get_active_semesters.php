<?php
require_once 'config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = connectDB();
    
    // Modified query to get all active semesters
    $query = "SELECT 
        s.id,
        s.name,
        c.name as course_name
        FROM semesters s
        JOIN courses c ON s.course_id = c.id
        WHERE s.is_disabled = 0
        ORDER BY c.name, s.name";

    $result = $conn->query($query);
    
    if ($result) {
        $semesters = [];
        while ($row = $result->fetch_assoc()) {
            $semesters[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $semesters
        ]);
    } else {
        throw new Exception('Error fetching semesters');
    }

} catch (Exception $e) {
    error_log("Error in get_active_semesters.php: " . $e->getMessage());
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