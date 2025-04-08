<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $conn = connectDB();
    
    // Get teacher_id from the session user_id
    $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Teacher not found');
    }
    
    $teacher = $result->fetch_assoc();
    $teacherId = $teacher['id'];
    
    // Get preferences for this teacher
    $query = "
        SELECT 
            tp.id,
            tp.teacher_id,
            tp.is_disabled,
            d.name as department_name,
            c.name as course_name,
            s.name as semester_name,
            sub.name as subject_name,
            sub.subject_type
        FROM teacher_preferences tp
        JOIN departments d ON tp.department_id = d.id
        JOIN courses c ON tp.course_id = c.id
        JOIN semesters s ON tp.semester_id = s.id
        JOIN subjects sub ON tp.subject_id = sub.id
        WHERE tp.teacher_id = ?
        ORDER BY tp.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $preferences = $result->fetch_all(MYSQLI_ASSOC);
    
    // Count active preferences by type
    $activeTheoryCount = 0;
    $activeLabCount = 0;
    
    foreach ($preferences as $pref) {
        if ($pref['is_disabled'] == 0) {
            if ($pref['subject_type'] == 'theory') {
                $activeTheoryCount++;
            } else if ($pref['subject_type'] == 'lab') {
                $activeLabCount++;
            }
        }
    }
    
    $response = [
        'preferences' => $preferences,
        'counts' => [
            'theory' => $activeTheoryCount,
            'lab' => $activeLabCount
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
