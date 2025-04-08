<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Log the received data for debugging
error_log('Toggle request received: ' . json_encode($_POST));

// Check if the request is POST and has the required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id']) && isset($_POST['current_status'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $current_status = intval($_POST['current_status']);
    
    // Toggle the status (if current is 1, make it 0, and vice versa)
    $new_status = $current_status === 1 ? 0 : 1;
    
    try {
        $conn = connectDB();
        
        // Get HOD's department_id
        $user_id = $_SESSION['user_id'];
        $query = "SELECT department_id FROM hod WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            error_log("No department found for HOD user ID: $user_id");
            echo json_encode(['success' => false, 'message' => 'Department not found for this HOD']);
            exit;
        }
        
        $hodData = $result->fetch_assoc();
        $department_id = $hodData['department_id'];
        
        error_log("HOD Department ID: $department_id, Schedule ID: $schedule_id");
        
        // If we're trying to enable a schedule, check if the time slot is available
        if ($new_status === 1) {
            // Get day and hour of the schedule we're trying to enable
            $query = "SELECT day_of_week, hour, subject_id, teacher_id FROM class_schedules WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Schedule not found']);
                exit;
            }
            
            $scheduleData = $result->fetch_assoc();
            $day = $scheduleData['day_of_week'];
            $hour = $scheduleData['hour'];
            $subject_id = $scheduleData['subject_id'];
            $teacher_id = $scheduleData['teacher_id'];
            
            // Check if another enabled schedule exists for this day and hour
            $query = "SELECT cs.id, t.name as teacher_name, s.name as subject_name 
                      FROM class_schedules cs
                      JOIN teachers t ON cs.teacher_id = t.id
                      JOIN subjects s ON cs.subject_id = s.id
                      WHERE cs.day_of_week = ? AND cs.hour = ? AND cs.is_enabled = 1 AND cs.id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $day, $hour, $schedule_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $conflict = $result->fetch_assoc();
                echo json_encode([
                    'success' => false, 
                    'message' => "Cannot enable this schedule. The time slot is already assigned to {$conflict['teacher_name']} for {$conflict['subject_name']}."
                ]);
                exit;
            }
        }
        
        // Update the schedule status
        $query = "UPDATE class_schedules SET is_enabled = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $new_status, $schedule_id);
        $stmt->execute();
        
        // Check if update was successful
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => $new_status === 1 ? 'Schedule has been enabled' : 'Schedule has been disabled and hour is now available for other teachers'
            ]);
        } else {
            // If no rows were affected, check if the schedule exists
            $query = "SELECT id, department_id FROM class_schedules WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Schedule not found']);
            } else {
                $scheduleData = $result->fetch_assoc();
                $schedule_department_id = $scheduleData['department_id'];
                
                if ($department_id != $schedule_department_id) {
                    echo json_encode(['success' => false, 'message' => 'This schedule does not belong to your department']);
                } else {
                    // Schedule exists and belongs to this department, but update failed for another reason
                    // Check if the current status is already what we're trying to set it to
                    $query = "SELECT is_enabled FROM class_schedules WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $schedule_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $current_db_status = $result->fetch_assoc()['is_enabled'];
                    
                    if ($current_db_status == $new_status) {
                        echo json_encode([
                            'success' => true, 
                            'message' => $new_status === 1 ? 'Schedule is already enabled' : 'Schedule is already disabled'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Unknown error updating schedule status'
                        ]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Exception in toggle_schedule_status.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
}
?>