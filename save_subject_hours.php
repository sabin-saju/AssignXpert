<?php
session_start();
require_once 'config.php';

// Clear output buffers
if (ob_get_level()) ob_end_clean();

// Set headers
header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }
    
    // Debug: log the raw POST data
    error_log('POST data: ' . print_r($_POST, true));
    
    // Parse input data
    $subjectId = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $weeklyHours = isset($_POST['weekly_hours']) ? (int)$_POST['weekly_hours'] : 0;
    $dailyHoursJson = isset($_POST['daily_hours']) ? $_POST['daily_hours'] : '';
    
    // Validate parameters
    if ($subjectId <= 0) {
        throw new Exception('Invalid subject ID: ' . $subjectId);
    }
    
    if ($weeklyHours <= 0) {
        throw new Exception('Weekly hours must be greater than zero: ' . $weeklyHours);
    }
    
    // Parse daily hours JSON
    $dailyHours = json_decode($dailyHoursJson, true);
    if ($dailyHours === null) {
        throw new Exception('Invalid daily hours data: ' . $dailyHoursJson . ' - JSON error: ' . json_last_error_msg());
    }
    
    // Get database connection
    $conn = connectDB();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Get department ID from session or database
    $departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : 0;
    if ($departmentId <= 0) {
        $hodId = $_SESSION['user_id'];
        $deptQuery = "SELECT department_id FROM hod WHERE user_id = ?";
        $deptStmt = $conn->prepare($deptQuery);
        $deptStmt->bind_param("i", $hodId);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();
        
        if ($deptResult->num_rows > 0) {
            $departmentId = $deptResult->fetch_assoc()['department_id'];
            $_SESSION['department_id'] = $departmentId;
        } else {
            throw new Exception('HOD department not found');
        }
        $deptStmt->close();
    }
    
    // Get subject details to validate it exists and get course ID
    $subjectQuery = "SELECT course_id FROM subjects WHERE id = ? AND department_id = ?";
    $subjectStmt = $conn->prepare($subjectQuery);
    if (!$subjectStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $subjectStmt->bind_param("ii", $subjectId, $departmentId);
    if (!$subjectStmt->execute()) {
        throw new Exception('Subject query failed: ' . $subjectStmt->error);
    }
    
    $subjectResult = $subjectStmt->get_result();
    if ($subjectResult->num_rows === 0) {
        throw new Exception('Subject not found or not in your department');
    }
    
    $subjectData = $subjectResult->fetch_assoc();
    $courseId = $subjectData['course_id'];
    
    $subjectStmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if subject_hours entry already exists
        $checkQuery = "SELECT id FROM subject_hours WHERE subject_id = ? AND department_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        if (!$checkStmt) {
            throw new Exception('Check prepare failed: ' . $conn->error);
        }
        
        $checkStmt->bind_param("ii", $subjectId, $departmentId);
        if (!$checkStmt->execute()) {
            throw new Exception('Check query failed: ' . $checkStmt->error);
        }
        
        $checkResult = $checkStmt->get_result();
        $subjectHoursId = null;
        
        if ($checkResult->num_rows > 0) {
            // Update existing record
            $subjectHoursId = $checkResult->fetch_assoc()['id'];
            
            $updateQuery = "UPDATE subject_hours SET weekly_hours = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            if (!$updateStmt) {
                throw new Exception('Update prepare failed: ' . $conn->error);
            }
            
            $updateStmt->bind_param("ii", $weeklyHours, $subjectHoursId);
            if (!$updateStmt->execute()) {
                throw new Exception('Update query failed: ' . $updateStmt->error);
            }
            
            $updateStmt->close();
            
            // Delete existing daily hours
            $deleteQuery = "DELETE FROM daily_subject_hours WHERE subject_hours_id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            if (!$deleteStmt) {
                throw new Exception('Delete prepare failed: ' . $conn->error);
            }
            
            $deleteStmt->bind_param("i", $subjectHoursId);
            if (!$deleteStmt->execute()) {
                throw new Exception('Delete query failed: ' . $deleteStmt->error);
            }
            
            $deleteStmt->close();
        } else {
            // Insert new record
            $insertQuery = "INSERT INTO subject_hours (subject_id, course_id, department_id, weekly_hours) VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            if (!$insertStmt) {
                throw new Exception('Insert prepare failed: ' . $conn->error);
            }
            
            $insertStmt->bind_param("iiii", $subjectId, $courseId, $departmentId, $weeklyHours);
            if (!$insertStmt->execute()) {
                throw new Exception('Insert query failed: ' . $insertStmt->error);
            }
            
            $subjectHoursId = $conn->insert_id;
            if (!$subjectHoursId) {
                throw new Exception('Failed to get insert ID');
            }
            $insertStmt->close();
        }
        
        // Check if daily_subject_hours table exists and create if needed
        $tableCheckQuery = "SHOW TABLES LIKE 'daily_subject_hours'";
        $tableResult = $conn->query($tableCheckQuery);
        if ($tableResult->num_rows === 0) {
            // Create the table if it doesn't exist
            $createTableQuery = "CREATE TABLE `daily_subject_hours` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `subject_hours_id` int(11) NOT NULL,
                `day_of_week` varchar(20) NOT NULL,
                `hours` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `subject_hours_id` (`subject_hours_id`),
                CONSTRAINT `daily_subject_hours_ibfk_1` FOREIGN KEY (`subject_hours_id`) REFERENCES `subject_hours` (`id`)
              )";
            if (!$conn->query($createTableQuery)) {
                throw new Exception('Failed to create daily_subject_hours table: ' . $conn->error);
            }
        }
        
        // Insert daily hours
        $insertDailyQuery = "INSERT INTO daily_subject_hours (subject_hours_id, day_of_week, hours) VALUES (?, ?, ?)";
        $insertDailyStmt = $conn->prepare($insertDailyQuery);
        if (!$insertDailyStmt) {
            throw new Exception('Daily hours prepare failed: ' . $conn->error);
        }
        
        // Log daily hours data for debugging
        error_log("Daily hours data: " . json_encode($dailyHours));
        error_log("Subject hours ID: " . $subjectHoursId);
        
        // Insert daily hours
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($days as $day) {
            $hours = isset($dailyHours[$day]) ? (int)$dailyHours[$day] : 0;
            $dayOfWeek = ucfirst($day);
            
            // Log the data for each day
            error_log("Inserting for $dayOfWeek: $hours hours");
            
            $insertDailyStmt->bind_param("isi", $subjectHoursId, $dayOfWeek, $hours);
            if (!$insertDailyStmt->execute()) {
                throw new Exception('Error inserting hours for ' . $day . ': ' . $insertDailyStmt->error);
            }
        }
        $insertDailyStmt->close();
        
        // Explicitly commit the transaction
        if (!$conn->commit()) {
            throw new Exception('Failed to commit transaction: ' . $conn->error);
        }
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Subject hours saved successfully',
            'subject_hours_id' => $subjectHoursId
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Log the error
    error_log('Error in save_subject_hours.php: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn)) $conn->close();
}
?>
