<?php
require_once 'config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add debug logging
function debug_log($message) {
    error_log("[Semester Debug] " . print_r($message, true));
}

debug_log("Received POST request");
debug_log("POST data: " . print_r($_POST, true));

try {
    $conn = connectDB();
    debug_log("Database connection established");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Log all POST data
        debug_log("Processing POST request with data:");
        debug_log("course-id: " . (isset($_POST['course-id']) ? $_POST['course-id'] : 'not set'));
        debug_log("semester-name: " . (isset($_POST['semester-name']) ? $_POST['semester-name'] : 'not set'));
        debug_log("start-date: " . (isset($_POST['start-date']) ? $_POST['start-date'] : 'not set'));
        debug_log("end-date: " . (isset($_POST['end-date']) ? $_POST['end-date'] : 'not set'));
        debug_log("department-id: " . (isset($_POST['department-id']) ? $_POST['department-id'] : 'not set'));

        // Validate inputs
        if (!isset($_POST['course-id']) || !isset($_POST['semester-name']) || 
            !isset($_POST['start-date']) || !isset($_POST['end-date']) ||
            !isset($_POST['department-id'])) {
            throw new Exception('Missing required fields: ' . 
                implode(', ', array_filter([
                    !isset($_POST['course-id']) ? 'course-id' : null,
                    !isset($_POST['semester-name']) ? 'semester-name' : null,
                    !isset($_POST['start-date']) ? 'start-date' : null,
                    !isset($_POST['end-date']) ? 'end-date' : null,
                    !isset($_POST['department-id']) ? 'department-id' : null
                ]))
            );
        }

        $course_id = (int)$_POST['course-id'];
        $department_id = (int)$_POST['department-id'];
        $name = trim($_POST['semester-name']);
        $start_date = $_POST['start-date'];
        $end_date = $_POST['end-date'];

        debug_log("Validated data:");
        debug_log("Course ID: $course_id");
        debug_log("Department ID: $department_id");
        debug_log("Name: $name");
        debug_log("Start Date: $start_date");
        debug_log("End Date: $end_date");

        // Validate course exists and is active
        $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND is_disabled = 0");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Invalid or inactive course selected');
        }
        $stmt->close();
        debug_log("Course validation passed");

        // Validate department exists and is active
        $stmt = $conn->prepare("SELECT id FROM departments WHERE id = ? AND is_disabled = 0");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Invalid or inactive department selected');
        }
        $stmt->close();
        debug_log("Department validation passed");

        // Validate semester name format
        if (!preg_match('/^[A-Za-z\s]+[IVX]*$/', $name)) {
            throw new Exception('Invalid semester name format');
        }

        // Validate dates
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        if ($end <= $start) {
            throw new Exception('End date must be after start date');
        }

        // Check if semester already exists
        $stmt = $conn->prepare("SELECT id FROM semesters WHERE course_id = ? AND name = ? AND is_disabled = 0");
        $stmt->bind_param("is", $course_id, $name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Semester already exists for this course');
        }
        $stmt->close();
        debug_log("Duplicate check passed");

        // Insert semester
        $insert_query = "INSERT INTO semesters (course_id, name, start_date, end_date, department_id) VALUES (?, ?, ?, ?, ?)";
        debug_log("Preparing insert query: " . $insert_query);
        
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("isssi", $course_id, $name, $start_date, $end_date, $department_id);
        debug_log("Bound parameters to insert statement");

        $execute_result = $stmt->execute();
        if (!$execute_result) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $affected_rows = $stmt->affected_rows;
        debug_log("Insert completed. Affected rows: " . $affected_rows);

        if ($affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Semester added successfully',
                'semester_id' => $conn->insert_id
            ]);
            debug_log("Success response sent");
        } else {
            throw new Exception('No rows were inserted');
        }

    } else {
        throw new Exception('Invalid request method');
    }

} catch (Exception $e) {
    debug_log("Error occurred: " . $e->getMessage());
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
        debug_log("Database connection closed");
    }
}
?>