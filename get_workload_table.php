<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

try {
    // Establish database connection
    $conn = connectDB();

    // Get department ID
    $dept_query = "SELECT department_id FROM hod WHERE user_id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $department_id = $stmt->get_result()->fetch_assoc()['department_id'];

    // Get workload data - no need to join with subjects and courses
    $query = "SELECT * FROM designation_workload
              WHERE department_id = ?
              ORDER BY designation, created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Designation</th>
                    <th>Weekly Hours</th>
                    <th>Date Added</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['designation']); ?></td>
                    <td id="hours-<?php echo $row['id']; ?>"><?php echo $row['weekly_hours']; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
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
                                onclick="editDesignationWorkload(<?php echo $row['id']; ?>, '<?php echo $row['designation']; ?>', <?php echo $row['weekly_hours']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
    } else {
        echo "<p>No workload data found.</p>";
    }

} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<script>
function toggleWorkloadStatus(id, status) {
    const action = status === 1 ? 'enable' : 'disable';
    if (confirm(`Are you sure you want to ${action} this workload?`)) {
        $.ajax({
            url: 'toggle_designation_workload.php',
            method: 'POST',
            data: { 
                id: id,
                status: status 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(`Workload ${action}d successfully!`);
                    loadWorkloadTable();
                } else {
                    alert(response.message || `Error ${action}ing workload`);
                }
            },
            error: function() {
                alert(`Error occurred while ${action}ing workload`);
            }
        });
    }
}

function editDesignationWorkload(id, designation, currentHours) {
    let maxHours = 22;
    
    // Set max hours based on designation
    if (designation === 'Junior Assistant Professor') {
        maxHours = 22;
    } else if (designation === 'Senior Assistant Professor') {
        maxHours = 20;
    } else if (designation === 'Associate Professor') {
        maxHours = 18;
    } else if (designation === 'HOD') {
        maxHours = 16;
    }
    
    const newHours = prompt(`Enter new weekly hours for ${designation} (max ${maxHours})`, currentHours);
    
    if (newHours === null) {
        return; // User cancelled
    }
    
    // Convert to number and validate
    const hours = parseInt(newHours);
    if (isNaN(hours) || hours <= 0 || hours > maxHours) {
        alert(`Weekly hours must be between 1 and ${maxHours}`);
        return;
    }
    
    $.ajax({
        url: 'update_designation_workload.php',
        method: 'POST',
        data: { 
            id: id,
            weekly_hours: hours 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Workload updated successfully!');
                // Update the specific cell instead of reloading the whole table
                $(`#hours-${id}`).text(hours);
            } else {
                alert(response.message || 'Error updating workload');
            }
        },
        error: function() {
            alert('Error occurred while updating workload');
        }
    });
}
</script>
