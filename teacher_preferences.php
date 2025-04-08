<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hod', 'teacher'])) {
    header("Location: login.php");
    exit;
}

// Get teacher ID from URL parameter
if (!isset($_GET['id'])) {
    echo "Error: No teacher ID specified";
    exit;
}

$teacherId = intval($_GET['id']);

try {
    $conn = connectDB();
    
    // First get the teacher details
    $teacherQuery = "SELECT u.email, t.name, t.department_id, d.name as department_name 
                    FROM teachers t 
                    JOIN users u ON t.user_id = u.user_id 
                    JOIN departments d ON t.department_id = d.id 
                    WHERE t.user_id = ?";
    
    $teacherStmt = $conn->prepare($teacherQuery);
    $teacherStmt->bind_param("i", $teacherId);
    $teacherStmt->execute();
    $teacherResult = $teacherStmt->get_result();
    
    if ($teacherResult->num_rows === 0) {
        echo "Error: Teacher not found";
        exit;
    }
    
    $teacherData = $teacherResult->fetch_assoc();
    
    // Get preferences for this teacher
    // Fix the SQL query - replacing sub.code with s.code
    $preferencesQuery = "
        SELECT tp.*, c.name as course_name, sem.name as semester_name, 
               s.name as subject_name, s.code as subject_code, s.subject_type
        FROM teacher_preferences tp
        JOIN subjects s ON tp.subject_id = s.id
        JOIN semesters sem ON s.semester_id = sem.id
        JOIN courses c ON sem.course_id = c.id
        WHERE tp.teacher_id = ?
        ORDER BY tp.preference_order
    ";
    
    $prefStmt = $conn->prepare($preferencesQuery);
    $prefStmt->bind_param("i", $teacherId);
    $prefStmt->execute();
    $prefResult = $prefStmt->get_result();
    
    // Check if Ajax request
    if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
        // If it's an AJAX request, only return the content part
        include 'teacher_preferences_content.php';
        exit;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Preferences | AssignXpert</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1, h2 {
            color: #2c3e50;
        }
        
        .teacher-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #2c3e50;
            color: white;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .back-button:hover {
            background-color: #2980b9;
        }
        
        .no-preferences {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <h1>Teacher Preferences</h1>
        
        <div class="teacher-info">
            <h2><?php echo htmlspecialchars($teacherData['name'] ?? $teacherData['email']); ?></h2>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($teacherData['email']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($teacherData['department_name']); ?></p>
        </div>
        
        <?php if ($prefResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Preference Order</th>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Subject</th>
                        <th>Subject Code</th>
                        <th>Subject Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pref = $prefResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pref['preference_order']); ?></td>
                            <td><?php echo htmlspecialchars($pref['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($pref['semester_name']); ?></td>
                            <td><?php echo htmlspecialchars($pref['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($pref['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($pref['subject_type'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-preferences">
                <i class="fas fa-exclamation-circle"></i>
                This teacher has not submitted any preferences yet.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>