<?php
session_start();
require_once 'config.php';  // Add this line to include database configuration

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

// AJAX endpoints within the same file
if (isset($_GET['ajax_request'])) {
    header('Content-Type: application/json');
    
    // Get courses for a department
    if ($_GET['ajax_request'] == 'get_courses' && isset($_GET['department_id'])) {
        $department_id = intval($_GET['department_id']);
        try {
            $conn = connectDB();
            $stmt = $conn->prepare("SELECT id, name FROM courses WHERE department_id = ? AND is_disabled = 0 ORDER BY name");
            $stmt->bind_param("i", $department_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $courses = [];
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
            
            echo json_encode($courses);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }
    
    // Get semesters for a course
    if ($_GET['ajax_request'] == 'get_semesters' && isset($_GET['course_id'])) {
        $course_id = intval($_GET['course_id']);
        try {
            $conn = connectDB();
            $stmt = $conn->prepare("SELECT id, name FROM semesters WHERE course_id = ? AND is_disabled = 0 ORDER BY name");
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $semesters = [];
            while ($row = $result->fetch_assoc()) {
                $semesters[] = $row;
            }
            
            echo json_encode($semesters);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }
    
    // Check preferences count for a semester
    if ($_GET['ajax_request'] == 'check_preferences' && isset($_GET['semester_id'])) {
        $semester_id = intval($_GET['semester_id']);
        $teacher_id = $_SESSION['user_id'];
        try {
            $conn = connectDB();
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM teacher_preferences tp
                JOIN subjects s ON tp.subject_id = s.id
                WHERE tp.teacher_id = ? AND s.semester_id = ? AND tp.is_disabled = 0
            ");
            $stmt->bind_param("ii", $teacher_id, $semester_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            echo json_encode(['count' => intval($result['count'])]);
        } catch (Exception $e) {
            echo json_encode(['count' => 0]);
        }
        exit;
    }
    
    // Get subjects for a semester
    if ($_GET['ajax_request'] == 'get_subjects' && isset($_GET['semester_id'])) {
        $semester_id = intval($_GET['semester_id']);
        $teacher_id = $_SESSION['user_id'];
        try {
            $conn = connectDB();
            
            // Get teacher's ID from user_id
            $stmt = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
            $stmt->bind_param("i", $teacher_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode([]);
                exit;
            }
            
            $teacher = $result->fetch_assoc();
            $actual_teacher_id = $teacher['id'];
            
            // Get subjects that are:
            // 1. In the selected semester
            // 2. Not already in this teacher's preferences
            // 3. For theory subjects: not in any other teacher's ENABLED preferences
            // 4. For lab subjects: have fewer than 3 enabled preferences from other teachers
            $stmt = $conn->prepare("
                SELECT s.id, s.name, s.subject_type 
                FROM subjects s
                LEFT JOIN teacher_preferences tp ON s.id = tp.subject_id AND tp.teacher_id = ? AND tp.is_disabled = 0
                WHERE s.semester_id = ? 
                AND s.is_disabled = 0 
                AND tp.id IS NULL
                AND (
                    (s.subject_type = 'theory' AND NOT EXISTS (
                        SELECT 1 FROM teacher_preferences tp2 
                        WHERE tp2.subject_id = s.id 
                        AND tp2.teacher_id != ?
                        AND tp2.is_disabled = 0
                    ))
                    OR 
                    (s.subject_type = 'lab' AND (
                        SELECT COUNT(*) FROM teacher_preferences tp3
                        WHERE tp3.subject_id = s.id
                        AND tp3.teacher_id != ?
                        AND tp3.is_disabled = 0
                    ) < 3)
                )
                ORDER BY s.name
            ");
            $stmt->bind_param("iiii", $actual_teacher_id, $semester_id, $actual_teacher_id, $actual_teacher_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $subjects = [];
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row;
            }
            
            echo json_encode($subjects);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    // Add this new endpoint
    if ($_GET['ajax_request'] == 'get_all_semester_subjects' && isset($_GET['semester_id'])) {
        $semester_id = intval($_GET['semester_id']);
        try {
            $conn = connectDB();
            
            // Get all subjects for this semester
            $stmt = $conn->prepare("
                SELECT id, name, subject_type, has_credits, credit_points
                FROM subjects
                WHERE semester_id = ? AND is_disabled = 0
                ORDER BY name
            ");
            $stmt->bind_param("i", $semester_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $subjects = [];
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row;
            }
            
            echo json_encode($subjects);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}

// Get user email from session
$userEmail = $_SESSION['email'] ?? 'Unknown User';

// Get teacher-specific data using the logged-in user's ID
$teacherId = $_SESSION['user_id'];

// Example of getting teacher-specific data (add this where needed)
function getTeacherData($conn, $teacherId) {
    $stmt = $conn->prepare("
        SELECT t.*, u.email 
        FROM teachers t 
        JOIN users u ON t.user_id = u.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

try {
    // Establish database connection
    $conn = connectDB();
    
    // Use this function when displaying or modifying teacher data
    $teacherData = getTeacherData($conn, $teacherId);

    // Get enabled departments
    $stmt = $conn->prepare("SELECT id, name FROM departments WHERE is_disabled = 0");
    $stmt->execute();
    $departments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $teacherData = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            padding: 20px 0;
            margin: 0;
            background-color: #34495e;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .sidebar .active {
            background-color: #3498db;
        }

        .dropdown {
            display: none;
            background-color: #34495e;
        }

        .dropdown a {
            padding-left: 40px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .welcome-section {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-card i {
            font-size: 50px;
            color: #3498db;
            margin-bottom: 15px;
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .info-card p {
            font-size: 24px;
            color: #3498db;
            font-weight: bold;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2c3e50;
            color: white;
            padding: 15px 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info span {
            margin: 0 15px;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .user-info i {
            font-size: 16px;
        }

        .user-info a {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-info a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
        }

        .user-info a i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                padding: 15px;
            }
            
            .header h1 {
                margin-bottom: 10px;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .user-info span {
                max-width: 150px;
            }
        }

        .sidebar {
            margin-top: 60px;
            height: calc(100vh - 60px);
        }

        .main-content {
            margin-top: 60px;
        }

        .preference-section {
            display: none;
        }

        .preference-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }

        button {
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:disabled {
            background-color: #cccccc;
        }

        .table-container {
            margin: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .preference-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }

        .preference-table th,
        .preference-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .preference-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }

        .preference-table tr:hover {
            background-color: #f9f9f9;
        }

        .btn-disable {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-enable {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .status-enabled {
            color: #28a745;
            font-weight: bold;
        }

        .status-disabled {
            color: #dc3545;
            font-weight: bold;
        }

        /* Enhanced Profile styles for a classic look */
        .profile-section {
            margin: 30px 0;
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        .profile-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 30px;
            color: white;
            display: flex;
            align-items: center;
            position: relative;
        }

        .profile-avatar {
            margin-right: 25px;
        }

        .profile-avatar i {
            font-size: 72px;
            color: white;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            border: 2px solid rgba(255,255,255,0.3);
        }

        .profile-title h3 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        .profile-title p {
            margin-bottom: 10px;
            opacity: 0.9;
            font-size: 14px;
        }

        .profile-status {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
        }

        .profile-status.complete {
            background: rgba(40, 167, 69, 0.2);
            color: #fff;
        }

        .profile-status.incomplete {
            background: rgba(255, 193, 7, 0.2);
            color: #fff;
        }

        .profile-status i {
            margin-right: 5px;
        }

        .profile-actions {
            position: absolute;
            top: 30px;
            right: 30px;
        }

        .btn-edit {
            background: rgba(255,255,255,0.15);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-edit:hover {
            background: rgba(255,255,255,0.25);
            text-decoration: none;
            color: white;
        }

        .btn-edit i {
            margin-right: 5px;
        }

        .profile-card {
            background: white;
            border-radius: 0 0 10px 10px;
            padding: 40px;
            box-shadow: none;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .profile-info-item {
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f9fa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #3498db;
            position: relative;
            overflow: hidden;
        }

        .profile-info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .profile-info-item::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 20px;
            height: 20px;
            background: #3498db;
            opacity: 0.1;
            border-radius: 0 0 0 20px;
        }

        .info-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .info-label i {
            margin-right: 8px;
            color: #3498db;
            font-size: 14px;
        }

        .info-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 600;
            word-break: break-word;
        }

        .profile-research {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-left: 4px solid #3498db;
            margin-top: 10px;
        }

        .research-text {
            line-height: 1.6;
            margin-top: 10px;
            font-weight: normal;
        }

        .profile-incomplete-message {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .profile-incomplete-message i {
            font-size: 60px;
            color: #f39c12;
            margin-bottom: 20px;
            display: block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        .profile-incomplete-message h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .profile-incomplete-message p {
            margin-bottom: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .btn-complete-profile {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            margin-top: 15px;
            text-decoration: none;
            transition: background-color 0.3s;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(52, 152, 219, 0.3);
        }

        .btn-complete-profile:hover {
            background-color: #2980b9;
            text-decoration: none;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.4);
        }

        @media (max-width: 768px) {
            .profile-info-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .profile-actions {
                position: relative;
                top: auto;
                right: auto;
                margin-top: 20px;
            }
        }

        .profile-designation {
            margin-bottom: 5px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-designation i {
            margin-right: 4px;
            opacity: 0.8;
        }

        /* Add these styles for Edit Profile section */
        .profile-section#profileEditSection {
            margin: 30px 0;
        }

        .edit-header {
            background: linear-gradient(135deg, #34495e, #2980b9);
        }

        .profile-edit-container {
            max-width: 800px;
            margin: 0 auto;
        }

        #teacherProfileForm {
            margin-top: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 8px;
            color: #3498db;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 15px;
            color: #333;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-group input.invalid,
        .form-group select.invalid,
        .form-group textarea.invalid {
            border-color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.05);
        }

        .validation-error {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        input.invalid + .validation-error,
        select.invalid + .validation-error,
        textarea.invalid + .validation-error {
            display: block;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            color: #7f8c8d;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: #e9ecef;
            color: #2c3e50;
            text-decoration: none;
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #3498db;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(52, 152, 219, 0.2);
        }

        .btn-save:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .btn-cancel i,
        .btn-save i {
            margin-right: 8px;
        }

        /* Responsive styles for the form */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-buttons {
                flex-direction: column-reverse;
                gap: 10px;
            }
            
            .btn-cancel, 
            .btn-save {
                width: 100%;
            }
        }

        /* Add visual enhancements to validation */
        input:valid:not(:placeholder-shown) {
            border-color: #2ecc71;
        }

        /* Improve the edit profile header */
        .edit-header .profile-avatar i {
            background-color: rgba(255, 255, 255, 0.25);
        }

        /* Main container styling */
        #courseExplorerSection {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin: 20px 0;
        }
        
        /* Main heading */
        #courseExplorerSection h2 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        /* Sub heading */
        #courseExplorerSection h5 {
            font-size: 18px;
            color: #34495e;
            margin-bottom: 10px;
        }
        
        /* Description text */
        #courseExplorerSection p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        
        /* Select labels */
        #courseExplorerSection label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        /* Select boxes */
        #courseExplorerSection select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
            font-size: 15px;
            color: #333;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
        
        #courseExplorerSection select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.25);
            outline: none;
        }
        
        /* Helper text */
        #courseExplorerSection .choose-text {
            color: #7f8c8d;
            font-size: 14px;
            margin: 10px 0 20px 0;
        }
        
        /* Subject list header */
        #courseExplorerSection .subject-list-header {
            font-weight: 600;
            color: #2c3e50;
            margin: 20px 0 10px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        /* Subject table */
        #courseExplorerSection table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 6px;
            overflow: hidden;
        }
        
        #courseExplorerSection th {
            background-color: #f4f7f9;
            padding: 12px 15px;
            text-align: left;
            color: #34495e;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }
        
        #courseExplorerSection td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        #courseExplorerSection tr:last-child td {
            border-bottom: none;
        }
        
        #courseExplorerSection tr:hover td {
            background-color: #f8f9fa;
        }
        
        /* Credit styling */
        #courseExplorerSection .credits {
            text-align: center;
            font-weight: 600;
            color: #3498db;
        }
        
        /* Theory badge */
        #courseExplorerSection .theory-badge {
            display: inline-block;
            background-color: #e3f2fd;
            color: #1565c0;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        /* Apply styles to the existing elements */
        window.addEventListener('load', function() {
            // Add classes and styling to existing elements
            document.querySelectorAll('#courseExplorerSection p').forEach(p => {
                if (p.textContent.includes('Choose a')) {
                    p.className = 'choose-text';
                }
            });
            
            document.querySelectorAll('#courseExplorerSection strong, #courseExplorerSection b').forEach(el => {
                if (el.textContent.includes('Subject List')) {
                    el.className = 'subject-list-header';
                }
            });
            
            // Add styling to the table cells for theory and credits
            const cells = document.querySelectorAll('#courseExplorerSection td');
            cells.forEach(cell => {
                if (cell.textContent.trim() === 'Theory') {
                    cell.innerHTML = '<span class="theory-badge">Theory</span>';
                }
                
                // Try to identify credit cells (usually numbers like 7, 9)
                if (/^[0-9]+$/.test(cell.textContent.trim())) {
                    cell.className = 'credits';
                }
            });
        });

        /* Styles for the schedule section */
        #schedule-section {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #3498db;
            border-radius: 8px 8px 0 0;
        }

        .card-title {
            font-size: 20px;
            font-weight: bold;
        }

        .schedule-container {
            margin-top: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background-color: #f4f7f9;
            color: #34495e;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .loading-spinner {
            font-size: 16px;
            color: #3498db;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="header">
        <h1>ASSIGNXPERT</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($userEmail); ?></span>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="sidebar">
        <h2>Teacher Dashboard</h2>
        <a href="#" id="profile-link"><i class="fas fa-user"></i> My Profile</a>
        <a href="#" id="edit-profile-link"><i class="fas fa-user-edit"></i> Edit Profile</a>
        <a href="#" id="add-preference-link"><i class="fas fa-plus-circle"></i> Add Preference</a>
        <a href="#" id="view-preference-link"><i class="fas fa-list"></i> View Preferences</a>
        <a href="#" id="view-courses-link"><i class="fas fa-book-open"></i> View Courses</a>
        <a href="#" id="view-schedule-link"><i class="fas fa-calendar-alt"></i> View Schedule</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <!-- View Profile Section -->
        <div class="profile-section" id="profileViewSection" style="display: none;">
            <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            
            <?php
            // Check if teacher profile has been completed
            $profileCompleted = false;
            if ($teacherData && !empty($teacherData['name']) && !empty($teacherData['mobile'])) {
                $profileCompleted = true;
            }
            ?>
            
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-title">
                        <h3><?php echo $profileCompleted ? htmlspecialchars($teacherData['name']) : htmlspecialchars($userEmail); ?></h3>
                        
                        <?php if ($profileCompleted): ?>
                            <?php 
                            // Fetch department name
                            $dept_query = "SELECT d.name FROM departments d WHERE d.id = ?";
                            $stmt = $conn->prepare($dept_query);
                            $stmt->bind_param("i", $teacherData['department_id']);
                            $stmt->execute();
                            $department = $stmt->get_result()->fetch_assoc();
                            $departmentName = $department ? $department['name'] : 'Unknown Department';
                            ?>
                            <p class="profile-designation">
                                <i class="fas fa-id-badge mr-1"></i> <?php echo htmlspecialchars(ucwords($teacherData['designation'])); ?> | 
                                <i class="fas fa-building mr-1"></i> <?php echo htmlspecialchars($departmentName); ?>
                            </p>
                        <?php endif; ?>
                        
                        <p><?php echo htmlspecialchars($userEmail); ?></p>
                        <?php if ($profileCompleted): ?>
                            <span class="profile-status complete"><i class="fas fa-check-circle"></i> Profile Complete</span>
                        <?php else: ?>
                            <span class="profile-status incomplete"><i class="fas fa-exclamation-circle"></i> Profile Incomplete</span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-actions">
                        <a href="#" id="goToEditBtn" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
                
                <!-- Profile View Content -->
                <div class="profile-card">
                    <?php if ($profileCompleted): ?>
                        <div class="profile-info-grid">
                            <div class="profile-info-item">
                                <div class="info-label"><i class="fas fa-user"></i> Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($teacherData['name']); ?></div>
                            </div>
                            
                            <div class="profile-info-item">
                                <div class="info-label"><i class="fas fa-phone"></i> Mobile</div>
                                <div class="info-value"><?php echo htmlspecialchars($teacherData['mobile']); ?></div>
                            </div>
                            
                            <div class="profile-info-item">
                                <div class="info-label"><i class="fas fa-venus-mars"></i> Gender</div>
                                <div class="info-value"><?php echo htmlspecialchars(ucfirst($teacherData['gender'])); ?></div>
                            </div>
                            
                            <div class="profile-info-item">
                                <div class="info-label"><i class="fas fa-graduation-cap"></i> Qualification</div>
                                <div class="info-value"><?php echo htmlspecialchars($teacherData['qualification']); ?></div>
                            </div>
                            
                            <div class="profile-info-item">
                                <div class="info-label"><i class="fas fa-book"></i> Specialized Subject</div>
                                <div class="info-value"><?php echo htmlspecialchars($teacherData['subject']); ?></div>
                            </div>
                        </div>
                        
                        <div class="profile-research">
                            <div class="info-label"><i class="fas fa-flask"></i> Research Interest</div>
                            <div class="info-value research-text"><?php echo htmlspecialchars($teacherData['research']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="profile-incomplete-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <h3>Profile Incomplete</h3>
                            <p>Your profile information is incomplete. Please complete your profile to use all features.</p>
                            <a href="#" id="completeProfileBtn" class="btn-complete-profile">Complete Profile Now</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Edit Profile Section -->
        <div class="profile-section" id="profileEditSection" style="display: none;">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            
            <div class="profile-container">
                <div class="profile-header edit-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="profile-title">
                        <h3>Edit Your Information</h3>
                        <p>Update your professional details</p>
                    </div>
                </div>
                
                <div class="profile-card">
                    <form id="teacherProfileForm" novalidate>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="teacher-name"><i class="fas fa-user"></i> Full Name:</label>
                                <input type="text" id="teacher-name" name="name" value="<?php echo $profileCompleted ? htmlspecialchars($teacherData['name']) : ''; ?>" required>
                                <div class="validation-error" id="name-error">Please enter a valid name (letters and spaces only)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="teacher-mobile"><i class="fas fa-phone"></i> Mobile Number:</label>
                                <input type="text" id="teacher-mobile" name="mobile" value="<?php echo $profileCompleted ? htmlspecialchars($teacherData['mobile']) : ''; ?>" required>
                                <div class="validation-error" id="mobile-error">Please enter a valid 10-digit mobile number</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="teacher-gender"><i class="fas fa-venus-mars"></i> Gender:</label>
                                <select id="teacher-gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($profileCompleted && $teacherData['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($profileCompleted && $teacherData['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($profileCompleted && $teacherData['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="validation-error" id="gender-error">Please select a gender</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="teacher-qualification"><i class="fas fa-graduation-cap"></i> Qualification:</label>
                                <input type="text" id="teacher-qualification" name="qualification" value="<?php echo $profileCompleted ? htmlspecialchars($teacherData['qualification']) : ''; ?>" required>
                                <div class="validation-error" id="qualification-error">Qualification is required</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="teacher-subject"><i class="fas fa-book"></i> Specialized Subject:</label>
                                <input type="text" id="teacher-subject" name="subject" value="<?php echo $profileCompleted ? htmlspecialchars($teacherData['subject']) : ''; ?>" required>
                                <div class="validation-error" id="subject-error">Subject is required</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="teacher-research"><i class="fas fa-flask"></i> Research Interest:</label>
                            <textarea id="teacher-research" name="research" rows="3" required><?php echo $profileCompleted ? htmlspecialchars($teacherData['research']) : ''; ?></textarea>
                            <div class="validation-error" id="research-error">Research interest is required</div>
                        </div>
                        
                        <div class="form-buttons">
                            <a href="#" id="cancelEditProfileBtn" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Save Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Preference Form Section -->
        <div class="preference-form" id="preferenceFormSection" style="display: none;">
            <h2>Add Teaching Preference</h2>
            <form id="preferenceForm">
                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="course">Course:</label>
                    <select id="course" name="course" required disabled>
                        <option value="">Select Course</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semester">Semester:</label>
                    <select id="semester" name="semester" required disabled>
                        <option value="">Select Semester</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <select id="subject" name="subject" required disabled>
                        <option value="">Select Subject</option>
                    </select>
                </div>
                
                <button type="submit" id="submitBtn" disabled>Save Preference</button>
            </form>
        </div>

        <!-- View Preference Section -->
        <div class="view-preference-section" id="viewPreferenceSection" style="display: none;">
            <h2>View Preferences</h2>
            <div id="preferenceRequirementAlert" class="alert alert-info" style="display: none;">
                <i class="fas fa-info-circle"></i>
                <span id="preferenceRequirementMessage"></span>
            </div>
            <div class="table-container">
                <div id="preferencesList">
                    <!-- Preferences will be displayed here -->
                </div>
            </div>
        </div>

        <!-- Course Explorer Section -->
        <div class="profile-section" id="courseExplorerSection" style="display: none;">
            <h2><i class="fas fa-book-open"></i> Department Courses Explorer</h2>
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-book-open mr-2"></i> Course Explorer</h5>
                    <p class="mb-0 mt-1 small opacity-75">Browse courses, semesters and subjects in your department</p>
                </div>
                <div class="card-body">
                    <!-- Course Selection Area -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="explorerCourseSelect" class="font-weight-bold text-primary">
                                    <i class="fas fa-graduation-cap mr-1"></i> Select Course:
                                </label>
                                <select id="explorerCourseSelect" class="form-control form-control-lg custom-select">
                                    <option value="">-- Select Course --</option>
                                </select>
                                <small class="form-text text-muted">Choose a course to view its semesters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="explorerSemesterSelect" class="font-weight-bold text-primary">
                                    <i class="fas fa-clock mr-1"></i> Select Semester:
                                </label>
                                <select id="explorerSemesterSelect" class="form-control form-control-lg custom-select" disabled>
                                    <option value="">-- Select Semester --</option>
                                </select>
                                <small class="form-text text-muted">Choose a semester to view its subjects</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Alert Area -->
                    <div class="alert alert-info shadow-sm border-left border-info" style="border-left-width: 4px !important;" id="explorerSubjectInfo">
                        <i class="fas fa-info-circle mr-2"></i> Select a course and semester to view subjects
                    </div>
                    
                    <!-- Subject Table Container with transition effect -->
                    <div class="explorer-subject-table-container mt-4" style="display: none; transition: all 0.3s ease;">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-primary"><i class="fas fa-list-alt mr-2"></i> Subject List</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th scope="col" class="border-top-0"><i class="fas fa-book mr-1"></i> Subject Name</th>
                                                <th scope="col" class="border-top-0 text-center"><i class="fas fa-tag mr-1"></i> Type</th>
                                                <th scope="col" class="border-top-0 text-center"><i class="fas fa-star mr-1"></i> Credits</th>
                                            </tr>
                                        </thead>
                                        <tbody id="explorerSubjectTableBody">
                                            <!-- Subject data will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state for when no subjects are found -->
                    <div id="explorerEmptySubjectsState" class="text-center py-5 mt-3" style="display: none;">
                        <i class="fas fa-search text-muted fa-3x mb-3"></i>
                        <h5 class="text-muted">No subjects found</h5>
                        <p class="text-muted">Try selecting a different semester or course</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add this new content section (don't replace any existing sections) -->
        <div class="content-section" id="schedule-section" style="display: none;">
            <h2><i class="fas fa-calendar-alt"></i> My Teaching Schedule</h2>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Select Semester</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="semesterSelect">Semester</label>
                        <select class="form-control" id="semesterSelect">
                            <option value="">All Semesters</option>
                            <!-- Populate semesters dynamically -->
                        </select>
                    </div>
                </div>
            </div>
            
            <div id="scheduleContainer" class="schedule-container">
                <div class="loading-spinner text-center">
                    <i class="fas fa-spinner fa-spin"></i> Select a semester to view your schedule
                </div>
                
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Hour</th>
                            <th>Subject</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populate schedule data dynamically -->
                        <tr>
                            <td>Monday</td>
                            <td>Hour 3</td>
                            <td>Formal Logic and Digital Fundamentals</td>
                            <td>Master of Computer Applications (Integrated)</td>
                            <td>Semester II</td>
                            <td>Theory</td>
                            <td>Active</td>
                        </tr>
                        <!-- Repeat for other schedule entries -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Updated navigation script for separate profile pages
        document.addEventListener('DOMContentLoaded', function() {
            // Default section to show (My Profile)
            document.getElementById('profileViewSection').style.display = 'block';
            document.getElementById('profile-link').classList.add('active');
            
            // Handle My Profile link
            document.getElementById('profile-link').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('profileViewSection').style.display = 'block';
                setActiveLink(this);
            });
            
            // Handle Edit Profile link
            document.getElementById('edit-profile-link').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('profileEditSection').style.display = 'block';
                setActiveLink(this);
            });
            
            // Handle Go to Edit button in profile view
            document.getElementById('goToEditBtn').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('profileEditSection').style.display = 'block';
                setActiveLink(document.getElementById('edit-profile-link'));
            });
            
            // Handle Complete Profile button
            if (document.getElementById('completeProfileBtn')) {
                document.getElementById('completeProfileBtn').addEventListener('click', function(e) {
                    e.preventDefault();
                    hideAllSections();
                    document.getElementById('profileEditSection').style.display = 'block';
                    setActiveLink(document.getElementById('edit-profile-link'));
                });
            }
            
            // Handle Cancel Edit button
            document.getElementById('cancelEditProfileBtn').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('profileViewSection').style.display = 'block';
                setActiveLink(document.getElementById('profile-link'));
            });
            
            // Handle Add Preference link
            document.getElementById('add-preference-link').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('preferenceFormSection').style.display = 'block';
                setActiveLink(this);
            });
            
            // Handle View Preferences link
            document.getElementById('view-preference-link').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('viewPreferenceSection').style.display = 'block';
                setActiveLink(this);
                loadPreferences();
            });
            
            // Handle View Courses link
            document.getElementById('view-courses-link').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections(); // Hide all sections first
                document.getElementById('courseExplorerSection').style.display = 'block'; // Show the View Courses section
                setActiveLink(this);
                
                // Load courses if not already loaded
                if (document.getElementById('explorerCourseSelect').options.length <= 1) {
                    loadTeacherCourses();
                }
            });
            
            // Helper function to hide all sections
            function hideAllSections() {
                document.getElementById('profileViewSection').style.display = 'none';
                document.getElementById('profileEditSection').style.display = 'none';
                document.getElementById('preferenceFormSection').style.display = 'none';
                document.getElementById('viewPreferenceSection').style.display = 'none';
                document.getElementById('courseExplorerSection').style.display = 'none'; // Ensure this is hidden
            }
            
            // Helper function to set active link
            function setActiveLink(activeLink) {
                document.querySelectorAll('.sidebar a').forEach(link => {
                    link.classList.remove('active');
                });
                activeLink.classList.add('active');
            }
            
            // Validation functions
            function validateName(name) {
                return /^[A-Za-z\s.]{3,50}$/.test(name);
            }
            
            function validateMobile(mobile) {
                return /^\d{10}$/.test(mobile);
            }
            
            // Add input validation listeners
            const nameInput = document.getElementById('teacher-name');
            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    if (!validateName(this.value)) {
                        this.classList.add('invalid');
                    } else {
                        this.classList.remove('invalid');
                    }
                });
            }
            
            const mobileInput = document.getElementById('teacher-mobile');
            if (mobileInput) {
                mobileInput.addEventListener('input', function() {
                    if (!validateMobile(this.value)) {
                        this.classList.add('invalid');
                    } else {
                        this.classList.remove('invalid');
                    }
                });
            }
        });

        // Profile form submission handler with validation
        document.getElementById('teacherProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all fields
            const nameInput = document.getElementById('teacher-name');
            const mobileInput = document.getElementById('teacher-mobile');
            const genderSelect = document.getElementById('teacher-gender');
            const qualificationInput = document.getElementById('teacher-qualification');
            const subjectInput = document.getElementById('teacher-subject');
            const researchInput = document.getElementById('teacher-research');
            
            // Reset validation states
            const allInputs = [nameInput, mobileInput, genderSelect, qualificationInput, subjectInput, researchInput];
            allInputs.forEach(input => input.classList.remove('invalid'));
            
            // Validate name (letters and spaces only)
            if (!nameInput.value.trim() || !/^[A-Za-z\s.]{3,50}$/.test(nameInput.value)) {
                nameInput.classList.add('invalid');
                nameInput.focus();
                return;
            }
            
            // Validate mobile (10 digits only)
            if (!mobileInput.value.trim() || !/^\d{10}$/.test(mobileInput.value)) {
                mobileInput.classList.add('invalid');
                mobileInput.focus();
                return;
            }
            
            // Validate other required fields
            let hasError = false;
            
            if (!genderSelect.value) {
                genderSelect.classList.add('invalid');
                hasError = true;
            }
            
            if (!qualificationInput.value.trim()) {
                qualificationInput.classList.add('invalid');
                hasError = true;
            }
            
            if (!subjectInput.value.trim()) {
                subjectInput.classList.add('invalid');
                hasError = true;
            }
            
            if (!researchInput.value.trim()) {
                researchInput.classList.add('invalid');
                hasError = true;
            }
            
            if (hasError) {
                return;
            }
            
            // Get form data
            const formData = new FormData(this);
            formData.append('teacher_id', <?php echo $teacherId; ?>);
            
            // Disable form elements during submission
            allInputs.forEach(input => input.disabled = true);
            
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Send request
            fetch('update_teacher_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Profile updated successfully!');
                    // Go back to profile view
                    document.getElementById('profileEditSection').style.display = 'none';
                    document.getElementById('profileViewSection').style.display = 'block';
                    document.getElementById('profile-link').classList.add('active');
                    document.getElementById('edit-profile-link').classList.remove('active');
                    // Reload page to refresh data
                    window.location.reload();
                } else {
                    alert(result.message || 'Error updating profile');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Re-enable form elements
                allInputs.forEach(input => input.disabled = false);
                submitButton.innerHTML = '<i class="fas fa-save"></i> Save Profile';
            });
        });

        // Update this specific event listener only
        document.getElementById('preferenceForm').addEventListener('submit', function(e) {
            // This line is critical - it prevents the default form submission
            e.preventDefault();
            
            console.log('Form submission intercepted by JavaScript');
            
            // Create form data from the form
            const formData = new FormData(this);
            
            // Disable the submit button to prevent multiple submissions
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            // Use AJAX to submit the form
            fetch('save_preference.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Reset form
                    this.reset();
                    // Reset dependent dropdowns
                    document.getElementById('course').disabled = true;
                    document.getElementById('semester').disabled = true;
                    document.getElementById('subject').disabled = true;
                    document.getElementById('course').innerHTML = '<option value="">Select Course</option>';
                    document.getElementById('semester').innerHTML = '<option value="">Select Semester</option>';
                    document.getElementById('subject').innerHTML = '<option value="">Select Subject</option>';
                } else {
                    alert(data.message || 'Error saving preference');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Re-enable the submit button
                submitBtn.disabled = document.getElementById('subject').value === '';
                submitBtn.textContent = 'Save Preference';
            });
        });

        // Add the togglePreference function that was missing
        function togglePreference(preferenceId, newStatus) {
            fetch('toggle_preference.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    preference_id: preferenceId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload preferences to update the list and counts
                    loadPreferences();
                    
                    // Show success message
                    const statusText = newStatus == 1 ? 'disabled' : 'enabled';
                    showAlert(`Preference ${statusText} successfully`, 'success');
                } else {
                    showAlert(data.message || 'Failed to update preference', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'error');
            });
        }
        
        // Helper function to show alerts
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            
            // Insert at the top of the view preference section
            const viewSection = document.getElementById('viewPreferenceSection');
            viewSection.insertBefore(alertDiv, viewSection.firstChild);
            
            // Remove after 3 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Update the loadPreferences function
        function loadPreferences() {
            fetch('get_teacher_preferences.php')
                .then(response => response.json())
                .then(data => {
                    const preferencesList = document.getElementById('preferencesList');
                    const alertBox = document.getElementById('preferenceRequirementAlert');
                    const alertMessage = document.getElementById('preferenceRequirementMessage');
                    preferencesList.innerHTML = '';
                    
                    // Check if preferences requirement is met
                    let missingRequirements = [];
                    const counts = data.counts || { theory: 0, lab: 0 };
                    
                    if (counts.theory < 2) {
                        missingRequirements.push(`You need to add ${2 - counts.theory} more theory subject preference${counts.theory === 1 ? '' : 's'}`);
                    }
                    
                    if (counts.lab < 2) {
                        missingRequirements.push(`You need to add ${2 - counts.lab} more lab subject preference${counts.lab === 1 ? '' : 's'}`);
                    }
                    
                    if (missingRequirements.length > 0) {
                        alertMessage.textContent = missingRequirements.join('. ');
                        alertBox.style.display = 'block';
                    } else {
                        alertBox.style.display = 'none';
                    }
                    
                    const preferences = data.preferences || [];
                    
                    if (!preferences.length) {
                        preferencesList.innerHTML = '<div class="no-preferences">No preferences found</div>';
                        return;
                    }
                    
                    preferences.forEach(pref => {
                        const preferenceCard = document.createElement('div');
                        preferenceCard.className = 'preference-item';
                        
                        const subjectTypeLabel = pref.subject_type.charAt(0).toUpperCase() + pref.subject_type.slice(1);
                        const subjectTypeClass = pref.subject_type === 'theory' ? 'subject-theory' : 'subject-lab';
                        
                        preferenceCard.innerHTML = `
                            <div class="preference-content">
                                <div class="preference-header">
                                    <h3>${pref.subject_name}</h3>
                                    <div class="preference-status">
                                        <span class="subject-type ${subjectTypeClass}">${subjectTypeLabel}</span>
                                        <span class="status-${pref.is_disabled ? 'disabled' : 'enabled'}">
                                            ${pref.is_disabled ? 'Disabled' : 'Enabled'}
                                        </span>
                                    </div>
                                </div>
                                <div class="preference-details">
                                    <div class="preference-detail">
                                        <i class="fas fa-university"></i> Department: ${pref.department_name}
                                    </div>
                                    <div class="preference-detail">
                                        <i class="fas fa-book"></i> Course: ${pref.course_name}
                                    </div>
                                    <div class="preference-detail">
                                        <i class="fas fa-calendar-alt"></i> Semester: ${pref.semester_name}
                                    </div>
                                </div>
                            </div>
                            <div class="preference-actions">
                                <button onclick="togglePreference(${pref.id}, ${pref.is_disabled ? '0' : '1'})" 
                                        class="btn-${pref.is_disabled ? 'enable' : 'disable'}">
                                    ${pref.is_disabled ? 'Enable' : 'Disable'}
                                </button>
                            </div>
                        `;
                        preferencesList.appendChild(preferenceCard);
                    });
                })
                .catch(error => {
                    console.error('Error fetching preferences:', error);
                    document.getElementById('preferencesList').innerHTML = 
                        '<div class="error-message">Failed to load preferences. Please try again.</div>';
                });
        }

        // Add enhanced styling with borders and visual effects
        const styleElement = document.createElement('style');
        styleElement.textContent = `
            #preferencesList {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-top: 20px;
                max-width: 900px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .preference-item {
                background-color: white;
                border-radius: 8px;
                border: 1px solid #e0e0e0;
                box-shadow: 0 3px 10px rgba(0,0,0,0.08);
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: transform 0.2s, box-shadow 0.2s;
                position: relative;
                overflow: hidden;
            }
            
            .preference-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                border-color: #d0d0d0;
            }
            
            .preference-item::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 5px;
                height: 100%;
                background-color: #3498db;
                border-top-left-radius: 8px;
                border-bottom-left-radius: 8px;
            }
            
            .preference-item.theory::before {
                background-color: #1565c0;
            }
            
            .preference-item.lab::before {
                background-color: #e65100;
            }
            
            .preference-content {
                flex: 1;
            }
            
            .preference-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px dashed #eaeaea;
            }
            
            .preference-header h3 {
                margin: 0;
                font-size: 18px;
                color: #2c3e50;
                font-weight: 600;
            }
            
            .preference-details {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-top: 10px;
            }
            
            .preference-detail {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                color: #555;
                background-color: #f9f9f9;
                padding: 6px 12px;
                border-radius: 20px;
                border: 1px solid #eee;
            }
            
            .preference-detail i {
                color: #3498db;
            }
            
            .preference-actions {
                margin-left: 20px;
            }
            
            .no-preferences {
                text-align: center;
                padding: 40px;
                color: #777;
                font-style: italic;
            }
            
            .error-message {
                color: #e74c3c;
                text-align: center;
                padding: 20px;
                background-color: #fdecea;
                border-radius: 8px;
                border: 1px solid #f8d7da;
            }
            
            .alert {
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                animation: fadeIn 0.3s ease-in-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .alert-info {
                background-color: #d1ecf1;
                border-color: #bee5eb;
                color: #0c5460;
            }
            
            .alert-success {
                background-color: #d4edda;
                border-color: #c3e6cb;
                color: #155724;
            }
            
            .alert-error {
                background-color: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24;
            }
            
            .alert i {
                margin-right: 8px;
            }
            
            .preference-status {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            .subject-type {
                font-size: 12px;
                padding: 4px 10px;
                border-radius: 20px;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border: 1px solid transparent;
            }
            
            .subject-theory {
                background-color: #e3f2fd;
                color: #1565c0;
                border-color: #bbdefb;
            }
            
            .subject-lab {
                background-color: #fff3e0;
                color: #e65100;
                border-color: #ffe0b2;
            }
            
            .status-enabled {
                color: #2e7d32;
                font-weight: 500;
            }
            
            .status-disabled {
                color: #c62828;
                font-weight: 500;
            }
            
            .btn-enable, .btn-disable {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.2s;
                box-shadow: 0 1px 3px rgba(0,0,0,0.12);
                letter-spacing: 0.5px;
            }
            
            .btn-enable {
                background-color: #4caf50;
                color: white;
                border: 1px solid #43a047;
            }
            
            .btn-disable {
                background-color: #f44336;
                color: white;
                border: 1px solid #e53935;
            }
            
            .btn-enable:hover {
                background-color: #388e3c;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            
            .btn-disable:hover {
                background-color: #d32f2f;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            
            .btn-enable:active, .btn-disable:active {
                transform: translateY(1px);
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
        `;
        document.head.appendChild(styleElement);

        // Add class to preference items based on subject type when loading
        document.addEventListener('DOMContentLoaded', function() {
            // Make sure this runs after the preferences are loaded and added to the DOM
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        // Apply subject type class to preference items
                        document.querySelectorAll('.preference-item').forEach(item => {
                            const subjectTypeElement = item.querySelector('.subject-type');
                            if (subjectTypeElement) {
                                if (subjectTypeElement.textContent.trim() === 'Theory') {
                                    item.classList.add('theory');
                                } else if (subjectTypeElement.textContent.trim() === 'Lab') {
                                    item.classList.add('lab');
                                }
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.getElementById('preferencesList'), { childList: true });
        });

        // Preference form handling
        document.getElementById('department').addEventListener('change', function() {
            const courseSelect = document.getElementById('course');
            const semesterSelect = document.getElementById('semester');
            const subjectSelect = document.getElementById('subject');
            const deptId = this.value;
            
            // Reset and disable dependent dropdowns
            courseSelect.disabled = true;
            semesterSelect.disabled = true;
            subjectSelect.disabled = true;
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            document.getElementById('submitBtn').disabled = true;
            
            if (deptId) {
                fetch(`teacher_dashboard.php?ajax_request=get_courses&department_id=${deptId}`)
                    .then(response => response.json())
                    .then(data => {
                        courseSelect.disabled = false;
                        data.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.id;
                            option.textContent = course.name;
                            courseSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching courses:', error);
                    });
            }
        });

        document.getElementById('course').addEventListener('change', function() {
            const semesterSelect = document.getElementById('semester');
            const subjectSelect = document.getElementById('subject');
            const courseId = this.value;
            
            semesterSelect.disabled = true;
            subjectSelect.disabled = true;
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            document.getElementById('submitBtn').disabled = true;
            
            if (courseId) {
                fetch(`teacher_dashboard.php?ajax_request=get_semesters&course_id=${courseId}`)
                    .then(response => response.json())
                    .then(data => {
                        semesterSelect.disabled = false;
                        data.forEach(semester => {
                            const option = document.createElement('option');
                            option.value = semester.id;
                            option.textContent = semester.name;
                            semesterSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching semesters:', error);
                    });
            }
        });

        document.getElementById('semester').addEventListener('change', function() {
            const semesterId = this.value;
            const subjectSelect = document.getElementById('subject');
            
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            document.getElementById('submitBtn').disabled = true;
            
            if (semesterId) {
                // First check if teacher already has 2 preferences for this semester
                fetch(`teacher_dashboard.php?ajax_request=check_preferences&semester_id=${semesterId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.count >= 2) {
                            alert('You already have 2 subject preferences for this semester. You cannot add more.');
                            return;
                        }
                        
                        // If less than 2 preferences, proceed to load subjects
                        fetch(`teacher_dashboard.php?ajax_request=get_subjects&semester_id=${semesterId}`)
                            .then(response => response.json())
                            .then(data => {
                                subjectSelect.disabled = false;
                                data.forEach(subject => {
                                    const option = document.createElement('option');
                                    option.value = subject.id;
                                    option.textContent = subject.name;
                                    subjectSelect.appendChild(option);
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching subjects:', error);
                            });
                    })
                    .catch(error => {
                        console.error('Error checking preferences:', error);
                    });
            }
        });

        document.getElementById('subject').addEventListener('change', function() {
            document.getElementById('submitBtn').disabled = !this.value;
        });

        // Add this new function to load teacher courses
        function loadTeacherCourses() {
            console.log('loadTeacherCourses function called');
            
            // Get the teacher's department from the profile
            const teacherDeptId = <?php echo isset($teacherData['department_id']) ? $teacherData['department_id'] : 'null'; ?>;
            
            if (!teacherDeptId) {
                const infoElement = document.getElementById('explorerSubjectInfo');
                if (infoElement) {
                    infoElement.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Please complete your profile to view department courses';
                }
                return;
            }
            
            // Show loading indicator
            const infoElement = document.getElementById('explorerSubjectInfo');
            if (infoElement) {
                infoElement.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Loading courses...';
            }
            
            // Fetch courses for the teacher's department
            fetch(`teacher_dashboard.php?ajax_request=get_courses&department_id=${teacherDeptId}`)
                .then(response => response.json())
                .then(data => {
                    const courseSelect = document.getElementById('explorerCourseSelect');
                    
                    // Clear existing options except the first
                    while (courseSelect.options.length > 1) {
                        courseSelect.remove(1);
                    }
                    
                    if (data.length === 0) {
                        if (infoElement) {
                            infoElement.innerHTML = '<i class="fas fa-info-circle mr-2"></i> No courses found for your department';
                        }
                        return;
                    }
                    
                    // Add each course as an option
                    data.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = course.name;
                        courseSelect.appendChild(option);
                    });
                    
                    if (infoElement) {
                        infoElement.innerHTML = '<i class="fas fa-info-circle mr-2"></i> Select a course and semester to view subjects';
                    }
                })
                .catch(error => {
                    console.error('Error loading courses:', error);
                    if (infoElement) {
                        infoElement.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Error loading courses';
                    }
                });
        }

        console.log('Debug script loaded');

        // Check if elements exist
        console.log('Link exists:', document.getElementById('view-courses-link') !== null);
        console.log('Section exists:', document.getElementById('courseExplorerSection') !== null);

        // Add a very simple click handler that just toggles display
        document.addEventListener('DOMContentLoaded', function() {
            const link = document.getElementById('view-courses-link');
            const section = document.getElementById('courseExplorerSection');
            
            if (link && section) {
                console.log('Both elements found, adding click handler');
                
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Link clicked');
                    
                    // Hide all profile sections first
                    const allSections = document.querySelectorAll('.profile-section');
                    console.log('Found', allSections.length, 'sections to hide');
                    
                    allSections.forEach(s => {
                        s.style.display = 'none';
                        console.log('Hidden section:', s.id);
                    });
                    
                    // Show just our section
                    section.style.display = 'block';
                    console.log('Showing courseExplorerSection');
                    
                    // Update sidebar active state
                    const allLinks = document.querySelectorAll('.sidebar a');
                    allLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    
                    // Try to load courses
                    if (typeof loadTeacherCourses === 'function') {
                        console.log('Calling loadTeacherCourses()');
                        loadTeacherCourses();
                    } else {
                        console.error('loadTeacherCourses function not found');
                        // Basic fallback
                        const info = document.getElementById('explorerSubjectInfo');
                        if (info) {
                            info.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: Could not load courses';
                        }
                    }
                });
            } else {
                console.error('Critical elements not found:', 
                             !link ? 'view-courses-link is missing' : '', 
                             !section ? 'courseExplorerSection is missing' : '');
            }
        });

        // Add this event handler for explorer course select dropdown
        document.getElementById('explorerCourseSelect').addEventListener('change', function() {
            const courseId = this.value;
            const semesterSelect = document.getElementById('explorerSemesterSelect');
            
            // Reset semester select
            semesterSelect.disabled = true;
            semesterSelect.innerHTML = '<option value="">-- Select Semester --</option>';
            
            // Hide subject table
            document.querySelector('.explorer-subject-table-container').style.display = 'none';
            document.getElementById('explorerEmptySubjectsState').style.display = 'none';
            
            // Show info message
            document.getElementById('explorerSubjectInfo').style.display = 'block';
            document.getElementById('explorerSubjectInfo').innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Loading semesters...';
            
            if (courseId) {
                // Fetch semesters for the selected course
                fetch(`teacher_dashboard.php?ajax_request=get_semesters&course_id=${courseId}`)
                    .then(response => response.json())
                    .then(data => {
                        semesterSelect.disabled = false;
                        
                        if (data.length === 0) {
                            document.getElementById('explorerSubjectInfo').innerHTML = 
                                '<i class="fas fa-info-circle mr-2"></i> No semesters found for this course';
                            return;
                        }
                        
                        // Add semesters to dropdown
                        data.forEach(semester => {
                            const option = document.createElement('option');
                            option.value = semester.id;
                            option.textContent = semester.name;
                            semesterSelect.appendChild(option);
                        });
                        
                        document.getElementById('explorerSubjectInfo').innerHTML = 
                            '<i class="fas fa-info-circle mr-2"></i> Select a semester to view subjects';
                    })
                    .catch(error => {
                        console.error('Error fetching semesters:', error);
                        document.getElementById('explorerSubjectInfo').innerHTML = 
                            '<i class="fas fa-exclamation-triangle mr-2"></i> Error loading semesters';
                    });
            } else {
                document.getElementById('explorerSubjectInfo').innerHTML = 
                    '<i class="fas fa-info-circle mr-2"></i> Select a course and semester to view subjects';
            }
        });

        // Add this event handler for explorer semester select dropdown
        document.getElementById('explorerSemesterSelect').addEventListener('change', function() {
            const semesterId = this.value;
            
            // Hide subject table initially
            document.querySelector('.explorer-subject-table-container').style.display = 'none';
            document.getElementById('explorerEmptySubjectsState').style.display = 'none';
            
            // Show loading message
            document.getElementById('explorerSubjectInfo').style.display = 'block';
            document.getElementById('explorerSubjectInfo').innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Loading subjects...';
            
            if (semesterId) {
                // Fetch all subjects for the selected semester
                fetch(`teacher_dashboard.php?ajax_request=get_all_semester_subjects&semester_id=${semesterId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Hide info message
                        document.getElementById('explorerSubjectInfo').style.display = 'none';
                        
                        if (Array.isArray(data) && data.length > 0) {
                            // Populate subject table
                            const tableBody = document.getElementById('explorerSubjectTableBody');
                            tableBody.innerHTML = '';
                            
                            data.forEach(subject => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${subject.name}</td>
                                    <td class="text-center">${subject.subject_type.charAt(0).toUpperCase() + subject.subject_type.slice(1)}</td>
                                    <td class="text-center">${subject.has_credits ? subject.credit_points : 'N/A'}</td>
                                `;
                                tableBody.appendChild(row);
                            });
                            
                            // Show subject table
                            document.querySelector('.explorer-subject-table-container').style.display = 'block';
                        } else {
                            // Show empty state
                            document.getElementById('explorerEmptySubjectsState').style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching subjects:', error);
                        document.getElementById('explorerSubjectInfo').innerHTML = 
                            '<i class="fas fa-exclamation-triangle mr-2"></i> Error loading subjects';
                        document.getElementById('explorerSubjectInfo').style.display = 'block';
                    });
            } else {
                document.getElementById('explorerSubjectInfo').innerHTML = 
                    '<i class="fas fa-info-circle mr-2"></i> Select a semester to view subjects';
            }
        });

        // Add styling to improve the appearance of the View Courses section
        document.addEventListener('DOMContentLoaded', function() {
            const style = document.createElement('style');
            style.textContent = `
                /* Main container styling */
                #courseExplorerSection {
                    background-color: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    padding: 20px;
                    margin: 20px 0;
                }
                
                /* Main heading */
                #courseExplorerSection h2 {
                    font-size: 24px;
                    color: #2c3e50;
                    margin-bottom: 15px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #3498db;
                }
                
                /* Sub heading */
                #courseExplorerSection h5 {
                    font-size: 18px;
                    color: #34495e;
                    margin-bottom: 10px;
                }
                
                /* Description text */
                #courseExplorerSection p {
                    color: #7f8c8d;
                    margin-bottom: 20px;
                }
                
                /* Select labels */
                #courseExplorerSection label {
                    display: block;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 8px;
                }
                
                /* Select boxes */
                #courseExplorerSection select {
                    width: 100%;
                    padding: 10px 15px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    background-color: #f9f9f9;
                    margin-bottom: 20px;
                    font-size: 15px;
                    color: #333;
                    box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
                }
                
                #courseExplorerSection select:focus {
                    border-color: #3498db;
                    box-shadow: 0 0 0 3px rgba(52,152,219,0.25);
                    outline: none;
                }
                
                /* Helper text */
                #courseExplorerSection .choose-text {
                    color: #7f8c8d;
                    font-size: 14px;
                    margin: 10px 0 20px 0;
                }
                
                /* Subject list header */
                #courseExplorerSection .subject-list-header {
                    font-weight: 600;
                    color: #2c3e50;
                    margin: 20px 0 10px 0;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #eee;
                }
                
                /* Subject table */
                #courseExplorerSection table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    border-radius: 6px;
                    overflow: hidden;
                }
                
                #courseExplorerSection th {
                    background-color: #f4f7f9;
                    padding: 12px 15px;
                    text-align: left;
                    color: #34495e;
                    font-weight: 600;
                    border-bottom: 2px solid #ddd;
                }
                
                #courseExplorerSection td {
                    padding: 12px 15px;
                    border-bottom: 1px solid #ecf0f1;
                }
                
                #courseExplorerSection tr:last-child td {
                    border-bottom: none;
                }
                
                #courseExplorerSection tr:hover td {
                    background-color: #f8f9fa;
                }
                
                /* Credit styling */
                #courseExplorerSection .credits {
                    text-align: center;
                    font-weight: 600;
                    color: #3498db;
                }
                
                /* Theory badge */
                #courseExplorerSection .theory-badge {
                    display: inline-block;
                    background-color: #e3f2fd;
                    color: #1565c0;
                    padding: 4px 10px;
                    border-radius: 4px;
                    font-size: 13px;
                }
                
                /* Apply styles to the existing elements */
                window.addEventListener('load', function() {
                    // Add classes and styling to existing elements
                    document.querySelectorAll('#courseExplorerSection p').forEach(p => {
                        if (p.textContent.includes('Choose a')) {
                            p.className = 'choose-text';
                        }
                    });
                    
                    document.querySelectorAll('#courseExplorerSection strong, #courseExplorerSection b').forEach(el => {
                        if (el.textContent.includes('Subject List')) {
                            el.className = 'subject-list-header';
                        }
                    });
                    
                    // Add styling to the table cells for theory and credits
                    const cells = document.querySelectorAll('#courseExplorerSection td');
                    cells.forEach(cell => {
                        if (cell.textContent.trim() === 'Theory') {
                            cell.innerHTML = '<span class="theory-badge">Theory</span>';
                        }
                        
                        // Try to identify credit cells (usually numbers like 7, 9)
                        if (/^[0-9]+$/.test(cell.textContent.trim())) {
                            cell.className = 'credits';
                        }
                    });
                });
            `;
            
            document.head.appendChild(style);
        });

        $(document).ready(function() {
            // Keep all existing code intact
            
            // Add these new event handlers and functions
            
            // Schedule view link
            $("#view-schedule-link").click(function(e) {
                e.preventDefault();
                // Hide all sections first
                $("#profileViewSection, #profileEditSection, #addPreferenceSection, #viewPreferencesSection, #courseExplorerSection, #schedule-section").hide();
                
                // Show schedule section
                $("#schedule-section").show();
                
                // Update active state of sidebar links
                $(".sidebar a").removeClass("active");
                $(this).addClass("active");
                
                // Load schedule if not already loaded
                if ($("#semesterSelect").val() === "") {
                    loadTeacherSchedule();
                }
            });
            
            // Semester select change event
            $("#semesterSelect").change(function() {
                loadTeacherSchedule($(this).val());
            });
            
            // Function to load teacher schedule
            function loadTeacherSchedule(semesterId = "") {
                $("#scheduleContainer").html('<div class="loading-spinner text-center"><i class="fas fa-spinner fa-spin"></i> Loading your schedule...</div>');
                
                $.ajax({
                    url: "get_teacher_schedule.php",
                    type: "GET",
                    data: { semester_id: semesterId },
                    dataType: "json",
                    success: function(response) {
                        if (response.success && response.schedules && response.schedules.length > 0) {
                            displaySchedule(response.schedules);
                        } else {
                            $("#scheduleContainer").html('<div class="alert alert-info">No schedule found for the selected semester.</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading schedule:", error);
                        $("#scheduleContainer").html('<div class="alert alert-danger">Failed to load schedule. Please try again.</div>');
                    }
                });
            }
            
            // Function to display the schedule
            function displaySchedule(schedules) {
                const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
                let html = '<div class="schedule-container">';
                
                days.forEach(function(day) {
                    const daySchedules = schedules.filter(s => s.day_of_week === day);
                    
                    if (daySchedules.length > 0) {
                        html += `
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">${day}</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th width="10%">Hour</th>
                                                <th width="25%">Subject</th>
                                                <th width="25%">Course</th>
                                                <th width="15%">Semester</th>
                                                <th width="10%">Type</th>
                                                <th width="15%">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
                        
                        // Sort by hour
                        daySchedules.sort((a, b) => a.hour - b.hour);
                        
                        daySchedules.forEach(function(schedule) {
                            const typeLabel = schedule.is_theory == 1 ? 'Theory' : 'Lab';
                            const badgeClass = schedule.is_theory == 1 ? 'badge-primary' : 'badge-warning';
                            
                            html += `
                            <tr ${schedule.is_enabled == 0 ? 'class="table-secondary text-muted"' : ''}>
                                <td>Hour ${schedule.hour}</td>
                                <td>${schedule.subject_name}</td>
                                <td>${schedule.course_name}</td>
                                <td>${schedule.semester_name}</td>
                                <td><span class="badge ${badgeClass}">${typeLabel}</span></td>
                                <td>${schedule.is_enabled == 1 ? 
                                    '<span class="badge badge-success">Active</span>' : 
                                    '<span class="badge badge-secondary">Disabled</span>'}</td>
                            </tr>`;
                        });
                        
                        html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>`;
                    }
                });
                
                html += '</div>';
                $("#scheduleContainer").html(html);
            }
        });

        // Modify your existing click handlers for other links to also hide schedule section
        $("#profile-link").click(function(e) {
            e.preventDefault();
            $("#schedule-section").hide();  // Add this line
            $("#profileViewSection").show();
            $("#profileEditSection, #addPreferenceSection, #viewPreferencesSection, #courseExplorerSection").hide();
            $(".sidebar a").removeClass("active");
            $(this).addClass("active");
        });

        $("#edit-profile-link").click(function(e) {
            e.preventDefault();
            $("#schedule-section").hide();  // Add this line
            $("#profileEditSection").show();
            $("#profileViewSection, #addPreferenceSection, #viewPreferencesSection, #courseExplorerSection").hide();
            $(".sidebar a").removeClass("active");
            $(this).addClass("active");
        });

        $("#add-preference-link").click(function(e) {
            e.preventDefault();
            $("#schedule-section").hide();  // Add this line
            $("#addPreferenceSection").show();
            $("#profileViewSection, #profileEditSection, #viewPreferencesSection, #courseExplorerSection").hide();
            $(".sidebar a").removeClass("active");
            $(this).addClass("active");
        });

        $("#view-preferences-link").click(function(e) {
            e.preventDefault();
            $("#schedule-section").hide();  // Add this line
            $("#viewPreferencesSection").show();
            $("#profileViewSection, #profileEditSection, #addPreferenceSection, #courseExplorerSection").hide();
            $(".sidebar a").removeClass("active");
            $(this).addClass("active");
        });

        $("#view-courses-link").click(function(e) {
            e.preventDefault();
            $("#schedule-section").hide();  // Add this line
            $("#courseExplorerSection").show();
            $("#profileViewSection, #profileEditSection, #addPreferenceSection, #viewPreferencesSection").hide();
            $(".sidebar a").removeClass("active");
            $(this).addClass("active");
            loadTeacherCourses();
        });
    </script>
</body>
</html> 
