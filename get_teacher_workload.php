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
    
    // Get teacher ID
    $teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
    if ($teacherId <= 0) {
        throw new Exception('Invalid teacher ID');
    }
    
    $conn = connectDB();
    
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
    }
    
    // Get teacher info
    $teacherQuery = "SELECT id, name FROM teachers WHERE id = ?";
    $teacherStmt = $conn->prepare($teacherQuery);
    $teacherStmt->bind_param("i", $teacherId);
    $teacherStmt->execute();
    $teacherResult = $teacherStmt->get_result();
    
    if ($teacherResult->num_rows === 0) {
        throw new Exception('Teacher not found');
    }
    
    $teacher = $teacherResult->fetch_assoc();
    
    // First check if there are any workload entries
    $checkQuery = "SELECT COUNT(*) as count FROM teacher_workload 
                   WHERE teacher_id = ? AND department_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ii", $teacherId, $departmentId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result()->fetch_assoc();
    
    if ($checkResult['count'] == 0) {
        // Return empty workload array if no entries found
        echo json_encode([
            'success' => true,
            'teacher' => $teacher,
            'workload' => [],
            'total_hours' => 0,
            'debug' => 'No workload entries found'
        ]);
        exit;
    }
    
    // Get all workload entries for this teacher
    $workloadQuery = "SELECT tw.id, tw.weekly_hours, tw.created_at
                      FROM teacher_workload tw
                      WHERE tw.teacher_id = ? AND tw.department_id = ?
                      ORDER BY tw.created_at DESC";
    $workloadStmt = $conn->prepare($workloadQuery);
    $workloadStmt->bind_param("ii", $teacherId, $departmentId);
    $workloadStmt->execute();
    $workloadResult = $workloadStmt->get_result();
    
    $workload = [];
    $totalHours = 0;
    
    // Process each workload entry
    while ($row = $workloadResult->fetch_assoc()) {
        $workloadId = $row['id'];
        $entry = [
            'id' => $workloadId,
            'weekly_hours' => $row['weekly_hours'],
            'created_at' => $row['created_at'],
            'monday_hours' => 0,
            'tuesday_hours' => 0,
            'wednesday_hours' => 0,
            'thursday_hours' => 0,
            'friday_hours' => 0
        ];
        
        // Get daily hours for this workload entry
        $dailyQuery = "SELECT day_of_week, hours 
                       FROM daily_teacher_hours 
                       WHERE teacher_workload_id = ?";
        $dailyStmt = $conn->prepare($dailyQuery);
        $dailyStmt->bind_param("i", $workloadId);
        $dailyStmt->execute();
        $dailyResult = $dailyStmt->get_result();
        
        // Map daily hours to the entry
        while ($dailyRow = $dailyResult->fetch_assoc()) {
            $day = strtolower($dailyRow['day_of_week']) . '_hours';
            if (isset($entry[$day])) {
                $entry[$day] = $dailyRow['hours'];
            }
        }
        
        $workload[] = $entry;
        $totalHours += $row['weekly_hours'];
        
        $dailyStmt->close();
    }
    
    // Return the data
    echo json_encode([
        'success' => true,
        'teacher' => $teacher,
        'workload' => $workload,
        'total_hours' => $totalHours,
        'workload_count' => count($workload)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($teacherStmt)) $teacherStmt->close();
    if (isset($checkStmt)) $checkStmt->close();
    if (isset($workloadStmt)) $workloadStmt->close();
    if (isset($conn)) $conn->close();
}
?>
