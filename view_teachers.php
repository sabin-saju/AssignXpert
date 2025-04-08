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

// FIX: Changed u.id to u.user_id in the JOIN clause
$query = "SELECT t.id, t.name, t.designation, t.qualification, t.subject, t.research, 
                 u.email, d.name as department_name 
          FROM teachers t 
          JOIN users u ON t.user_id = u.user_id 
          JOIN departments d ON t.department_id = d.id
          WHERE t.department_id = ? 
          ORDER BY t.designation, t.name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$teachers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Teachers | AssignXpert</title>
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
        }

        .user-info a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .user-info a i {
            margin-right: 5px;
        }

        /* Dashboard title */
        .dashboard-title {
            background-color: #34495e;
            color: white;
            padding: 10px 25px;
            font-size: 16px;
            letter-spacing: 1px;
        }

        /* Sidebar styling */
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #2c3e50;
            padding-top: 110px;
            transition: all 0.3s;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1;
        }

        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 15px;
            color: #ecf0f1;
            display: block;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }

        .sidebar a i {
            margin-right: 10px;
            font-size: 18px;
            width: 25px;
            text-align: center;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .sidebar a.active {
            background-color: #3498db;
            color: white;
            border-left: 5px solid #2980b9;
        }

        /* Main content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        /* Teacher list styling */
        .teacher-list-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #e1e1e1;
            padding-bottom: 15px;
        }

        .teacher-list-header h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
            display: flex;
            align-items: center;
        }

        .teacher-list-header h3 i {
            margin-right: 10px;
            color: #3498db;
        }

        .teacher-list-header p {
            color: #7f8c8d;
            margin: 0;
            font-size: 14px;
        }

        .teacher-list-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }

        .department-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 20px;
            background-color: rgba(52, 152, 219, 0.1);
            padding: 15px;
            border-radius: 5px;
        }

        .department-info p {
            margin: 0;
            color: #2c3e50;
            font-size: 14px;
        }

        .department-info strong {
            color: #34495e;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.02);
        }

        .teacher-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .teacher-email {
            font-size: 13px;
            color: #7f8c8d;
        }

        .badge-designation {
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 500;
            color: white;
        }

        .badge-junior {
            background-color: #3498db;
        }

        .badge-senior {
            background-color: #9b59b6;
        }

        .badge-associate {
            background-color: #e67e22;
        }

        .badge-hod {
            background-color: #e74c3c;
        }

        .expertise-chip {
            display: inline-block;
            background-color: #f1f1f1;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 12px;
            color: #2c3e50;
        }

        .btn-view {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-view:hover {
            background-color: #2980b9;
            color: white;
            text-decoration: none;
        }

        .btn-view i {
            margin-right: 5px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding-top: 100px;
            }
            
            .sidebar a {
                padding: 15px;
                text-align: center;
            }
            
            .sidebar a i {
                font-size: 20px;
                margin: 0;
            }
            
            .sidebar a span {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
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
        <a href="view_teachers.php" class="active"><i class="fas fa-chalkboard-teacher"></i> View Teachers</a>
        <a href="department_preferences.php"><i class="fas fa-users"></i> All Preferences</a>
        <a href="manage_workload.php"><i class="fas fa-tasks"></i> Manage Workload</a>
        <a href="subject_workload.php"><i class="fas fa-book"></i> Subject Workload</a>
        
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="teacher-list-header">
            <h3><i class="fas fa-chalkboard-teacher"></i> Department Teachers</h3>
            <p>View and manage teachers in <?php echo htmlspecialchars($departmentName); ?> department</p>
        </div>
        
        <div class="teacher-list-container">
            <div class="department-info">
                <p><strong>Department:</strong> <?php echo htmlspecialchars($departmentName); ?></p>
                <p><strong>Total Teachers:</strong> <?php echo $teachers->num_rows; ?></p>
            </div>
            
            <?php if ($teachers->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
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
                                        <a href="teacher_details.php?id=<?php echo $teacher['id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No teachers found in this department.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 