<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied'
    ]);
    exit;
}

// Function to log detailed error information
function logError($message, $data = []) {
    error_log($message . (empty($data) ? '' : ': ' . json_encode($data)));
}

try {
    $conn = connectDB();
    
    // Get teacher's ID and designation from the session
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, designation FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Teacher profile not found");
    }
    
    $teacher = $result->fetch_assoc();
    $teacher_id = $teacher['id'];
    $teacher_designation = $teacher['designation'];
    
    // Get form data
    $department_id = isset($_POST['department']) ? intval($_POST['department']) : 0;
    $course_id = isset($_POST['course']) ? intval($_POST['course']) : 0;
    $semester_id = isset($_POST['semester']) ? intval($_POST['semester']) : 0;
    $subject_id = isset($_POST['subject']) ? intval($_POST['subject']) : 0;
    
    if (!$department_id || !$course_id || !$semester_id || !$subject_id) {
        throw new Exception("Missing required fields");
    }
    
    // Check if another teacher with the same designation has already added a preference for this course
    $stmt = $conn->prepare("
        SELECT t.name, t.designation 
        FROM teacher_preferences tp
        JOIN teachers t ON tp.teacher_id = t.id
        WHERE tp.course_id = ? 
        AND t.designation = ? 
        AND tp.teacher_id != ? 
        AND tp.is_disabled = 0
    ");
    $stmt->bind_param("isi", $course_id, $teacher_designation, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existing_teacher = $result->fetch_assoc();
        throw new Exception("Another " . strtolower($teacher_designation) . " has already added a preference for this course");
    }
    
    // Log input data for debugging
    logError("Input data", [
        'teacher_id' => $teacher_id,
        'department_id' => $department_id,
        'course_id' => $course_id,
        'semester_id' => $semester_id,
        'subject_id' => $subject_id
    ]);
    
    // Check if the course exists
    $stmt = $conn->prepare("SELECT id, name FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $courseResult = $stmt->get_result();
    
    if ($courseResult->num_rows === 0) {
        throw new Exception("Invalid course ID");
    }
    
    // Check if subject exists and belongs to the selected semester
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE id = ? AND semester_id = ? AND is_disabled = 0");
    $stmt->bind_param("ii", $subject_id, $semester_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Invalid subject or subject doesn't belong to the selected semester");
    }
    
    // Check if this subject already has enabled preferences from other teachers
    // For lab subjects, allow up to 3 teachers to select the same subject
    $stmt = $conn->prepare("
        SELECT s.subject_type, COUNT(tp.id) as teacher_count, GROUP_CONCAT(t.name SEPARATOR ', ') as teacher_names
        FROM teacher_preferences tp
        JOIN teachers t ON tp.teacher_id = t.id
        JOIN subjects s ON tp.subject_id = s.id
        WHERE tp.subject_id = ? AND tp.teacher_id != ? AND tp.is_disabled = 0
        GROUP BY s.subject_type
    ");
    $stmt->bind_param("ii", $subject_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $preference_data = $result->fetch_assoc();
        
        // For theory subjects, only 1 teacher can add preference
        if ($preference_data['subject_type'] === 'theory') {
            $other_teacher = $preference_data['teacher_names'];
            throw new Exception("This theory subject already has an active preference from teacher: " . $other_teacher);
        } 
        // For lab subjects, allow up to 3 teachers
        else if ($preference_data['subject_type'] === 'lab' && $preference_data['teacher_count'] >= 3) {
            throw new Exception("This lab subject already has the maximum of 3 active preferences from other teachers");
        }
    }
    
    // Check if teacher already has a preference for this subject
    $stmt = $conn->prepare("SELECT id FROM teacher_preferences WHERE teacher_id = ? AND subject_id = ?");
    $stmt->bind_param("ii", $teacher_id, $subject_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("You already have a preference for this subject");
    }
    
    // INSERT using all required foreign keys
    $stmt = $conn->prepare("
        INSERT INTO teacher_preferences 
        (teacher_id, subject_id, department_id, course_id, semester_id, is_disabled) 
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    
    $stmt->bind_param("iiiii", $teacher_id, $subject_id, $department_id, $course_id, $semester_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    echo json_encode(['success' => true, 'message' => 'Preference saved successfully']);
    
} catch (Exception $e) {
    logError("Preference save error", ['message' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
