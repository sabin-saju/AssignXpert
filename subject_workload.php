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

    // Get all courses for this department
    $course_query = "SELECT id, name FROM courses WHERE department_id = ? AND is_disabled = 0 ORDER BY name";
    $stmt = $conn->prepare($course_query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $courses = $stmt->get_result();

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
    <title>Subject Workload - AssignXpert</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

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

        .sidebar a.active {
            background-color: #3498db;
        }

        .sidebar i {
            margin-right: 10px;
        }

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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            padding: 0;
            font-size: 24px;
        }

        .content-wrapper {
            margin-top: 60px;
            padding: 20px;
        }

        .logout-btn {
            color: white;
            text-decoration: none;
        }

        .card {
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>ASSIGNXPERT</h1>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Sidebar with Navigation - STANDARDIZED -->
    <div class="sidebar">
        <a href="hod_dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
        <a href="view_teachers.php"><i class="fas fa-chalkboard-teacher"></i> View Teachers</a>
        <a href="department_preferences.php"><i class="fas fa-users"></i> All Preferences</a>
        <a href="manage_workload.php"><i class="fas fa-tasks"></i> Manage Workload</a>
        <a href="subject_workload.php" class="active"><i class="fas fa-book"></i> Subject Workload</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <h2>Subject Workload Management</h2>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Weekly Hours Guidelines</h5>
                </div>
                <div class="card-body">
                    <p>All subjects are assigned a standard workload of 5 hours per week, regardless of subject type or credits.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Assign Subject Workload</h5>
                </div>
                <div class="card-body">
                    <form id="subject-workload-form">
                        <div class="form-group">
                            <label>Course</label>
                            <select id="course-select" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php while($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Subject</label>
                            <select id="subject-select" class="form-control" required disabled>
                                <option value="">Select Course First</option>
                            </select>
                            <small id="subject-info" class="form-text text-muted"></small>
                        </div>

                        <div class="form-group">
                            <label>Weekly Hours</label>
                            <input type="number" id="weekly-hours" name="weekly-hours" class="form-control" placeholder="Enter 5 hours">
                            <small id="hours-info" class="text-muted">Enter weekly hours for this subject (must be 5)</small>
                        </div>

                        <button type="submit" id="submit-btn" class="btn btn-primary" disabled>Assign Workload</button>
                    </form>
                </div>
            </div>

            <!-- Display Current Subject Workload -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Current Subject Workload</h5>
                </div>
                <div class="card-body">
                    <div id="subject-workload-table">
                        <!-- Table will be loaded here via AJAX -->
                        <p>Loading subject workload data...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            loadSubjectWorkloadTable();

            // Course selection change event
            $('#course-select').on('change', function() {
                const courseId = $(this).val();
                
                if (!courseId) {
                    $('#subject-select').html('<option value="">Select Course First</option>');
                    $('#subject-select').prop('disabled', true);
                    $('#weekly-hours').prop('disabled', true);
                    $('#submit-btn').prop('disabled', true);
                    return;
                }
                
                // Fetch subjects for selected course
                $.ajax({
                    url: 'get_subjects_by_course.php',
                    method: 'GET',
                    data: { course_id: courseId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.subjects.length > 0) {
                            let options = '<option value="">Select Subject</option>';
                            
                            response.subjects.forEach(function(subject) {
                                options += `<option value="${subject.id}" 
                                            data-type="${subject.subject_type}" 
                                            data-credits="${subject.credit_points}">
                                            ${subject.name} (${subject.subject_type}, ${subject.credit_points} credits)
                                            </option>`;
                            });
                            
                            $('#subject-select').html(options);
                            $('#subject-select').prop('disabled', false);
                        } else {
                            $('#subject-select').html('<option value="">No subjects found</option>');
                        }
                    },
                    error: function() {
                        $('#subject-select').html('<option value="">Error loading subjects</option>');
                    }
                });
            });

            // Subject selection change event
            $('#subject-select').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const subjectType = selectedOption.data('type');
                const creditPoints = selectedOption.data('credits');
                
                if (!subjectType || !creditPoints) {
                    $('#weekly-hours').val('');
                    $('#subject-info').text('');
                    $('#submit-btn').prop('disabled', true);
                    return;
                }
                
                // Display subject info but don't set any automatic hours
                $('#subject-info').text(`${subjectType.toUpperCase()} subject with ${creditPoints} credits`);
                $('#hours-info').text(`Required workload: 5 hours/week for all subjects`);
                $('#weekly-hours').val(''); // Clear any previous value
                $('#weekly-hours').prop('disabled', false); // Enable manual entry
                $('#submit-btn').prop('disabled', false);
            });

            // Form submission
            $('#subject-workload-form').on('submit', function(e) {
                e.preventDefault();
                
                const courseId = $('#course-select').val();
                const subjectId = $('#subject-select').val();
                const weeklyHours = $('#weekly-hours').val();
                
                if (!courseId || !subjectId) {
                    alert('Please select a course and subject');
                    return;
                }
                
                if (!weeklyHours) {
                    alert('Please enter weekly hours');
                    return;
                }
                
                // Validate that hours must be 5
                if (parseInt(weeklyHours) !== 5) {
                    alert('Weekly hours must be 5 for all subjects');
                    return;
                }
                
                $.ajax({
                    url: 'save_subject_workload.php',
                    method: 'POST',
                    data: {
                        course_id: courseId,
                        subject_id: subjectId,
                        weekly_hours: weeklyHours
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Subject workload assigned successfully!');
                            $('#subject-workload-form')[0].reset();
                            $('#subject-select').html('<option value="">Select Course First</option>');
                            $('#subject-select').prop('disabled', true);
                            $('#weekly-hours').val('');
                            $('#subject-info').text('');
                            $('#hours-info').text('Enter weekly hours for this subject');
                            $('#submit-btn').prop('disabled', true);
                            loadSubjectWorkloadTable();
                        } else {
                            alert(response.message || 'Error assigning subject workload');
                        }
                    },
                    error: function() {
                        alert('Error occurred while saving subject workload');
                    }
                });
            });
        });

        function loadSubjectWorkloadTable() {
            $.get('get_subject_workload_table.php')
                .done(function(data) {
                    $('#subject-workload-table').html(data);
                })
                .fail(function() {
                    $('#subject-workload-table').html('<p>Error loading subject workload data</p>');
                });
        }

        function deleteSubjectWorkload(id) {
            if (confirm('Are you sure you want to delete this subject workload?')) {
                $.ajax({
                    url: 'delete_subject_workload.php',
                    method: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Subject workload deleted successfully!');
                            loadSubjectWorkloadTable();
                        } else {
                            alert(response.message || 'Error deleting subject workload');
                        }
                    },
                    error: function() {
                        alert('Error occurred while deleting subject workload');
                    }
                });
            }
        }

        function toggleWorkloadStatus(id, status) {
            const action = status === 1 ? 'enable' : 'disable';
            if (confirm(`Are you sure you want to ${action} this subject workload?`)) {
                $.ajax({
                    url: 'toggle_subject_workload.php',
                    method: 'POST',
                    data: { 
                        id: id,
                        status: status 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(`Subject workload ${action}d successfully!`);
                            loadSubjectWorkloadTable();
                        } else {
                            alert(response.message || `Error ${action}ing subject workload`);
                        }
                    },
                    error: function() {
                        alert(`Error occurred while ${action}ing subject workload`);
                    }
                });
            }
        }

        function editWorkload(id, currentHours) {
            const newHours = prompt('Enter new weekly hours (must be 5):', currentHours);
            
            if (newHours === null) {
                return; // User cancelled
            }
            
            // Convert to number and validate
            const hours = parseInt(newHours);
            if (isNaN(hours) || hours !== 5) {
                alert('Weekly hours must be 5 for all subjects');
                return;
            }
            
            $.ajax({
                url: 'update_subject_workload.php',
                method: 'POST',
                data: { 
                    id: id,
                    weekly_hours: hours 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Subject workload updated successfully!');
                        // Instead of reloading the whole table, just update the specific cell
                        $(`#hours-${id}`).text(hours);
                    } else {
                        alert(response.message || 'Error updating subject workload');
                    }
                },
                error: function() {
                    alert('Error occurred while updating subject workload');
                }
            });
        }
    </script>
</body>
</html>
