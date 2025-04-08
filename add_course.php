<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header to return JSON
header('Content-Type: application/json');

try {
    $conn = connectDB();

    // Get form data
    $name = $_POST['course-name'];
    $code = $_POST['course-code'];
    $departmentId = $_POST['department-id']; // Make sure this matches your form field name
    $courseType = $_POST['course-type'];
    $numSemesters = $_POST['num-semesters'];

    // Validate department_id
    if (empty($departmentId)) {
        throw new Exception("Department ID is required");
    }

    // Validate course name and code are different
    if (strtolower(trim($name)) === strtolower(trim($code))) {
        throw new Exception("Course name and course code must be different");
    }

    // Validate course type and number of semesters
    $validSemesters = false;
    switch($courseType) {
        case 'UG':
            $validSemesters = ($numSemesters == 6 || $numSemesters == 8);
            break;
        case 'PG':
            $validSemesters = ($numSemesters == 4);
            break;
        case 'UG+PG':
            $validSemesters = ($numSemesters == 10 || $numSemesters == 12);
            break;
        default:
            throw new Exception("Invalid course type");
    }

    if (!$validSemesters) {
        throw new Exception("Invalid number of semesters for the selected course type");
    }

    // Debug log
    error_log("Adding course: Name=$name, Code=$code, DepartmentID=$departmentId, Semesters=$numSemesters");

    // Check if course code already exists
    $checkQuery = "SELECT id FROM courses WHERE code = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("s", $code);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $codeExists = $result->num_rows > 0;
    $checkStmt->close(); // Close the statement
    $result->close();    // Close the result set

    if ($codeExists) {
        throw new Exception("Course with this code already exists");
    }

    // Check if the course already exists in the selected department
    $query = "SELECT COUNT(*) as count FROM courses WHERE name = ? AND department_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $name, $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'];
    $stmt->close();     // Close the statement
    $result->close();   // Close the result set

    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Course already exists in the selected department.'
        ]);
        return; // Exit the function if the course exists
    }

    // Insert the course with course type
    $insertQuery = "INSERT INTO courses (name, code, department_id, course_type, num_semesters) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ssisi", $name, $code, $departmentId, $courseType, $numSemesters);

    if ($insertStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Course added successfully'
        ]);
    } else {
        throw new Exception("Error executing query: " . $insertStmt->error);
    }
    $insertStmt->close(); // Close the insert statement

} catch (Exception $e) {
    error_log("Error in add_course.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>