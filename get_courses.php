<?php
require_once 'config.php';

$conn = connectDB();

$query = "SELECT id, name, code, num_semesters, is_disabled 
          FROM courses 
          ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

echo json_encode($courses);

$stmt->close();
$conn->close(); 
?>