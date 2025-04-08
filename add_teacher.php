<?php
header('Content-Type: application/json');
require('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email, department and designation are set and not empty
    if (!isset($_POST['email']) || empty($_POST['email']) || 
        !isset($_POST['department']) || empty($_POST['department']) ||
        !isset($_POST['designation']) || empty($_POST['designation'])) {
        die(json_encode([
            'success' => false,
            'message' => 'Email, department, and designation are required'
        ]));
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        die(json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]));
    }

    $department = $_POST['department'];
    $designation = $_POST['designation'];
    
    // Validate designation
    $validDesignations = ['Junior Assistant Professor', 'Senior Assistant Professor', 'Associate Professor'];
    if (!in_array($designation, $validDesignations)) {
        die(json_encode([
            'success' => false,
            'message' => 'Invalid designation'
        ]));
    }

    try {
        // First, find the teacher by email
        $stmt = $conn->prepare("SELECT t.id FROM teachers t 
                                JOIN users u ON t.user_id = u.user_id 
                                WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Teacher with this email not found");
        }
        
        $teacher = $result->fetch_assoc();
        $teacherId = $teacher['id'];
        
        // Update the department_id and designation
        $stmt = $conn->prepare("UPDATE teachers SET department_id = ?, designation = ? WHERE id = ?");
        $stmt->bind_param("isi", $department, $designation, $teacherId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update teacher information: " . $stmt->error);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Teacher information updated successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>