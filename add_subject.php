<?php
require_once 'config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn = connectDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['subject-name']);
        $hasCredits = $_POST['has-credits'] === '1';
        $creditPoints = $hasCredits ? (int)$_POST['credit-points'] : null;
        $semesterId = $_POST['semester_id'];
        $subjectType = $_POST['subject-type'];

        // Validate inputs
        if (empty($name)) {
            throw new Exception('Subject name is required');
        }

        // Validate subject type
        if (!in_array($subjectType, ['theory', 'lab', 'elective'])) {
            throw new Exception('Invalid subject type');
        }

        // Validate credit points based on subject type
        if ($hasCredits) {
            switch ($subjectType) {
                case 'theory':
                    if (!in_array($creditPoints, [2, 3, 4])) {
                        throw new Exception('Theory subjects can only have 2, 3, or 4 credit points');
                    }
                    break;
                    
                case 'lab':
                    if (!in_array($creditPoints, [1, 2])) {
                        throw new Exception('Lab subjects can only have 1 or 2 credit points');
                    }
                    break;
                    
                case 'elective':
                    if ($creditPoints !== 4) {
                        throw new Exception('Elective subjects must have exactly 4 credit points');
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid subject type');
            }
        }

        // Get course_id and department_id based on the semester_id
        $query = "SELECT s.course_id, c.department_id 
                  FROM semesters s 
                  JOIN courses c ON s.course_id = c.id 
                  WHERE s.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $semesterId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Invalid semester ID");
        }
        
        $relationData = $result->fetch_assoc();
        $courseId = $relationData['course_id'];
        $departmentId = $relationData['department_id'];
        
        $stmt->close();
        
        // Insert subject with all the foreign keys
        $query = "INSERT INTO subjects (name, has_credits, credit_points, semester_id, course_id, department_id, subject_type) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siiiiis", $name, $hasCredits, $creditPoints, $semesterId, $courseId, $departmentId, $subjectType);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Error executing query: " . $stmt->error);
        }

        $stmt->close();
    } else {
        throw new Exception('Invalid request method');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 
?>