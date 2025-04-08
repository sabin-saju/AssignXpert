
<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header('Location: login.php');
    exit;
}

// Check if teacher ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: view_teachers.php');
    exit;
}

$teacher_id = $_GET['id'];
$conn = connectDB();
$user_id = $_SESSION['user_id'];
$userEmail = $_SESSION['email'];

// Get the HOD's department_id
$query = "SELECT department_id FROM hod WHERE user_id = ?";
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

// Get teacher details
$query = "SELECT t.*, d.name as department_name, u.email 
          FROM teachers t 
          JOIN departments d ON t.department_id = d.id
          JOIN users u ON t.user_id = u.user_id
          WHERE t.id = ? AND t.department_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $teacher_id, $department_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: view_teachers.php');
    exit;
}

$teacher = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Details | AssignXpert</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            margin: 0;
            padding: 0;
        }
        
        /* Teacher profile styles */
        .teacher-profile-container {
            max-width: 900px;
            margin: 20px auto;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        .profile-avatar {
            background: rgba(255, 255, 255, 0.2);
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 30px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-avatar i {
            font-size: 48px;
            color: white;
        }
        
        .profile-details h2 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .profile-meta {
            display: flex;
            align-items: center;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .profile-meta-item {
            display: flex;
            align-items: center;
            margin-right: 20px;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .profile-meta-item i {
            margin-right: 5px;
            opacity: 0.8;
        }
        
        .designation-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.2);
            margin-top: 5px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .profile-body {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .info-item {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .info-label {
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 600;
            font-size: 16px;
        }
        
        .empty-value {
            color: #bdc3c7;
            font-style: italic;
            font-weight: normal;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding-top: 50px;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .back-link {
                top: 10px;
                left: 10px;
            }
            
            .profile-meta {
                justify-content: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Header for the page */
        .page-header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 22px;
        }
        
        .page-actions a {
            color: white;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .page-actions a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Add this to the <style> section in teacher_details.php */
        .preferences-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .preference-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            position: relative;
            border-left: 3px solid #3498db;
        }

        .preference-item.preference-disabled {
            opacity: 0.7;
            border-left-color: #e74c3c;
        }

        .preference-course {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .preference-subject {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .preference-status {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #7f8c8d;
        }

        .preference-active .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #27ae60;
            border-radius: 50%;
            margin-right: 5px;
        }

        .preference-disabled .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #e74c3c;
            border-radius: 50%;
            margin-right: 5px;
        }

        .empty-preferences {
            color: #7f8c8d;
            text-align: center;
            padding: 20px;
            font-style: italic;
        }

        .loading-indicator {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <!-- Simple Header -->
    <div class="page-header">
        <h1>Teacher Details</h1>
        <div class="page-actions">
            <a href="view_teachers.php"><i class="fas fa-arrow-left"></i> Back to Teachers</a>
        </div>
    </div>

    <!-- Teacher Profile -->
    <div class="teacher-profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-details">
                <h2><?php echo htmlspecialchars($teacher['name']); ?></h2>
                <div class="profile-meta">
                    <div class="profile-meta-item">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($teacher['email']); ?>
                    </div>
                    <?php if (!empty($teacher['mobile'])): ?>
                    <div class="profile-meta-item">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($teacher['mobile']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="profile-meta-item">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($teacher['department_name']); ?>
                    </div>
                </div>
                <div class="designation-badge">
                    <?php echo htmlspecialchars($teacher['designation']); ?>
                </div>
            </div>
        </div>
        
        <div class="profile-body">
            <div class="info-section">
                <h3 class="info-section-title">Personal Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Mobile Number</div>
                        <div class="info-value">
                            <?php echo !empty($teacher['mobile']) ? htmlspecialchars($teacher['mobile']) : '<span class="empty-value">Not provided</span>'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value">
                            <?php echo !empty($teacher['gender']) ? htmlspecialchars(ucfirst($teacher['gender'])) : '<span class="empty-value">Not provided</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-section">
                <h3 class="info-section-title">Professional Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['department_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Designation</div>
                        <div class="info-value"><?php echo htmlspecialchars($teacher['designation']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Qualification</div>
                        <div class="info-value">
                            <?php echo !empty($teacher['qualification']) ? htmlspecialchars($teacher['qualification']) : '<span class="empty-value">Not provided</span>'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Joined On</div>
                        <div class="info-value">
                            <?php 
                            $date = new DateTime($teacher['created_at']);
                            echo $date->format('F j, Y'); 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-section">
                <h3 class="info-section-title">Academic Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Subject Expertise</div>
                        <div class="info-value">
                            <?php echo !empty($teacher['subject']) ? htmlspecialchars($teacher['subject']) : '<span class="empty-value">Not provided</span>'; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Research Interests</div>
                        <div class="info-value">
                            <?php echo !empty($teacher['research']) ? htmlspecialchars($teacher['research']) : '<span class="empty-value">Not provided</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar with Navigation - STANDARDIZED -->
    <div class="sidebar">
        <a href="hod_dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
        <a href="view_teachers.php"><i class="fas fa-chalkboard-teacher"></i> View Teachers</a>
        <a href="department_preferences.php"><i class="fas fa-users"></i> All Preferences</a>
        <a href="manage_workload.php"><i class="fas fa-tasks"></i> Manage Workload</a>
        <a href="subject_workload.php"><i class="fas fa-book"></i> Subject Workload</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 