<?php
session_start();
require_once 'config.php';

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
        throw new Exception('Unauthorized access');
    }

    $conn = connectDB();

    // Get department ID
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $department_id = $stmt->get_result()->fetch_assoc()['department_id'];

    // Get subject workload data with subject and course details
    $query = "SELECT sw.id, s.name as subject_name, c.name as course_name, 
                    sw.weekly_hours, s.subject_type, s.credit_points, sw.is_enabled
              FROM subject_workload sw
              JOIN subjects s ON sw.subject_id = s.id
              JOIN courses c ON sw.course_id = c.id
              WHERE sw.department_id = ?
              ORDER BY c.name, s.name";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Credits</th>
                    <th>Weekly Hours</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($row['subject_type'])); ?></td>
                    <td><?php echo $row['credit_points']; ?></td>
                    <td id="hours-<?php echo $row['id']; ?>"><?php echo $row['weekly_hours']; ?></td>
                    <td>
                        <?php if($row['is_enabled']): ?>
                            <span class="badge badge-success">Enabled</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($row['is_enabled']): ?>
                            <button class="btn btn-warning btn-sm mr-1" 
                                    onclick="toggleWorkloadStatus(<?php echo $row['id']; ?>, 0)">
                                <i class="fas fa-ban"></i> Disable
                            </button>
                        <?php else: ?>
                            <button class="btn btn-success btn-sm mr-1" 
                                    onclick="toggleWorkloadStatus(<?php echo $row['id']; ?>, 1)">
                                <i class="fas fa-check"></i> Enable
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-primary btn-sm" 
                                onclick="editWorkload(<?php echo $row['id']; ?>, <?php echo $row['weekly_hours']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
    } else {
        echo "<p>No subject workload assigned yet.</p>";
    }

} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
