<?php
require_once 'config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Debug log
    error_log("Received GET parameters: " . print_r($_GET, true));

    // Check if semester_id exists and is not empty
    if (!isset($_GET['semester_id']) || empty($_GET['semester_id'])) {
        throw new Exception('Semester ID is required');
    }

    $semesterId = intval($_GET['semester_id']);
    
    // Debug log
    error_log("Processing semester ID: " . $semesterId);

    $conn = connectDB();
    
    // Modified query to get all courses with their specific dates
    $query = "SELECT 
        s1.id as semester_id,
        s1.name as semester_name,
        s1.start_date,
        s1.end_date,
        s1.course_id,
        c.name as course_name,
        c.code as course_code,
        c.department_id,
        d.name as department_name,
        d.code as department_code
    FROM semesters s1
    LEFT JOIN courses c ON s1.course_id = c.id
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE (
        s1.id = ? 
        OR 
        s1.name = (SELECT name FROM semesters WHERE id = ?)
    )
    AND s1.is_disabled = 0
    ORDER BY d.name, c.name";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $semesterId, $semesterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug log
    error_log("Query result: " . print_r($result, true));
    
    if ($result->num_rows > 0) {
        $semesters = [];
        while ($data = $result->fetch_assoc()) {
            // Format dates for each instance
            $data['start_date'] = $data['start_date'] ? date('Y-m-d', strtotime($data['start_date'])) : null;
            $data['end_date'] = $data['end_date'] ? date('Y-m-d', strtotime($data['end_date'])) : null;
            
            // Handle department data
            $data['department_name'] = !empty($data['department_name']) ? $data['department_name'] : 'Not assigned';
            $data['department_code'] = !empty($data['department_code']) ? $data['department_code'] : 'N/A';
            
            $semesters[] = $data;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $semesters
        ]);
    } else {
        throw new Exception('Semester not found');
    }

} catch (Exception $e) {
    error_log("Error in get_semester_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 