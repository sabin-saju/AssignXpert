<?php
session_start();
require_once 'db_connection.php';

// Verify if user is HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header('Location: login.php');
    exit;
}

$designation = $_GET['designation'] ?? '';
$remaining_hours = $_GET['remaining'] ?? 0;

// Get department ID of logged-in HOD
$dept_query = "SELECT department_id FROM teachers WHERE user_id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$department_id = $row['department_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject_id = $_POST['subject_id'];
    $course_id = $_POST['course_id'];
    $weekly_hours = $_POST['weekly_hours'];
    
    if ($weekly_hours <= $remaining_hours) {
        $insert_query = "INSERT INTO designation_workload (designation, subject_id, course_id, department_id, weekly_hours) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("siiii", $designation, $subject_id, $course_id, $department_id, $weekly_hours);
        
        if ($stmt->execute()) {
            header("Location: hod_dashboard.php?success=1");
            exit;
        }
    }
}

// Get subjects for the department
$subject_query = "SELECT id, name FROM subjects WHERE department_id = ?";
$stmt = $conn->prepare($subject_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$subjects = $stmt->get_result();

// Get courses for the department
$course_query = "SELECT id, name FROM courses WHERE department_id = ?";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$courses = $stmt->get_result();

// In the form validation or when displaying max hours
$max_hours = 22; // Default for Junior Assistant Professor

if ($designation === 'Senior Assistant Professor') {
    $max_hours = 20;
} else if ($designation === 'Associate Professor') {
    $max_hours = 18;
} else if ($designation === 'HOD') {
    $max_hours = 16;
}
?>

<div class="container mt-4">
    <h2>Add Workload for <?php echo htmlspecialchars($designation); ?></h2>
    <form method="POST">
        <div class="form-group">
            <label>Subject</label>
            <select name="subject_id" class="form-control" required>
                <?php while($subject = $subjects->fetch_assoc()): ?>
                    <option value="<?php echo $subject['id']; ?>">
                        <?php echo htmlspecialchars($subject['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Course</label>
            <select name="course_id" class="form-control" required>
                <?php while($course = $courses->fetch_assoc()): ?>
                    <option value="<?php echo $course['id']; ?>">
                        <?php echo htmlspecialchars($course['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Weekly Hours (Maximum: <?php echo $remaining_hours; ?>)</label>
            <input type="number" name="weekly_hours" class="form-control" 
                   max="<?php echo $remaining_hours; ?>" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Add Workload</button>
        <a href="hod_dashboard.php" class="btn btn-secondary">Back</a>
    </form>
</div>
