<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

// Enable detailed error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log GET parameters
error_log("check_teacher_day_schedule.php called with: " . json_encode($_GET));

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get parameters with better debugging
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
$day_of_week = isset($_GET['day_of_week']) ? $_GET['day_of_week'] : '';
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$subject_type = isset($_GET['subject_type']) ? $_GET['subject_type'] : '';
$hour = isset($_GET['hour']) ? intval($_GET['hour']) : 0;

// Log the received parameters for debugging
error_log("Received parameters: teacher_id=$teacher_id, day_of_week=$day_of_week, subject_id=$subject_id, subject_type=$subject_type, hour=$hour");

// More informative error message
if (!$teacher_id || !$day_of_week || !$subject_id || !$subject_type || !$hour) {
    $missing = [];
    if (!$teacher_id) $missing[] = 'teacher_id';
    if (!$day_of_week) $missing[] = 'day_of_week';
    if (!$subject_id) $missing[] = 'subject_id';
    if (!$subject_type) $missing[] = 'subject_type';
    if (!$hour) $missing[] = 'hour';
    
    error_log("Missing parameters: " . implode(', ', $missing));
    echo json_encode([
        'success' => false, 
        'messages' => ['Missing required parameters: ' . implode(', ', $missing)]
    ]);
    exit;
}

