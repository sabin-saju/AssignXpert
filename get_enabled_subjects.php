<?php
include 'config.php';  // Remove the '../'

// Initialize search parameters
$courseName = isset($_GET['course_name']) ? $_GET['course_name'] : '';
$courseCode = isset($_GET['course_code']) ? $_GET['course_code'] : '';
$semesterName = isset($_GET['semester_name']) ? $_GET['semester_name'] : '';
$subjectName = isset($_GET['subject_name']) ? $_GET['subject_name'] : '';

// Build the SQL query with search filters
$sql = "SELECT s.id, s.name, s.has_credits, s.credit_points, 
               c.name as course_name, c.code as course_code, 
               sem.name as semester_name
        FROM subjects s
        JOIN semesters sem ON s.semester_id = sem.id
        JOIN courses c ON sem.course_id = c.id
        WHERE s.is_disabled = FALSE";

// Add search filters if provided
if (!empty($courseName)) {
    $courseName = '%' . $conn->real_escape_string($courseName) . '%';
    $sql .= " AND c.name LIKE '$courseName'";
}

if (!empty($courseCode)) {
    $courseCode = '%' . $conn->real_escape_string($courseCode) . '%';
    $sql .= " AND c.code LIKE '$courseCode'";
}

if (!empty($semesterName)) {
    $semesterName = '%' . $conn->real_escape_string($semesterName) . '%';
    $sql .= " AND sem.name LIKE '$semesterName'";
}

if (!empty($subjectName)) {
    $subjectName = '%' . $conn->real_escape_string($subjectName) . '%';
    $sql .= " AND s.name LIKE '$subjectName'";
}

// Execute the query
$result = $conn->query($sql);

$subjects = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($subjects);
$conn->close();
?> 