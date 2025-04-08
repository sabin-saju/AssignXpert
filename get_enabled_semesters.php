<?php
include 'config.php';  // Remove the '../'

$sql = "SELECT name, code FROM departments WHERE is_disabled = 0";
$result = $conn->query($sql);

$departments = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($departments);
$conn->close();
?> 