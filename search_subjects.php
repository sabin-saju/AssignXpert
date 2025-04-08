<?php
session_start();
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get the search parameters
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : '';
$semester_name = isset($_GET['semester_name']) ? $_GET['semester_name'] : '';
$subject_name = isset($_GET['subject_name']) ? $_GET['subject_name'] : '';

try {
    // Connect to database
    $conn = connectDB();
    
    // Prepare the base query
    $query = "SELECT s.id, s.name, s.credit_points, s.is_disabled, c.name as course_name, sem.name as semester_name 
              FROM subjects s
              JOIN semesters sem ON s.semester_id = sem.id
              JOIN courses c ON sem.course_id = c.id
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Add conditions based on search type
    if ($search_type === 'semester' && !empty($semester_name)) {
        $query .= " AND sem.name LIKE ?";
        $params[] = "%$semester_name%";
        $types .= "s";
    } elseif ($search_type === 'subject' && !empty($subject_name)) {
        $query .= " AND s.name LIKE ?";
        $params[] = "%$subject_name%";
        $types .= "s";
    }
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    // Return the results
    echo json_encode([
        'success' => true,
        'subjects' => $subjects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error occurred: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
