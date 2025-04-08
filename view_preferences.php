<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header('Location: login.php');
    exit;
}

// Get the HOD's department_id
$conn = connectDB();
$user_id = $_SESSION['user_id'];
$query = "SELECT h.department_id, h.full_name, d.name as department_name 
          FROM hod h
          JOIN departments d ON h.department_id = d.id
          WHERE h.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: hod_dashboard.php');
    exit;
}

$hodData = $result->fetch_assoc();
$department_id = $hodData['department_id'];
$hodName = $hodData['full_name'] ?? $_SESSION['email'];
$departmentName = $hodData['department_name'];
$userEmail = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Preferences | AssignXpert</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            margin: 0;
            padding: 0;
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

        /* Dashboard title */
        .dashboard-title {
            background-color: #34495e;
            color: white;
            padding: 15px 25px;
            font-size: 20px;
            font-weight: 600;
        }

        /* Sidebar styling */
        .sidebar {
            height: 100%;
            width: 240px;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #2c3e50;
            overflow-x: hidden;
            padding-top: 115px;
        }

        .sidebar a {
            padding: 12px 25px;
            text-decoration: none;
            font-size: 15px;
            color: #b2bec3;
            display: block;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover {
            background-color: #34495e;
            color: white;
            border-left: 3px solid #3498db;
        }

        .sidebar a.active {
            background-color: #34495e;
            color: white;
            border-left: 3px solid #3498db;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content */
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

        /* Preferences container */
        .preferences-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 20px;
        }

        .preferences-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .preferences-header h3 {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .preferences-header p {
            color: #7f8c8d;
            margin-bottom: 0;
        }

        /* Table styling */
        .table-preferences {
            width: 100%;
        }

        .table-preferences th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-top: none;
            padding: 12px 15px;
        }

        .table-preferences td {
            vertical-align: middle;
            padding: 12px 15px;
            color: #2c3e50;
            border-color: #f1f1f1;
        }

        .teacher-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .teacher-email {
            font-size: 13px;
            color: #7f8c8d;
        }

        .badge-preference {
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 30px;
        }

        .badge-active {
            background-color: #e6f7ee;
            color: #27ae60;
        }

        .badge-disabled {
            background-color: #f8eae7;
            color: #e74c3c;
        }

        .btn-view-teacher {
            background-color: #f8f9fa;
            color: #3498db;
            border: 1px solid #e0e0e0;
            padding: 5px 10px;
            font-size: 13px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .btn-view-teacher:hover {
            background-color: #3498db;
            color: white;
            text-decoration: none;
        }

        .btn-view-teacher i {
            margin-right: 5px;
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

    <!-- Sidebar with Navigation -->
    <div class="sidebar">
        <a href="hod_dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
        <a href="my_profile.php"><i class="fas fa-user"></i> My Profile</a>
        <a href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profile</a>
        <a href="view_teachers.php"><i class="fas fa-chalkboard-teacher"></i> View Teachers</a>
        <a href="view_preferences.php" class="active"><i class="fas fa-list-alt"></i> Teacher Preferences</a>
        <a href="manage_workload.php"><i class="fas fa-tasks"></i> Manage Workload</a>
        <a href="subject_workload.php"><i class="fas fa-book"></i> Subject Workload</a>
        <a href="view_reports.php"><i class="fas fa-chart-bar"></i> View Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="preferences-container">
            <div class="preferences-header">
                <h3><i class="fas fa-list-alt"></i> Teacher Preferences</h3>
                <p>View subject preferences submitted by teachers in the <?php echo htmlspecialchars($departmentName); ?> department</p>
            </div>
            
            <div id="preferences-table-container">
                <div class="text-center p-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading preferences...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load teacher preferences
            loadTeacherPreferences();
            
            function loadTeacherPreferences() {
                $.ajax({
                    url: 'get_hod_teacher_preferences.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(preferences) {
                        displayPreferences(preferences);
                    },
                    error: function() {
                        $('#preferences-table-container').html(
                            '<div class="alert alert-danger">' +
                            '<i class="fas fa-exclamation-circle"></i> ' +
                            'Unable to load preferences. Please try again later.' +
                            '</div>'
                        );
                    }
                });
            }
            
            function displayPreferences(preferences) {
                if (preferences.length === 0) {
                    $('#preferences-table-container').html(
                        '<div class="alert alert-info">' +
                        '<i class="fas fa-info-circle"></i> ' +
                        'No preferences have been submitted by teachers yet.' +
                        '</div>'
                    );
                    return;
                }
                
                let tableHtml = `
                    <div class="table-responsive">
                        <table class="table table-preferences">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Course</th>
                                    <th>Semester</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                preferences.forEach(function(pref) {
                    const statusBadge = pref.is_disabled == 0 
                        ? '<span class="badge badge-preference badge-active">Active</span>' 
                        : '<span class="badge badge-preference badge-disabled">Disabled</span>';
                    
                    tableHtml += `
                        <tr>
                            <td>
                                <div class="teacher-name">${pref.teacher_name}</div>
                                <div class="teacher-email">${pref.teacher_email}</div>
                            </td>
                            <td>${pref.course_name}</td>
                            <td>${pref.semester_name}</td>
                            <td>${pref.subject_name}</td>
                            <td>${statusBadge}</td>
                            <td>
                                <a href="teacher_details.php?id=${pref.teacher_id}" class="btn-view-teacher">
                                    <i class="fas fa-eye"></i> View Teacher
                                </a>
                            </td>
                        </tr>
                    `;
                });
                
                tableHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#preferences-table-container').html(tableHtml);
            }
        });
    </script>
</body>
</html> 