<?php
session_start();
require_once 'config.php';  // Add this line to include database configuration

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: login.php");
    exit;
}

// Get user email from session
$userEmail = $_SESSION['email'] ?? 'Unknown User';

// Get HOD-specific data using the logged-in user's ID
$hodId = $_SESSION['user_id'];

// Example of getting HOD-specific data (add this where needed)
function getHodData($conn, $hodId) {
    $stmt = $conn->prepare("
        SELECT h.*, u.email 
        FROM hod h 
        JOIN users u ON h.user_id = u.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $hodId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Add these AJAX endpoints to your PHP at the top (within the existing if(isset($_GET['ajax_request'])) section)
// Get courses for HOD's department
if (isset($_GET['ajax_request']) && $_GET['ajax_request'] == 'get_courses_for_hod') {
    header('Content-Type: application/json');
    try {
        $conn = connectDB();
        
        // Get department_id of logged-in HOD
        // Check if the table name is 'hod' or 'hods'
        try {
            $stmt = $conn->prepare("SELECT department_id FROM hods WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Table 'hods' not found");
            }
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
        } catch (Exception $e) {
            // Try with 'hod' table if 'hods' failed
            $stmt = $conn->prepare("SELECT department_id FROM hod WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Neither 'hod' nor 'hods' table found");
            }
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
        }
        
        if ($result->num_rows === 0) {
            throw new Exception("No department found for HOD user_id: " . $_SESSION['user_id']);
        }
        
        $row = $result->fetch_assoc();
        $department_id = $row['department_id'];
        
        // Get courses for this department
        $stmt = $conn->prepare("SELECT id, name, code, course_type FROM courses WHERE department_id = ? AND is_disabled = 0 ORDER BY name");
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        echo json_encode($courses);
    } catch (Exception $e) {
        error_log("HOD Dashboard Error: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Get semesters for a course
if (isset($_GET['ajax_request']) && $_GET['ajax_request'] == 'get_semesters_for_course' && isset($_GET['course_id'])) {
    header('Content-Type: application/json');
    $course_id = intval($_GET['course_id']);
    try {
        $conn = connectDB();
        $stmt = $conn->prepare("SELECT id, name, start_date, end_date FROM semesters WHERE course_id = ? AND is_disabled = 0 ORDER BY name");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $semesters = [];
        while ($row = $result->fetch_assoc()) {
            $semesters[] = $row;
        }
        
        echo json_encode($semesters);
    } catch (Exception $e) {
        error_log("HOD Dashboard Error: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Get subjects for a semester
if (isset($_GET['ajax_request']) && $_GET['ajax_request'] == 'get_subjects_for_semester' && isset($_GET['semester_id'])) {
    header('Content-Type: application/json');
    $semester_id = intval($_GET['semester_id']);
    try {
        $conn = connectDB();
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
        error_log("HOD Dashboard Error: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

try {
    // Establish database connection
    $conn = connectDB();
    
    // Use this function when displaying or modifying HOD data
    $hodData = getHodData($conn, $hodId);

    // Get department ID of logged-in HOD
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if we got a result
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $department_id = $row['department_id'];
    } else {
        // Default value if no department is found
        $department_id = null;
        error_log("No department found for HOD with user_id: " . $_SESSION['user_id']);
    }

    // Only proceed with designation query if we have a department_id
    if ($department_id) {
        // Get all designations and their total workload
        $designation_query = "SELECT 
            d.designation,
            COALESCE(SUM(dw.weekly_hours), 0) as total_hours,
            CASE 
                WHEN d.designation = 'Junior Assistant Professor' THEN 22
                WHEN d.designation = 'Senior Assistant Professor' THEN 20
                WHEN d.designation = 'Associate Professor' THEN 18
                WHEN d.designation = 'HOD' THEN 16
            END as max_hours
        FROM (
            SELECT DISTINCT designation 
            FROM teachers 
            WHERE department_id = ?
        ) d
        LEFT JOIN designation_workload dw 
            ON d.designation = dw.designation 
            AND dw.department_id = ?
            AND dw.is_enabled = 1
        GROUP BY d.designation";

        $stmt = $conn->prepare($designation_query);
        $stmt->bind_param("ii", $department_id, $department_id);
        $stmt->execute();
        $designations = $stmt->get_result();
    } else {
        // Create an empty result set or handle the lack of department_id
        $designations = null;
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | AssignXpert</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Header styling */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2c3e50;
            color: white;
            padding: 15px 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 0;
            padding-bottom: 0;
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

        /* Dashboard header title */
        .dashboard-title {
            background-color: #34495e;
            color: white;
            padding: 15px 25px;
            font-size: 20px;
            font-weight: 600;
            margin-top: 0;
        }

        /* Sidebar styling */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100%;
            background-color: #2c3e50;
            padding-top: 60px;
            z-index: 1;
            margin-top: -1px;
            border-top: none;
        }

        .sidebar a {
            display: block;
            color: #ecf0f1;
            padding: 12px 25px;
            margin: 5px 0;
            text-decoration: none;
            transition: 0.3s;
            font-size: 15px;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #3498db;
        }

        .sidebar a.active {
            background-color: #3498db;
            border-left-color: #2980b9;
            color: white;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content area */
        .main-content {
            margin-left: 240px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-top: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
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
        }

        /* Sidebar styles */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2c3e50;
            padding-top: 60px;
        }

        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 16px;
            color: white;
            display: block;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        /* Main content styles */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .content-wrapper {
            margin-top: 60px;
            padding: 20px;
        }

        .table {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .card {
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* Profile styles for HOD Dashboard */
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
            position: relative;
            overflow: hidden;
            border-left: 4px solid #3498db;
        }

        .profile-info-item::before {
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
        
        /* Add these styles to your CSS section */
        .dashboard-header {
            margin-bottom: 25px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }

        .brand-container {
            display: flex;
            flex-direction: column;
        }

        .brand-container h2 {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
            position: relative;
        }

        .brand-container h2:after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background: #3498db;
            margin-top: 8px;
        }

        .brand-tagline {
            color: #7f8c8d;
            font-size: 16px;
            font-weight: 400;
            margin-top: 0;
        }

        #viewTeachersSection .profile-container {
            max-width: 100%;
            margin: 0;
        }

        #viewTeachersSection .profile-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 25px 30px;
        }

        #viewTeachersSection .profile-title h3 {
            color: white;
            font-size: 22px;
            margin-bottom: 5px;
        }

        #viewTeachersSection .profile-title p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }

        #viewTeachersSection .profile-card {
            background: white;
            padding: 25px;
            border-radius: 0 0 8px 8px;
        }

        #viewTeachersSection .department-info {
            background-color: rgba(52, 152, 219, 0.1);
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        #viewTeachersSection .table {
            margin-bottom: 0;
        }

        #viewTeachersSection .badge-designation {
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
        }

        #viewTeachersSection .expertise-chip {
            background-color: #f1f1f1;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 12px;
            color: #2c3e50;
        }

        #viewTeachersSection .btn-view {
            background-color: #3498db;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.3s;
        }

        #viewTeachersSection .btn-view:hover {
            background-color: #2980b9;
            text-decoration: none;
            color: white;
        }

        #teacherDetailsSection .profile-container {
            max-width: 100%;
            margin: 0;
        }

        #teacherDetailsSection .profile-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 25px 30px;
            position: relative;
        }

        #teacherDetailsSection .profile-title h3 {
            color: white;
            font-size: 22px;
            margin-bottom: 5px;
        }

        #teacherDetailsSection .profile-title p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }

        #teacherDetailsSection .profile-actions {
            position: absolute;
            top: 25px;
            right: 30px;
        }

        #teacherDetailsSection .btn-edit {
            background: rgba(255,255,255,0.15);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        #teacherDetailsSection .btn-edit:hover {
            background: rgba(255,255,255,0.25);
        }

        #teacherDetailsSection .profile-card {
            background: white;
            padding: 25px;
            border-radius: 0 0 8px 8px;
        }

        .teacher-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .teacher-detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }

        .detail-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 15px;
            color: #2c3e50;
            font-weight: 500;
        }

        #departmentPreferencesSection .profile-container {
            max-width: 100%;
            margin: 0;
        }

        #departmentPreferencesSection .profile-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 25px 30px;
        }

        #departmentPreferencesSection .profile-title h3 {
            color: white;
            font-size: 22px;
            margin-bottom: 5px;
        }

        #departmentPreferencesSection .profile-title p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }

        #departmentPreferencesSection .profile-card {
            background: white;
            padding: 25px;
            border-radius: 0 0 8px 8px;
        }

        #manageWorkloadSection .profile-container {
            max-width: 100%;
            margin: 0;
        }

        #manageWorkloadSection .profile-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 25px 30px;
        }

        #manageWorkloadSection .profile-title h3 {
            color: white;
            font-size: 22px;
            margin-bottom: 5px;
        }

        #manageWorkloadSection .profile-title p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }

        #manageWorkloadSection .profile-card {
            background: white;
            padding: 25px;
            border-radius: 0 0 8px 8px;
        }

        #subjectWorkloadSection .profile-container {
            max-width: 100%;
            margin: 0;
        }

        #subjectWorkloadSection .profile-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 25px 30px;
        }

        #subjectWorkloadSection .profile-title h3 {
            color: white;
            font-size: 22px;
            margin-bottom: 5px;
        }

        #subjectWorkloadSection .profile-title p {
            color: rgba(255, 255, 255, 0.8);
            margin: 0;
        }

        #subjectWorkloadSection .profile-card {
            background: white;
            padding: 25px;
            border-radius: 0 0 8px 8px;
        }

        /* Custom styles for course data section */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }
        
        .custom-select {
            background-position: right 0.75rem center;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .custom-select:hover {
            border-color: #4e73df;
        }
        
        /* Table row hover effect */
        #subjectTableBody tr {
            transition: all 0.2s;
        }
        
        #subjectTableBody tr:hover {
            background-color: #f0f7ff;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        /* Badge styles for subject types */
        .badge-theory {
            background-color: #36b9cc;
            color: white;
            font-weight: 500;
            padding: 0.5em 0.75em;
        }
        
        .badge-lab {
            background-color: #1cc88a;
            color: white;
            font-weight: 500;
            padding: 0.5em 0.75em;
        }
        
        .badge-credits {
            background-color: #f6c23e;
            color: #2e2f37;
            font-weight: 600;
            padding: 0.4em 0.7em;
            border-radius: 50px;
        }

        .sidebar h2 {
            color: white;
            padding: 15px 25px;
            margin-top: 0;
            font-size: 20px;
            font-weight: 600;
            background-color: #34495e;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Header in matching style -->
    <div class="header">
        <h1>ASSIGNXPERT</h1>
        <div class="user-info">
            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($userEmail); ?></span>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

    </div>

    <!-- Dashboard Title -->
    <div class="dashboard-title">
        HOD Dashboard
    </div>

    <!-- Sidebar with Navigation - STANDARD SIDEBAR FOR ALL HOD PAGES -->
    <div class="sidebar">
    <h2>HOD Dashboard</h2>
        <a href="hod_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
        <a href="view_teachers.php"><i class="fas fa-chalkboard-teacher"></i> View Teachers</a>
        <a href="department_preferences.php"><i class="fas fa-users"></i> All Preferences</a>
        <a href="manage_workload.php"><i class="fas fa-tasks"></i> Manage Workload</a>
        <a href="subject_workload.php"><i class="fas fa-book"></i> Subject Workload</a>
        <a href="teacher_scheduling.php"><i class="fas fa-calendar-alt"></i> Teacher Scheduling</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <a href="#" id="viewCourseDataLink"><i class="fas fa-book"></i> View Courses</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- HOD Profile Section -->
        <div class="profile-section" id="profileSection">
            <div class="profile-container">
                <?php
                // Check if profile is complete
                $profileCompleted = isset($hodData) && 
                                   !empty($hodData['full_name']) && 
                                   !empty($hodData['mobile']) && 
                                   !empty($hodData['gender']);
                ?>
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-title">
                        <h3><?php echo $profileCompleted ? htmlspecialchars($hodData['full_name']) : htmlspecialchars($userEmail); ?></h3>
                        
                        <?php if ($profileCompleted): ?>
                            <?php 
                            // Fetch department name
                            $dept_query = "SELECT d.name FROM departments d WHERE d.id = ?";
                            $stmt = $conn->prepare($dept_query);
                            $stmt->bind_param("i", $hodData['department_id']);
                            $stmt->execute();
                            $department = $stmt->get_result()->fetch_assoc();
                            $departmentName = $department ? $department['name'] : 'Unknown Department';
                            ?>
                            <p class="profile-designation">
                                <i class="fas fa-id-badge mr-1"></i> Head of Department | 
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
                        <a href="#" id="goToEditBtn" class="btn-edit" onclick="showEditProfile()">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
                
                <?php if ($profileCompleted): ?>
                <div class="profile-card">
                    <div class="profile-info-grid">
                        <div class="profile-info-item">
                            <div class="info-label"><i class="fas fa-phone"></i> Mobile Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($hodData['mobile']); ?></div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-label"><i class="fas fa-venus-mars"></i> Gender</div>
                            <div class="info-value"><?php echo htmlspecialchars(ucfirst($hodData['gender'])); ?></div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-label"><i class="fas fa-graduation-cap"></i> Qualification</div>
                            <div class="info-value"><?php echo !empty($hodData['qualification']) ? htmlspecialchars($hodData['qualification']) : 'Not specified'; ?></div>
                        </div>
                        <div class="profile-info-item">
                            <div class="info-label"><i class="fas fa-book"></i> Subject Expertise</div>
                            <div class="info-value"><?php echo !empty($hodData['subject_expertise']) ? htmlspecialchars($hodData['subject_expertise']) : 'Not specified'; ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($hodData['research'])): ?>
                    <div class="profile-research">
                        <div class="info-label"><i class="fas fa-flask"></i> Research Interests</div>
                        <div class="research-text"><?php echo htmlspecialchars($hodData['research']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="profile-card">
                    <div class="profile-incomplete-message">
                        <i class="fas fa-user-edit"></i>
                        <h3>Your Profile is Incomplete</h3>
                        <p>Please complete your profile information to enhance collaboration and ensure we have your correct details.</p>
                        <a href="#" class="btn-complete-profile" onclick="showEditProfile()">Complete Your Profile</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit Profile Section (Initially Hidden) -->
        <div class="profile-section" id="profileEditSection" style="display: none;">
            <div class="profile-container">
                <div class="profile-header edit-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="profile-title">
                        <h3>Edit Your Profile</h3>
                        <p>Update your personal and professional information</p>
                    </div>
                </div>
                <div class="profile-card">
                    <form id="hodProfileForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($hodData['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="mobile"><i class="fas fa-phone"></i> Mobile Number</label>
                                <input type="tel" id="mobile" name="mobile" class="form-control" value="<?php echo htmlspecialchars($hodData['mobile'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (isset($hodData['gender']) && $hodData['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($hodData['gender']) && $hodData['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo (isset($hodData['gender']) && $hodData['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="qualification"><i class="fas fa-graduation-cap"></i> Qualification</label>
                                <input type="text" id="qualification" name="qualification" class="form-control" value="<?php echo htmlspecialchars($hodData['qualification'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject_expertise"><i class="fas fa-book"></i> Subject Expertise</label>
                            <input type="text" id="subject_expertise" name="subject_expertise" class="form-control" value="<?php echo htmlspecialchars($hodData['subject_expertise'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="research"><i class="fas fa-flask"></i> Research Interests</label>
                            <textarea id="research" name="research" class="form-control" rows="4"><?php echo htmlspecialchars($hodData['research'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-buttons">
                            <button type="button" class="btn-cancel" onclick="hideEditProfile()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add this new section after your profile sections -->
        <div id="courseDataSection" class="profile-section" style="display: none;">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white py-3">
                    <h5 class="mb-0"><i class="fas fa-book-open mr-2"></i> Course Explorer</h5>
                </div>
                <div class="card-body">
                    <!-- Course Selection Area -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="courseSelect" class="font-weight-bold text-primary">
                                    <i class="fas fa-graduation-cap mr-1"></i> Select Course:
                                </label>
                                <select id="courseSelect" class="form-control form-control-lg custom-select">
                                    <option value="">-- Select Course --</option>
                                </select>
                                <small class="form-text text-muted">Choose a course to view its semesters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="semesterSelect" class="font-weight-bold text-primary">
                                    <i class="fas fa-clock mr-1"></i> Select Semester:
                                </label>
                                <select id="semesterSelect" class="form-control form-control-lg custom-select" disabled>
                                    <option value="">-- Select Semester --</option>
                                </select>
                                <small class="form-text text-muted">Choose a semester to view its subjects</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Alert Area -->
                    <div class="alert alert-info shadow-sm border-left border-info" style="border-left-width: 4px !important;" id="subjectInfo">
                        <i class="fas fa-info-circle mr-2"></i> Select a course and semester to view subjects
                    </div>
                    
                    <!-- Subject Table Container with transition effect -->
                    <div class="subject-table-container mt-4" style="display: none; transition: all 0.3s ease;">
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
                                        <tbody id="subjectTableBody">
                                            <!-- Subject data will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state for when no subjects are found -->
                    <div id="emptySubjectsState" class="text-center py-5 mt-3" style="display: none;">
                        <i class="fas fa-search text-muted fa-3x mb-3"></i>
                        <h5 class="text-muted">No subjects found</h5>
                        <p class="text-muted">Try selecting a different semester or course</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Teachers Section (Initially Hidden) -->
        <div class="profile-section" id="viewTeachersSection" style="display: none;">
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-title">
                        <h3><i class="fas fa-chalkboard-teacher"></i> Department Teachers</h3>
                        <p>View and manage teachers in your department</p>
                    </div>
                </div>
                <div class="profile-card">
                    <div id="teachersContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Teacher Details Section (Initially Hidden) -->
        <div class="profile-section" id="teacherDetailsSection" style="display: none;">
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-title">
                        <h3><i class="fas fa-user-circle"></i> Teacher Details</h3>
                        <p>Detailed information about the teacher</p>
                    </div>
                    <div class="profile-actions">
                        <button class="btn-edit" onclick="backToTeachers()">
                            <i class="fas fa-arrow-left"></i> Back to Teachers List
                        </button>
                    </div>
                </div>
                <div class="profile-card">
                    <div id="teacherDetailsContent">
                        <!-- Teacher details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Preferences Section (Initially Hidden) -->
        <div class="profile-section" id="departmentPreferencesSection" style="display: none;">
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-title">
                        <h3><i class="fas fa-users"></i> Department Preferences</h3>
                        <p>View all preferences submitted by teachers in your department</p>
                    </div>
                </div>
                <div class="profile-card">
                    <div id="preferencesContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Workload Section (Initially Hidden) -->
        <div class="profile-section" id="manageWorkloadSection" style="display: none;">
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-title">
                        <h3><i class="fas fa-tasks"></i> Manage Workload</h3>
                        <p>View and manage teacher workload in your department</p>
                    </div>
                </div>
                <div class="profile-card">
                    <div id="workloadContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject Workload Section (Initially Hidden) -->
        <div class="profile-section" id="subjectWorkloadSection" style="display: none;">
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-title">
                        <h3><i class="fas fa-book"></i> Subject Workload</h3>
                        <p>Manage and view subject workload distribution</p>
                    </div>
                </div>
                <div class="profile-card">
                    <div id="subjectWorkloadContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- Close of main-content -->

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function addWorkload(designation, maxHours, currentHours) {
            const remainingHours = maxHours - currentHours;
            
            if (remainingHours <= 0) {
                alert('Maximum workload limit reached for this designation!');
                return;
            }
            
            window.location.href = `add_workload.php?designation=${encodeURIComponent(designation)}&remaining=${remainingHours}`;
        }

        function showEditProfile() {
            document.getElementById('profileSection').style.display = 'none';
            document.getElementById('profileEditSection').style.display = 'block';
        }

        function hideEditProfile() {
            document.getElementById('profileEditSection').style.display = 'none';
            document.getElementById('profileSection').style.display = 'block';
        }

        // Form submission
        $(document).ready(function() {
            $('#hodProfileForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'update_hod_profile.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Profile updated successfully!');
                            location.reload(); // Reload to see the updated profile
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
            
            // Handle view course data link click
            $('#viewCourseDataLink').click(function(e) {
                e.preventDefault();
                
                // Hide other sections
                $('.profile-section').hide();
                
                // Show course data section
                $('#courseDataSection').show();
                
                // Update active state in sidebar
                $('.sidebar a').removeClass('active');
                $(this).addClass('active');
                
                // Load courses if not already loaded
                if ($('#courseSelect option').length <= 1) {
                    loadCoursesForHOD();
                }
            });
            
            function loadCoursesForHOD() {
                $.ajax({
                    url: 'hod_dashboard.php?ajax_request=get_courses_for_hod',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            console.error('Server error:', data.error);
                            alert('Error loading courses: ' + data.error);
                            return;
                        }
                        
                        const courseSelect = $('#courseSelect');
                        courseSelect.find('option:not(:first)').remove();
                        
                        if (data.length === 0) {
                            $('#subjectInfo').text('No courses found for your department');
                            return;
                        }
                        
                        $.each(data, function(i, course) {
                            courseSelect.append($('<option>', {
                                value: course.id,
                                text: course.name + ' (' + course.code + ')'
                            }));
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        console.log('Response:', xhr.responseText);
                        alert('Error loading courses. Check console for details.');
                    }
                });
            }
            
            // Handle course selection change
            $('#courseSelect').change(function() {
                const courseId = $(this).val();
                const semesterSelect = $('#semesterSelect');
                
                // Reset semester select and subject info
                semesterSelect.find('option:not(:first)').remove();
                semesterSelect.prop('disabled', !courseId);
                $('#subjectInfo').text('Select a semester to view subjects');
                $('.subject-table-container').hide();
                
                if (courseId) {
                    // Load semesters for this course
                    $.ajax({
                        url: `hod_dashboard.php?ajax_request=get_semesters_for_course&course_id=${courseId}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data.error) {
                                alert('Error: ' + data.error);
                                return;
                            }
                            
                            if (data.length === 0) {
                                $('#subjectInfo').text('No semesters found for this course');
                                return;
                            }
                            
                            $.each(data, function(i, semester) {
                                semesterSelect.append($('<option>', {
                                    value: semester.id,
                                    text: semester.name
                                }));
                            });
                        },
                        error: function() {
                            alert('Error loading semesters');
                        }
                    });
                }
            });
            
            // Handle semester selection change
            $('#semesterSelect').change(function() {
                const semesterId = $(this).val();
                
                if (semesterId) {
                    // Show loading animation
                    $('#subjectInfo').html('<i class="fas fa-circle-notch fa-spin mr-2"></i> Loading subjects...');
                    
                    // Load subjects for this semester
                    $.ajax({
                        url: `hod_dashboard.php?ajax_request=get_subjects_for_semester&semester_id=${semesterId}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data.error) {
                                alert('Error: ' + data.error);
                                $('#subjectInfo').html('<i class="fas fa-exclamation-circle mr-2"></i> Error loading subjects');
                                $('.subject-table-container').hide();
                                $('#emptySubjectsState').hide();
                                return;
                            }
                            
                            if (data.length > 0) {
                                $('#subjectInfo').html(`<i class="fas fa-check-circle mr-2"></i> Found ${data.length} subjects for this semester`);
                                
                                // Populate subject table
                                const subjectTableBody = $('#subjectTableBody');
                                subjectTableBody.empty();
                                
                                $.each(data, function(i, subject) {
                                    const credits = subject.has_credits ? subject.credit_points : 'N/A';
                                    const subjectType = subject.subject_type === 'theory' ? 
                                        '<span class="badge badge-theory">Theory</span>' : 
                                        '<span class="badge badge-lab">Lab</span>';
                                    const creditBadge = subject.has_credits ? 
                                        `<span class="badge badge-credits">${subject.credit_points}</span>` : 
                                        '<span class="text-muted">N/A</span>';
                                    
                                    subjectTableBody.append(`
                                        <tr>
                                            <td class="font-weight-medium">${subject.name}</td>
                                            <td class="text-center">${subjectType}</td>
                                            <td class="text-center">${creditBadge}</td>
                                        </tr>
                                    `);
                                });
                                
                                $('.subject-table-container').fadeIn(300);
                                $('#emptySubjectsState').hide();
                            } else {
                                $('#subjectInfo').html('<i class="fas fa-info-circle mr-2"></i> No subjects found for this semester');
                                $('.subject-table-container').hide();
                                $('#emptySubjectsState').fadeIn(300);
                            }
                        },
                        error: function() {
                            alert('Error loading subjects');
                            $('#subjectInfo').html('<i class="fas fa-exclamation-triangle mr-2"></i> Error loading subjects');
                            $('.subject-table-container').hide();
                            $('#emptySubjectsState').hide();
                        }
                    });
                } else {
                    $('#subjectInfo').html('<i class="fas fa-info-circle mr-2"></i> Select a semester to view subjects');
                    $('.subject-table-container').hide();
                    $('#emptySubjectsState').hide();
                }
            });

            // Handle view teachers link click
            $('a[href="view_teachers.php"]').click(function(e) {
                e.preventDefault();
                
                // Hide content sections but NOT the dashboard structure
                $('.profile-section').hide();
                
                // Show teachers section and load content
                $('#viewTeachersSection').show();
                
                // Update active state in sidebar
                $('.sidebar a').removeClass('active');
                $(this).addClass('active');
                
                // Show loading indicator
                $('#teachersContent').html(`
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading teachers data...</p>
                    </div>
                `);
                
                // Load view_teachers.php content
                $.ajax({
                    url: 'view_teachers.php',
                    type: 'GET',
                    success: function(response) {
                        // Extract just the table content without the department info
                        const tableContent = $(response).find('.table-responsive').html();
                        
                        // Extract just the department info once
                        const departmentInfo = $(response).find('.department-info').html();
                        
                        // Combine them in the correct order
                        $('#teachersContent').html(`
                            <div class="department-info">
                                ${departmentInfo}
                            </div>
                            <div class="table-responsive">
                                ${tableContent}
                            </div>
                        `);
                    },
                    error: function() {
                        $('#teachersContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Error loading teachers data. Please try again.
                            </div>
                        `);
                    }
                });
            });

            // Function to go back to teachers list
            function backToTeachers() {
                $('#teacherDetailsSection').hide();
                $('#viewTeachersSection').show();
            }

            // Add event delegation for view details buttons
            $(document).on('click', '.btn-view', function(e) {
                e.preventDefault();
                const teacherId = $(this).attr('href').split('=')[1]; // Extract ID from the href
                
                // Hide teachers list
                $('#viewTeachersSection').hide();
                
                // Show teacher details section with loading state
                $('#teacherDetailsSection').show();
                $('#teacherDetailsContent').html(`
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading teacher details...</p>
                    </div>
                `);
                
                // Load teacher details
                $.ajax({
                    url: 'teacher_details.php',
                    type: 'GET',
                    data: { id: teacherId },
                    success: function(response) {
                        // Extract the teacher profile content
                        const profileContent = $(response).find('.teacher-profile-container').html();
                        
                        if (profileContent) {
                            $('#teacherDetailsContent').html(`
                                <div class="teacher-profile-container">
                                    ${profileContent}
                                </div>
                            `);
                        } else {
                            // Fallback if the specific container isn't found
                            // Create a temp div with the response to clean it
                            const tempDiv = $('<div>').html(response);
                            
                            // Remove header, sidebar, and other navigation elements
                            tempDiv.find('.header, .sidebar, .page-header, script').remove();
                            
                            // Get the cleaned content
                            $('#teacherDetailsContent').html(tempDiv.html());
                        }
                    },
                    error: function() {
                        $('#teacherDetailsContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Error loading teacher details. Please try again.
                            </div>
                        `);
                    }
                });
            });

            // Handle department preferences link click
            $('a[href="department_preferences.php"]').click(function(e) {
                e.preventDefault();
                
                // Hide content sections but NOT the dashboard structure
                $('.profile-section').hide();
                
                // Show preferences section and load content
                $('#departmentPreferencesSection').show();
                
                // Update active state in sidebar
                $('.sidebar a').removeClass('active');
                $(this).addClass('active');
                
                // Show loading indicator
                $('#preferencesContent').html(`
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading department preferences...</p>
                    </div>
                `);
                
                // Load department_preferences.php content
                $.ajax({
                    url: 'department_preferences.php',
                    type: 'GET',
                    success: function(response) {
                        // Extract the main content
                        const mainContent = $(response).find('.main-content').html();
                        
                        if (mainContent) {
                            $('#preferencesContent').html(mainContent);
                        } else {
                            // If we can't find .main-content, try to get the content some other way
                            const tempDiv = $('<div>').html(response);
                            
                            // Remove header, sidebar, and other navigation elements
                            tempDiv.find('.header, .sidebar, .dashboard-title, script').remove();
                            
                            // Get the cleaned content
                            $('#preferencesContent').html(tempDiv.html());
                        }
                        
                        // Remove any duplicated navigation elements
                        $('#preferencesContent .header, #preferencesContent .sidebar').remove();
                    },
                    error: function() {
                        $('#preferencesContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Error loading preferences data. Please try again.
                            </div>
                        `);
                    }
                });
            });

            // Make sure dashboard link shows main profile section
            $('a[href="hod_dashboard.php"]').click(function(e) {
                if (window.location.pathname.endsWith('hod_dashboard.php')) {
                    e.preventDefault();
                    $('.profile-section').hide();
                    $('#profileSection').show();
                    
                    // Update active state in sidebar
                    $('.sidebar a').removeClass('active');
                    $(this).addClass('active');
                }
            });

            // Handle manage workload link click
            $('a[href="manage_workload.php"]').click(function(e) {
                e.preventDefault();
                
                // Hide other sections
                $('.profile-section').hide();
                
                // Show workload section
                $('#manageWorkloadSection').show();
                
                // Show loading indicator
                $('#workloadContent').html(`
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading workload data...</p>
                    </div>
                `);
                
                // Update active state in sidebar
                $('.sidebar a').removeClass('active');
                $(this).addClass('active');
                
                // Load workload data directly
                $.ajax({
                    url: 'manage_workload.php?ajax=true',
                    type: 'GET',
                    success: function(response) {
                        // Instead of trying to parse the HTML, let's just display the content directly
                        $('#workloadContent').html(response);
                        
                        // Remove any duplicate headers, sidebars, etc.
                        $('#workloadContent .header, #workloadContent .sidebar, #workloadContent .dashboard-title').remove();
                        
                        // Make sure any "Add Workload" button works
                        $('#workloadContent button, #workloadContent .btn').click(function(e) {
                            const onclick = $(this).attr('onclick');
                            if (onclick && onclick.includes('addWorkload')) {
                                e.preventDefault();
                                eval(onclick); // Execute the original onclick function
                            }
                        });
                        
                        // Ensure any forms submit via AJAX
                        $('#workloadContent form').submit(function(e) {
                            e.preventDefault();
                            $.ajax({
                                url: $(this).attr('action'),
                                type: $(this).attr('method'),
                                data: $(this).serialize(),
                                success: function() {
                                    // Reload the workload content
                                    $('a[href="manage_workload.php"]').click();
                                },
                                error: function() {
                                    alert('Error processing your request. Please try again.');
                                }
                            });
                        });
                    },
                    error: function() {
                        $('#workloadContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Error loading workload data. Please try again.
                            </div>
                        `);
                    }
                });
            });

            // Handle subject workload link click
            $('a[href="subject_workload.php"]').click(function(e) {
                e.preventDefault();
                
                // Hide other sections
                $('.profile-section').hide();
                
                // Show subject workload section
                $('#subjectWorkloadSection').show();
                
                // Show loading indicator
                $('#subjectWorkloadContent').html(`
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading subject workload data...</p>
                    </div>
                `);
                
                // Update active state in sidebar
                $('.sidebar a').removeClass('active');
                $(this).addClass('active');
                
                // Load subject_workload.php content via AJAX
                $.ajax({
                    url: 'subject_workload.php?ajax=1',
                    type: 'GET',
                    success: function(response) {
                        // Simply display the content directly
                        $('#subjectWorkloadContent').html(response);
                        
                        // Remove any duplicate headers, sidebars, etc.
                        $('#subjectWorkloadContent .header, #subjectWorkloadContent .sidebar, #subjectWorkloadContent .dashboard-title').remove();
                        
                        // Handle form submissions via AJAX
                        $('#subjectWorkloadContent form').submit(function(e) {
                            e.preventDefault();
                            $.ajax({
                                url: $(this).attr('action'),
                                type: $(this).attr('method'),
                                data: $(this).serialize() + '&ajax=1',
                                success: function() {
                                    // Reload the subject workload content
                                    $('a[href="subject_workload.php"]').click();
                                },
                                error: function() {
                                    alert('Error processing your request. Please try again.');
                                }
                            });
                        });
                        
                        // Handle select changes for dependent dropdowns
                        $('#subjectWorkloadContent select').change(function() {
                            // Trigger any onchange events that might be in the original code
                            if ($(this).attr('onchange')) {
                                eval($(this).attr('onchange'));
                            }
                        });
                    },
                    error: function() {
                        $('#subjectWorkloadContent').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                Error loading subject workload data. Please try again.
                            </div>
                        `);
                    }
                });
            });

            $('#hodProfileForm input, #hodProfileForm select, #hodProfileForm textarea').on('input change', function() {
                validateField($(this));
            });
            
            function validateField(field) {
                const value = field.val().trim();
                let isValid = true;
                let errorMessage = '';
                
                if (field.attr('name') === 'full_name') {
                    const nameRegex = /^[A-Za-z\s]+$/; // Only letters and spaces
                    if (!nameRegex.test(value) || value.length < 2) {
                        isValid = false;
                        errorMessage = 'Full name must be at least 2 characters long and contain only letters and spaces.';
                    }
                } else if (field.attr('name') === 'mobile') {
                    const phoneRegex = /^[0-9]{10}$/; // Must be exactly 10 digits
                    if (!phoneRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'Mobile number must be exactly 10 digits and contain no letters.';
                    }
                } else if (['qualification', 'subject_expertise', 'research'].includes(field.attr('name'))) {
                    const textRegex = /^[A-Za-z\s]+$/; // Only letters and spaces
                    if (!textRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'This field can only contain letters and spaces.';
                    }
                }
                
                if (isValid) {
                    field.removeClass('is-invalid').addClass('is-valid');
                    field.next('.invalid-feedback').remove();
                } else {
                    field.removeClass('is-valid').addClass('is-invalid');
                    if (field.next('.invalid-feedback').length === 0) {
                        field.after(`<div class="invalid-feedback">${errorMessage}</div>`);
                    }
                }
            }
        });
    </script>
</body>
</html>