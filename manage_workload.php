<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Check if user is logged in and is an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: login.php");
    exit;
}

try {
    // Establish database connection
    $conn = connectDB();

    // Get department ID of logged-in HOD
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $department_id = $row['department_id'];

    // Get all designations from teachers in this department
    $designation_query = "SELECT DISTINCT designation 
                         FROM teachers 
                         WHERE department_id = ?
                         ORDER BY designation";
    $stmt = $conn->prepare($designation_query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $designations = $stmt->get_result();

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
    <title>Manage Workload - AssignXpert</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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

        .sidebar .active {
            background-color: #3498db;
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
        
        .logout-btn {
            float: right;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1 style="float: left; margin: 0; padding: 0; font-size: 24px;">ASSIGNXPERT</h1>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <div style="clear: both;"></div>
    </div>

    <!-- Sidebar with Navigation - STANDARDIZED -->
    <div class="sidebar">
        <a href="hod_dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
        <a href="view_teachers.php"><i class="fas fa-chalkboard-teacher"></i> View Teachers</a>
        <a href="department_preferences.php"><i class="fas fa-users"></i> All Preferences</a>
        <a href="manage_workload.php" class="active"><i class="fas fa-tasks"></i> Manage Workload</a>
        <a href="subject_workload.php"><i class="fas fa-book"></i> Subject Workload</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <h2>Manage Workload</h2>
            
            <div class="card">
                <div class="card-header">
                    <h5>Add Workload</h5>
                </div>
                <div class="card-body">
                    <form id="workload-form" method="POST" action="save_workload.php">
                        <div class="form-group">
                            <label>Select Designation</label>
                            <select name="designation" class="form-control" required>
                                <option value="">Select Designation</option>
                                <?php while($designations && $row = $designations->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($row['designation']); ?>">
                                        <?php echo htmlspecialchars($row['designation']); ?>
                                    </option>
                                <?php endwhile; ?>
                                <option value="HOD">HOD</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Weekly Hours</label>
                            <input type="number" name="weekly_hours" class="form-control" required>
                            <small class="text-muted">
                                Maximum hours: Junior Assistant Professor - 22, Senior Assistant Professor - 20, 
                                Associate Professor - 19, HOD - 17
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">Add Workload</button>
                    </form>
                </div>
            </div>

            <!-- Display Current Workload -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Current Workload Status</h5>
                </div>
                <div class="card-body">
                    <div id="workload-table">
                        <!-- Table will be loaded here via AJAX -->
                        <p>Loading workload data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            loadWorkloadTable();

            // Handle form submission
            $('#workload-form').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'save_workload.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            alert('Workload added successfully!');
                            $('#workload-form')[0].reset();
                            loadWorkloadTable();
                        } else {
                            alert(response.message || 'Error adding workload');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error:", status, error);
                        alert('Error occurred while saving workload');
                    }
                });
            });
        });

        function loadWorkloadTable() {
            $.get('get_workload_table.php')
                .done(function(data) {
                    $('#workload-table').html(data);
                })
                .fail(function(xhr, status, error) {
                    console.error("Error loading workload table:", status, error);
                    $('#workload-table').html('<p>Error loading workload data. Please refresh the page.</p>');
                });
        }
    </script>
</body>
</html>
