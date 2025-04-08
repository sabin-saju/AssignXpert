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

// Get Junior Assistant Professors who have added preferences to this department
$query = "SELECT DISTINCT t.id, t.name, t.designation, u.email 
          FROM teachers t
          JOIN users u ON t.user_id = u.user_id
          JOIN teacher_preferences tp ON t.id = tp.teacher_id
          WHERE tp.department_id = ? 
          AND (t.designation = 'Junior Assistant Professor' OR 
               t.designation = 'Senior Assistant Professor' OR 
               t.designation = 'Associate Professor')
          AND tp.is_disabled = 0
          ORDER BY t.designation, t.name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$teachers = $stmt->get_result();

// Get days of week
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Hours of the day (1-7)
$hours = range(1, 7);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Scheduling | AssignXpert</title>
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

        /* Dashboard layout */
        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }

        /* Sidebar styling */
        .sidebar {
            width: 250px;
            background-color: #34495e;
            color: white;
            padding-top: 20px;
            flex-shrink: 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 20px;
            transition: 0.3s;
        }

        .sidebar-menu a:hover, 
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content styling */
        .main-content {
            flex-grow: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        /* Page title */
        .page-title {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }

        /* Scheduling form */
        .schedule-form-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .form-row {
            margin-bottom: 15px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }

        .days-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .day-card {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.3s;
            background-color: #f9f9f9;
        }

        .day-card:hover {
            border-color: #3498db;
            background-color: #f0f7fc;
        }

        .day-card.selected {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }

        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 8px;
        }

        .badge-theory {
            background-color: #3498db;
            color: white;
        }

        .badge-lab {
            background-color: #e74c3c;
            color: white;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-action {
            padding: 8px 24px;
            border-radius: 4px;
            font-weight: 500;
            transition: 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-submit {
            background-color: #3498db;
            color: white;
        }

        .btn-submit:hover {
            background-color: #2980b9;
        }

        .btn-cancel {
            background-color: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background-color: #d1d1d1;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
            font-size: 13px;
            padding: 4px 12px;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        /* Current schedule styling */
        .current-schedule {
            margin-top: 20px;
        }

        .schedule-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            overflow: hidden;
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
        }

        .schedule-title {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            display: flex;
            align-items: center;
        }

        .schedule-details {
            padding: 15px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .schedule-item {
            display: flex;
            flex-direction: column;
        }

        .schedule-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }

        .schedule-value {
            font-weight: 500;
            color: #333;
        }

        .no-schedule {
            text-align: center;
            padding: 30px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            color: #7f8c8d;
        }

        .no-schedule i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #bdc3c7;
        }

        .tab-content {
            margin-top: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #7f8c8d;
            font-weight: 500;
            padding: 10px 15px;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link.active {
            color: #3498db;
            border-bottom: 2px solid #3498db;
            background-color: transparent;
        }

        .hour-select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>AssignXpert</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($userEmail); ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
    
    <div class="dashboard-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="hod_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="department_preferences.php"><i class="fas fa-clipboard-list"></i> Teacher Preferences</a></li>
                <li><a href="teacher_scheduling.php" class="active"><i class="fas fa-calendar-alt"></i> Schedule Classes</a></li>
                <li><a href="view_schedules.php"><i class="fas fa-calendar-check"></i> View Schedules</a></li>
                <li><a href="manage_teachers.php"><i class="fas fa-user-tie"></i> Manage Teachers</a></li>
                <li><a href="manage_subjects.php"><i class="fas fa-book"></i> Manage Subjects</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div id="alertContainer"></div>
            
            <h1 class="page-title">Schedule Classes - <?php echo htmlspecialchars($departmentName); ?></h1>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Add New Class Schedule</h5>
                </div>
                <div class="card-body">
                    <div id="alertContainer"></div>
                    
                    <form id="scheduleForm">
                        <div class="form-group">
                            <label for="teacherSelect">Select Teacher</label>
                            <select class="form-control" id="teacherSelect" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                    <?php 
                                    $designation = isset($teacher['designation']) ? $teacher['designation'] : 'Unknown';
                                    $displayTitle = '';
                                    
                                    // Format the designation display
                                    if ($designation == 'Junior Assistant Professor' || $designation == 'Senior Assistant Professor') {
                                        $displayTitle = 'Asst. Prof. ';
                                    } elseif ($designation == 'Associate Professor') {
                                        $displayTitle = 'Assoc. Prof. ';
                                    }
                                    ?>
                                    <option value="<?php echo $teacher['id']; ?>" data-designation="<?php echo htmlspecialchars($designation); ?>">
                                        <?php echo htmlspecialchars($displayTitle . $teacher['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <input type="hidden" id="teacherDesignation" name="teacherDesignation" value="">

                        <div class="form-group">
                            <label for="subjectType">Subject Type</label>
                            <select class="form-control" id="subjectType" name="subject_type" required>
                                <option value="theory">Theory</option>
                                <option value="lab">Lab</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subjectSelect">Select Subject</label>
                            <select class="form-control" id="subjectSelect" name="subject_id" required disabled>
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="dayOfWeek">Day of Week</label>
                            <select class="form-control" id="dayOfWeek" name="dayOfWeek" required onchange="checkDayScheduleLimit()">
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="hourSelect">Hour</label>
                            <select class="form-control" id="hourSelect" name="hour" required>
                                <option value="">Select Hour</option>
                                <?php foreach ($hours as $hour): ?>
                                    <option value="<?php echo $hour; ?>">Hour <?php echo $hour; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" id="isTheory" name="isTheory" value="1">
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="fas fa-save"></i> Save Schedule
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelBtn">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <ul class="nav nav-tabs" id="scheduleTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="current-tab" data-toggle="tab" href="#currentSchedule" role="tab">Current Schedules</a>
                </li>
            </ul>
            
            <div class="tab-content" id="scheduleTabContent">
                <div class="tab-pane fade show active" id="currentSchedule" role="tabpanel">
                    <!-- Current schedules will be loaded here via AJAX -->
                    <div class="loading-spinner text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading schedules...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load current schedule on page load
            loadCurrentSchedule();
            
            // Teacher selection change event
            $('#teacherSelect').change(function() {
                const teacherId = $(this).val();
                $('#subjectType').val('theory');
                $('#subjectSelect').empty().append('<option value="">Select Subject</option>');
                $('#subjectSelect').prop('disabled', true);
                $('#submitBtn').prop('disabled', true);
                $('#isTheory').val('1');
                
                if (teacherId) {
                    // Load teacher's subjects
                    loadTeacherSubjects(teacherId, 'theory');
                }
            });
            
            // Subject type change event
            $('#subjectType').change(function() {
                const teacherId = $('#teacherSelect').val();
                const subjectType = $(this).val();
                
                // Update the isTheory hidden field
                $('#isTheory').val(subjectType === 'theory' ? '1' : '0');
                
                if (teacherId && subjectType) {
                    loadTeacherSubjects(teacherId, subjectType);
                }
            });
            
            // Subject selection change event
            $('#subjectSelect').change(function() {
                validateForm();
            });
            
            // Day of week change event
            $('#dayOfWeek').change(function() {
                validateForm();
            });
            
            // Hour change event
            $('#hourSelect').change(function() {
                validateForm();
            });
            
            // Add day of week change handler 
            $('#dayOfWeek').change(function() {
                checkDayScheduleLimit();
            });
            
            // Add subject type change handler
            $('#subjectType').change(function() {
                // Existing code...
                checkDayScheduleLimit();
            });
            
            // Update form submission with improved error handling
            $('#scheduleForm').submit(function(e) {
                e.preventDefault();
                
                const teacherId = $('#teacherSelect').val();
                const subjectId = $('#subjectSelect').val();
                const dayOfWeek = $('#dayOfWeek').val();
                const hour = $('#hourSelect').val();
                const designation = $('#teacherDesignation').val();
                const subjectType = $('#subjectType').val();
                
                // Check if we have all required values
                if (!teacherId || !subjectId || !dayOfWeek || !hour) {
                    showAlert('Please fill all required fields', 'danger');
                    return;
                }
                
                // Show loading state
                $('#submitBtn').prop('disabled', true);
                showAlert('Saving schedule...', 'info');
                
                console.log('Submitting schedule data:', {
                    teacher_id: teacherId,
                    subject_id: subjectId,
                    day_of_week: dayOfWeek,
                    hour: hour,
                    subjectType: subjectType,
                    designation: designation
                });
                
                // First check if the schedule meets all constraints
                $.ajax({
                    url: 'check_teacher_day_schedule.php',
                    type: 'GET',
                    data: {
                        teacher_id: teacherId,
                        day_of_week: dayOfWeek,
                        subject_id: subjectId,
                        subject_type: subjectType,
                        hour: hour
                    },
                    dataType: 'json',
                    success: function(validation) {
                        if (!validation.success) {
                            $('#submitBtn').prop('disabled', false);
                            showAlert((validation.messages && validation.messages.length > 0) ? 
                                validation.messages.join('<br>') : 
                                'This schedule would violate workload constraints', 'warning');
                            return;
                        }
                        
                        // If validation passes, proceed with saving the schedule
                        $.ajax({
                            url: 'save_schedule.php',
                            type: 'POST',
                            data: {
                                teacher_id: teacherId,
                                subject_id: subjectId,
                                day_of_week: dayOfWeek,
                                hour: hour,
                                isTheory: (subjectType === 'theory' ? 1 : 0)
                            },
                            dataType: 'json',
                            success: function(response) {
                                console.log('Schedule save response:', response);
                                
                                $('#submitBtn').prop('disabled', false);
                                
                                if (response.success) {
                                    showAlert('Schedule saved successfully!', 'success');
                                    // Reset form
                                    $('#scheduleForm')[0].reset();
                                    $('#subjectSelect').empty().append('<option value="">Select Subject</option>');
                                    $('#subjectSelect').prop('disabled', true);
                                    
                                    // Reload schedule table if it exists
                                    if (typeof loadScheduleTable === 'function') {
                                        loadScheduleTable();
                                    }
                                } else {
                                    showAlert(response.message || 'Error saving schedule', 'danger');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error saving schedule:', error);
                                console.error('Status:', status);
                                console.error('Response text:', xhr.responseText);
                                
                                $('#submitBtn').prop('disabled', false);
                                showAlert('An error occurred while saving the schedule', 'danger');
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error validating schedule:', error);
                        $('#submitBtn').prop('disabled', false);
                        showAlert('Error validating schedule: ' + error, 'danger');
                    }
                });
            });
            
            // Cancel button click event
            $('#cancelBtn').click(function() {
                $('#scheduleForm')[0].reset();
                $('#subjectSelect').empty().append('<option value="">Select Subject</option>');
                $('#subjectSelect').prop('disabled', true);
                $('#submitBtn').prop('disabled', true);
            });
        });
        
        // Function to validate form
        function validateForm() {
            const teacherId = $('#teacherSelect').val();
            const subjectId = $('#subjectSelect').val();
            const dayOfWeek = $('#dayOfWeek').val();
            const hour = $('#hourSelect').val();
            
            if (teacherId && subjectId && dayOfWeek && hour) {
                $('#submitBtn').prop('disabled', false);
            } else {
                $('#submitBtn').prop('disabled', true);
            }
        }
        
        // Function to load teacher subjects
        function loadTeacherSubjects(teacherId, subjectType) {
            $('#subjectSelect').empty().append('<option value="">Select Subject</option>');
            $('#subjectSelect').prop('disabled', true);
            
            console.log('Loading subjects for teacher:', teacherId, 'type:', subjectType);
            
            $.ajax({
                url: 'get_teacher_subjects.php',
                type: 'GET',
                data: {
                    teacher_id: teacherId,
                    subject_type: subjectType
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Subject response:', response);
                    
                    // Clear the dropdown
                    $('#subjectSelect').empty().append('<option value="">Select Subject</option>');
                    
                    // Check if the response is an array and has items
                    if (Array.isArray(response) && response.length > 0) {
                        // Add each subject to the dropdown
                        response.forEach(function(subject) {
                            $('#subjectSelect').append(`
                                <option value="${subject.subject_id}">
                                    ${subject.subject_name} (${subject.course_name} - ${subject.semester_name})
                                </option>
                            `);
                        });
                        $('#subjectSelect').prop('disabled', false);
                    } else {
                        // Show message if no subjects found
                        showAlert(`No ${subjectType} subjects found for this teacher.`, 'info');
                    }
                    
                    validateForm();
                },
                error: function(xhr, status, error) {
                    console.error('Error loading subjects:', xhr.responseText);
                    showAlert(`Failed to load teacher subjects: ${error}`, 'danger');
                    $('#subjectSelect').prop('disabled', true);
                }
            });
        }
        
        // Function to delete a schedule
        function deleteSchedule(scheduleId) {
            if (confirm('Are you sure you want to delete this schedule?')) {
                $.ajax({
                    url: 'delete_schedule.php',
                    type: 'POST',
                    data: { schedule_id: scheduleId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert('Schedule deleted successfully!', 'success');
                            loadCurrentSchedule(); // Reload the schedule table
                        } else {
                            showAlert(response.message || 'Error deleting schedule', 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting schedule:', error);
                        showAlert('Failed to delete schedule. Please try again.', 'danger');
                    }
                });
            }
        }
        
        // Function to show alerts
        function showAlert(message, type) {
            const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>`;
            
            $('#alertContainer').html(alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('#alertContainer .alert').alert('close');
            }, 5000);
        }
        
        // Function to load current schedule - ONLY replace this function
        function loadCurrentSchedule() {
            $.ajax({
                url: 'get_current_schedule.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Schedule data received:', response);
                    
                    let html = '';
                    
                    if (response.success && response.schedules && response.schedules.length > 0) {
                        // First, identify all unique courses
                        const courses = [...new Set(response.schedules.map(s => s.course_name))];
                        console.log('Found courses:', courses);
                        
                        // Create a container for all courses
                        html += '<div class="course-schedules">';
                        
                        // For each course, create a section
                        courses.forEach(function(courseName) {
                            // Filter schedules for this course
                            const courseSchedules = response.schedules.filter(s => s.course_name === courseName);
                            
                            html += `
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">${courseName}</h5>
                                </div>
                                <div class="card-body p-0">`;
                            
                            // Get all unique days for this course
                            const days = [...new Set(courseSchedules.map(s => s.day_of_week))];
                            
                            // Sort days in correct order
                            const dayOrder = {
                                'Monday': 1, 
                                'Tuesday': 2, 
                                'Wednesday': 3, 
                                'Thursday': 4, 
                                'Friday': 5
                            };
                            days.sort((a, b) => dayOrder[a] - dayOrder[b]);
                            
                            // For each day, create a table
                            days.forEach(function(day) {
                                // Filter schedules for this day
                                const daySchedules = courseSchedules.filter(s => s.day_of_week === day);
                                
                                // Sort by hour
                                daySchedules.sort((a, b) => parseInt(a.hour) - parseInt(b.hour));
                                
                                html += `
                                <div class="day-schedule mb-3">
                                    <div class="day-header bg-light p-2 font-weight-bold">
                                        ${day}
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th width="10%">Hour</th>
                                                    <th width="25%">Subject</th>
                                                    <th width="20%">Teacher</th>
                                                    <th width="10%">Type</th>
                                                    <th width="15%">Semester</th>
                                                    <th width="20%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;
                                
                                daySchedules.forEach(function(schedule) {
                                    const typeLabel = schedule.is_theory == 1 ? 'Theory' : 'Lab';
                                    const badgeClass = schedule.is_theory == 1 ? 'badge-primary' : 'badge-warning';
                                    
                                    html += `
                                    <tr ${schedule.is_enabled == 0 ? 'class="table-secondary text-muted"' : ''}>
                                        <td>Hour ${schedule.hour}</td>
                                        <td>${schedule.subject_name}</td>
                                        <td>${schedule.teacher_name}</td>
                                        <td><span class="badge ${badgeClass}">${typeLabel}</span></td>
                                        <td>${schedule.semester_name}</td>
                                        <td>
                                            ${schedule.is_enabled == 1 ? 
                                                `<button class="btn btn-danger btn-sm" onclick="deleteSchedule(${schedule.id})">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>` : 
                                                `<button class="btn btn-sm btn-success" onclick="toggleScheduleStatus(${schedule.id}, 0)">
                                                    <i class="fas fa-check"></i> Enable
                                                </button>`
                                            }
                                        </td>
                                    </tr>`;
                                });
                                
                                html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>`;
                            });
                            
                            html += `
                                </div>
                            </div>`;
                        });
                        
                        html += '</div>';
                    } else {
                        html = `<div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    No schedules have been created yet. Use the form to add schedules.
                                </div>`;
                    }
                    
            $('#currentSchedule').html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error loading schedules:', error);
            console.error('Response:', xhr.responseText);
            
            $('#currentSchedule').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Failed to load schedules.
                </div>`);
        }
    });
}

// Function to check day schedule limit
function checkDayScheduleLimit() {
    const teacherId = $('#teacherSelect').val();
    const dayOfWeek = $('#dayOfWeek').val();
    const subjectType = $('#subjectType').val();
    const designation = $('#teacherDesignation').val();
    const subjectId = $('#subjectSelect').val();
    const hour = $('#hourSelect').val();
    
    if (!teacherId || !dayOfWeek) return;
    
    // Show loading indicator
    $('#submitBtn').prop('disabled', true);
    
    $.ajax({
        url: 'check_teacher_day_schedule.php',
        type: 'GET',
        data: {
            teacher_id: teacherId,
            day_of_week: dayOfWeek,
            subject_id: subjectId,
            subject_type: subjectType,
            hour: hour
        },
        dataType: 'json',
        success: function(response) {
            console.log('Schedule limit check response:', response);
            
            if (response) {
                if (!response.success && response.messages && response.messages.length > 0) {
                    // Display all validation messages
                    showAlert(response.messages.join('<br>'), 'warning');
                    $('#submitBtn').prop('disabled', true);
                } else if (response.success) {
                    // All constraints are satisfied
                    $('#submitBtn').prop('disabled', false);
                    
                    // Show informational summary
                    const theoryCount = response.theory_count || 0;
                    const labCount = response.lab_count || 0;
                    const totalHours = response.total_daily_hours || 0;
                    const weeklyHours = response.total_weekly_hours || 0;
                    
                    let infoMsg = `<strong>Current Schedule:</strong><br>`;
                    infoMsg += `- Theory hours today: ${theoryCount}<br>`;
                    infoMsg += `- Lab hours today: ${labCount}<br>`;
                    infoMsg += `- Total hours today: ${totalHours}<br>`;
                    infoMsg += `- Total weekly hours: ${weeklyHours}`;
                    
                    // Display subject-specific hours if we have a subject selected
                    if (subjectId && response.subject_daily_hours && response.subject_daily_hours[subjectId]) {
                        infoMsg += `<br>- Selected subject hours today: ${response.subject_daily_hours[subjectId]}`;
                    }
                    
                    infoMsg += `<br><br><em>Global constraints: 
                        <br>- Multiple theory subjects allowed per day
                        <br>- Maximum 2 hours per theory subject per day
                        <br>- Maximum 5 lab subjects per day
                        <br>- Only 1 lab subject per day</em>`;
                    
                    if (designation) {
                        infoMsg += `<br><br><em>${designation} specific limits also apply</em>`;
                    }
                    
                    showAlert(infoMsg, 'info');
                } else {
                    // Generic validation failure
                    showAlert('This schedule would violate workload constraints', 'warning');
                    $('#submitBtn').prop('disabled', true);
                }
            } else {
                console.warn('Invalid response format:', response);
                $('#submitBtn').prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error checking schedule limits:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            $('#submitBtn').prop('disabled', false);
            showAlert('Error checking schedule limits: ' + error, 'danger');
        }
    });
}

// Function to toggle schedule enable/disable status
function toggleScheduleStatus(scheduleId, currentStatus) {
    if (!confirm(currentStatus == 1 ? 
        'Are you sure you want to disable this schedule? This will make the hour available for other teachers.' : 
        'Are you sure you want to enable this schedule?')) {
        return;
    }
    
    // Show loading message
    showAlert('Updating schedule status...', 'info');
    
    $.ajax({
        url: 'toggle_schedule_status.php',
        type: 'POST',
        data: {
            schedule_id: scheduleId,
            current_status: currentStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                showAlert(response.message || 'Schedule status updated successfully!', 'success');
                
                // Reload the schedule table
                loadCurrentSchedule();
            } else {
                // Show error message
                showAlert(response.message || 'Failed to update schedule status.', 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error toggling schedule status:', error);
            showAlert('An error occurred while updating the schedule status.', 'danger');
        }
    });
}
    </script>
</body>
</html>

