<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$conn = connectDB();
$teacherId = $_SESSION['user_id'];

// Get enabled departments
$stmt = $conn->prepare("SELECT id, name FROM departments WHERE is_disabled = 0");
$stmt->execute();
$departments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Preference</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .preference-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        button {
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:disabled {
            background-color: #cccccc;
        }
    </style>
</head>
<body>
    <div class="preference-form">
        <h2>Add Teaching Preference</h2>
        <form id="preferenceForm">
            <div class="form-group">
                <label for="department">Department:</label>
                <select id="department" name="department" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="course">Course:</label>
                <select id="course" name="course" required disabled>
                    <option value="">Select Course</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="semester">Semester:</label>
                <select id="semester" name="semester" required disabled>
                    <option value="">Select Semester</option>
                </select>
            </div>
            
            <button type="submit" id="submitBtn" disabled>Save Preference</button>
        </form>
    </div>

    <script>
        document.getElementById('department').addEventListener('change', function() {
            const courseSelect = document.getElementById('course');
            const semesterSelect = document.getElementById('semester');
            const deptId = this.value;
            
            courseSelect.disabled = true;
            semesterSelect.disabled = true;
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            
            if (deptId) {
                fetch(`get_preference_courses.php?department_id=${deptId}`)
                    .then(response => response.json())
                    .then(data => {
                        courseSelect.disabled = false;
                        data.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.id;
                            option.textContent = course.name;
                            courseSelect.appendChild(option);
                        });
                    });
            }
        });

        document.getElementById('course').addEventListener('change', function() {
            const semesterSelect = document.getElementById('semester');
            const courseId = this.value;
            
            semesterSelect.disabled = true;
            semesterSelect.innerHTML = '<option value="">Select Semester</option>';
            
            if (courseId) {
                fetch(`get_preference_semesters.php?course_id=${courseId}`)
                    .then(response => response.json())
                    .then(data => {
                        semesterSelect.disabled = false;
                        data.forEach(semester => {
                            const option = document.createElement('option');
                            option.value = semester.id;
                            option.textContent = semester.name;
                            semesterSelect.appendChild(option);
                        });
                    });
            }
        });

        document.getElementById('semester').addEventListener('change', function() {
            document.getElementById('submitBtn').disabled = !this.value;
        });

        document.getElementById('preferenceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('save_preference.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Preference saved successfully!');
                    window.location.href = 'teacher_dashboard.php';
                } else {
                    alert(data.message || 'Error saving preference');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>