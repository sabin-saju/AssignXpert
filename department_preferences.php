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

// Get all teachers who have added preferences to this department, regardless of their own department
$query = "SELECT DISTINCT t.id, t.name, t.designation, t.qualification, t.subject, 
                 u.email, d.name as teacher_department, 
                 (t.department_id = ?) as is_same_department
          FROM teacher_preferences tp
          JOIN teachers t ON tp.teacher_id = t.id 
          JOIN users u ON t.user_id = u.user_id
          JOIN departments d ON t.department_id = d.id
          WHERE tp.department_id = ? 
          ORDER BY is_same_department DESC, t.name";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $department_id, $department_id);
$stmt->execute();
$teachers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Preferences | AssignXpert</title>
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

        /* Dashboard title */
        .dashboard-title {
            background-color: #34495e;
            color: white;
            padding: 15px 25px;
            font-size: 18px;
            font-weight: 600;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Sidebar styling */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            height: 100vh;
            position: fixed;
            top: 122px;
            left: 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 10;
            padding-top: 20px;
        }

        .sidebar a {
            display: block;
            color: #ecf0f1;
            padding: 15px 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #3498db;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content styling */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 122px);
        }

        /* Teacher list styling */
        .teacher-list-header {
            margin-bottom: 20px;
        }

        .teacher-list-header h3 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .teacher-list-header p {
            color: #7f8c8d;
            margin-bottom: 0;
        }

        .teacher-list-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }

        .department-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        /* Table styling */
        .table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-top: none;
            padding: 12px 15px;
        }

        .table td {
            vertical-align: middle;
            padding: 12px 15px;
            color: #2c3e50;
            border-color: #f1f1f1;
        }

        .teacher-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 3px;
        }

        .teacher-email {
            font-size: 13px;
            color: #7f8c8d;
        }

        .badge-designation {
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 30px;
        }

        .badge-junior {
            background-color: #e1f5fe;
            color: #0288d1;
        }

        .badge-senior {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .badge-associate {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .badge-hod {
            background-color: #e8eaf6;
            color: #3f51b5;
        }

        .expertise-chip {
            display: inline-block;
            padding: 4px 8px;
            background-color: #f1f2f6;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 4px;
            margin-bottom: 4px;
            color: #2c3e50;
        }

        .btn-view {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            font-size: 13px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background-color: #2980b9;
            color: white;
            text-decoration: none;
        }

        .department-badge {
            display: inline-block;
            padding: 4px 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 12px;
            color: #2c3e50;
            border: 1px solid #e9ecef;
        }

        .same-department {
            background-color: #e8f5e9;
            color: #388e3c;
            border-color: #c8e6c9;
        }

        .other-department {
            background-color: #fff8e1;
            color: #ffa000;
            border-color: #ffecb3;
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
    <!-- Header -->
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

    <!-- Sidebar with Navigation - STANDARDIZED -->
    <div class="sidebar">
        <a href="hod_dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
        <a href="view_teachers.php"><i class="fas fa-chalkboard-teacher"></i> View Teachers</a>
        <a href="department_preferences.php" class="active"><i class="fas fa-users"></i> All Preferences</a>
        <a href="manage_workload.php"><i class="fas fa-tasks"></i> Manage Workload</a>
        <a href="subject_workload.php"><i class="fas fa-book"></i> Subject Workload</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="teacher-list-header">
            <h3><i class="fas fa-users"></i> All Department Preferences</h3>
            <p>Teachers who have chosen preferences in <?php echo htmlspecialchars($departmentName); ?> department</p>
        </div>
        
        <div class="teacher-list-container">
            <div class="department-info">
                <p><strong>Department:</strong> <?php echo htmlspecialchars($departmentName); ?></p>
                <p><strong>Total Teachers with Preferences:</strong> <?php echo $teachers->num_rows; ?></p>
            </div>
            
            <?php if ($teachers->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Qualification</th>
                                <th>Subject Expertise</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                <tr>
                                    <td class="teacher-name"><?php echo htmlspecialchars($teacher['name']); ?></td>
                                    <td class="teacher-email"><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td>
                                        <span class="department-badge <?php echo $teacher['is_same_department'] ? 'same-department' : 'other-department'; ?>">
                                            <?php echo htmlspecialchars($teacher['teacher_department']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $designationClass = '';
                                        switch ($teacher['designation']) {
                                            case 'Junior Assistant Professor':
                                                $designationClass = 'badge-junior';
                                                break;
                                            case 'Senior Assistant Professor':
                                                $designationClass = 'badge-senior';
                                                break;
                                            case 'Associate Professor':
                                                $designationClass = 'badge-associate';
                                                break;
                                            case 'HOD':
                                                $designationClass = 'badge-hod';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-designation <?php echo $designationClass; ?>">
                                            <?php echo htmlspecialchars($teacher['designation']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($teacher['qualification']) ? htmlspecialchars($teacher['qualification']) : '-'; ?></td>
                                    <td>
                                        <?php if (!empty($teacher['subject'])): ?>
                                            <span class="expertise-chip"><?php echo htmlspecialchars($teacher['subject']); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="teacher_preferences.php?id=<?php echo $teacher['id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View Preferences
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No teachers have added preferences for this department yet.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 