try {
    $conn = connectDB();
    
    // Get teacher designation
    $stmt = $conn->prepare("SELECT designation FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacher = $result->fetch_assoc();
    $designation = $teacher['designation'] ?? '';
    
    // 1. Get current schedule for this teacher for this day
    $stmt = $conn->prepare("
        SELECT ts.*, s.name as subject_name, s.subject_type, s.id as subject_id
        FROM class_schedules ts
        JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.teacher_id = ? AND ts.day_of_week = ?
    ");
    $stmt->bind_param("is", $teacher_id, $day_of_week);
    $stmt->execute();
    $day_schedule = $stmt->get_result();
    
    // Count hours by subject type
    $theory_hours_today = 0;
    $lab_hours_today = 0;
    $total_hours_today = 0;
    $theory_subjects_today = [];
    $lab_subjects_today = [];
    $subject_daily_hours = [];
    
    while ($schedule = $day_schedule->fetch_assoc()) {
        $total_hours_today++;
        
        if ($schedule['subject_type'] == 'theory') {
            $theory_hours_today++;
            if (!isset($theory_subjects_today[$schedule['subject_id']])) {
                $theory_subjects_today[$schedule['subject_id']] = 0;
            }
            $theory_subjects_today[$schedule['subject_id']]++;
        } else {
            $lab_hours_today++;
            if (!isset($lab_subjects_today[$schedule['subject_id']])) {
                $lab_subjects_today[$schedule['subject_id']] = 0;
            }
            $lab_subjects_today[$schedule['subject_id']]++;
        }
        
        if (!isset($subject_daily_hours[$schedule['subject_id']])) {
            $subject_daily_hours[$schedule['subject_id']] = 0;
        }
        $subject_daily_hours[$schedule['subject_id']]++;
    }
    
    // Check total lab hours for this day (all teachers)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_lab_hours
        FROM class_schedules ts
        JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.day_of_week = ? AND s.subject_type = 'lab'
    ");
    $stmt->bind_param("s", $day_of_week);
    $stmt->execute();
    $result = $stmt->get_result();
    $lab_data = $result->fetch_assoc();
    $total_lab_hours_today = $lab_data['total_lab_hours'];
    
    // Check if there are already different theory subjects for this teacher
    $different_theory_subjects = count($theory_subjects_today);
    
    // 2. Get weekly schedule for this teacher
    $stmt = $conn->prepare("
        SELECT ts.*, s.name as subject_name, s.subject_type 
        FROM class_schedules ts
        JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.teacher_id = ?
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $weekly_schedule = $stmt->get_result();
    
    $total_weekly_hours = 0;
    $subject_weekly_hours = [];
    
    while ($schedule = $weekly_schedule->fetch_assoc()) {
        $total_weekly_hours++;
        
        if (!isset($subject_weekly_hours[$schedule['subject_id']])) {
            $subject_weekly_hours[$schedule['subject_id']] = 0;
        }
        $subject_weekly_hours[$schedule['subject_id']]++;
    }
    
    // For lab subjects, include hours from other teachers who share the same lab
    if ($subject_type == 'lab') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as shared_lab_hours
            FROM class_schedules ts
            WHERE ts.subject_id = ? AND ts.teacher_id != ?
        ");
        $stmt->bind_param("ii", $subject_id, $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $shared_lab_data = $result->fetch_assoc();
        $shared_lab_hours = $shared_lab_data['shared_lab_hours'];
        
        // Add these hours to our count
        $total_weekly_hours += $shared_lab_hours;
    }
    
    // Validation messages array
    $messages = [];
    
    // 3. Check constraints
    
    // Weekly hour limits based on designation
    $weekly_hour_limit = 20; // Default for assistant professors
    if ($designation == 'Associate Professor') {
        $weekly_hour_limit = 18;
    }
    
    if ($total_weekly_hours >= $weekly_hour_limit) {
        $messages[] = "Teacher cannot exceed {$weekly_hour_limit} hours per week based on their designation";
    }
    
    // Subject weekly limit (5 hours per subject per week)
    if (isset($subject_weekly_hours[$subject_id]) && $subject_weekly_hours[$subject_id] >= 5) {
        $messages[] = "A subject cannot be scheduled for more than 5 hours per week";
    }
    
    // Daily theory subject hours limit (max 2 hours per day per theory subject)
    if ($subject_type == 'theory' && isset($subject_daily_hours[$subject_id]) && $subject_daily_hours[$subject_id] >= 2) {
        $messages[] = "A theory subject cannot be scheduled for more than 2 hours per day";
    }
    
    // KEEP the existing constraint limiting each theory subject to 2 hours per day:
// Daily theory subject hours limit (max 2 hours per day per theory subject)
if ($subject_type == 'theory' && isset($subject_daily_hours[$subject_id]) && $subject_daily_hours[$subject_id] >= 2) {
    $messages[] = "A theory subject cannot be scheduled for more than 2 hours per day";
}
    
    // Lab and theory subject combination constraints
    if ($subject_type == 'lab' && $theory_hours_today > 2) {
        $messages[] = "Cannot schedule lab hours on a day with more than 2 hours of theory subjects";
    }
    
    // If 5 lab hours are already scheduled, theory subjects are limited to 1 hour per teacher
    if ($subject_type == 'theory' && $total_lab_hours_today >= 5) {
        if ($theory_hours_today >= 1) {
            $messages[] = "With 5 lab hours scheduled, you have already reached the maximum of 1 hour for theory subjects for this teacher";
        }
    }
    
    // Lab hours limit (max 5 hours per day)
    if ($subject_type == 'lab' && ($lab_hours_today + 1) > 5) {
        $messages[] = "Cannot schedule more than 5 lab hours per day";
    }
    
    // Check if any lab subjects are already scheduled for this day by any teacher
    $stmt = $conn->prepare("
        SELECT DISTINCT ts.subject_id, s.name as subject_name
        FROM class_schedules ts
        JOIN subjects s ON ts.subject_id = s.id
        WHERE ts.day_of_week = ? AND s.subject_type = 'lab'
    ");
    $stmt->bind_param("s", $day_of_week);
    $stmt->execute();
    $existing_lab_subjects = $stmt->get_result();
    $lab_subjects_count = $existing_lab_subjects->num_rows;

    // If trying to add a lab subject when a different lab subject is already scheduled for this day
    if ($subject_type == 'lab' && $lab_subjects_count > 0) {
        $existing_lab = $existing_lab_subjects->fetch_assoc();
        if ($existing_lab['subject_id'] != $subject_id) {
            $messages[] = "Cannot schedule multiple lab subjects on the same day. '{$existing_lab['subject_name']}' is already scheduled for this day.";
        }
    }
    
    // Return the validation result
    echo json_encode([
        'success' => empty($messages),
        'messages' => $messages,
        'theory_count' => $theory_hours_today,
        'lab_count' => $lab_hours_today, 
        'total_daily_hours' => $total_hours_today,
        'total_weekly_hours' => $total_weekly_hours,
        'subject_daily_hours' => $subject_daily_hours,
        'total_lab_hours_today' => $total_lab_hours_today,
        'weekly_hour_limit' => $weekly_hour_limit
    ]);
    
} catch (Exception $e) {
    error_log("Error in check_teacher_day_schedule.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'messages' => ['Database error: ' . $e->getMessage()]]);
}
?>