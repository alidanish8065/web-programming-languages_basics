<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('public/login.php'));
    exit;
}

// Check if user has permission to view users
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    die('Access denied. You do not have permission to view this page.');
}

// Handle AJAX status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    header('Content-Type: application/json');
    
    $user_id = intval($_POST['user_id']);
    $new_status = $_POST['status'];
    
    // Validate status
    if (!in_array($new_status, ['active', 'inactive', 'suspended'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Update status
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

$active_page = 'users';

// Fetch users with their roles
$sql = "
    SELECT 
        u.id,
        u.user_code,
        u.first_name,
        u.last_name,
        u.full_name,
        u.email,
        u.contact_number,
        u.status,
        u.created_at,
        GROUP_CONCAT(r.role_name SEPARATOR ', ') as roles
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.role_id
    WHERE u.is_deleted = FALSE
    GROUP BY u.id
    ORDER BY u.created_at DESC
";

$result = $conn->query($sql);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">User Management</h4>
        <a href="create_user.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New User
        </a>
    </div>

    <?php if (empty($users)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No users found. Create your first user to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Roles</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $serial++ ?></td>
                                    <td>
                                        <code><?= htmlspecialchars($user['user_code']) ?></code>
                                    </td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td>
                                        <?php if ($user['email']): ?>
                                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                                <?= htmlspecialchars($user['email']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['contact_number'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($user['roles']): ?>
                                            <?php 
                                            $roleArray = explode(', ', $user['roles']);
                                            foreach ($roleArray as $role): 
                                            ?>
                                                <span class="badge bg-secondary me-1">
                                                    <?= ucfirst(htmlspecialchars($role)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No roles</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-<?= $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'secondary' : 'warning') ?> dropdown-toggle" 
                                                    type="button" 
                                                    id="statusDropdown<?= $user['id'] ?>" 
                                                    data-bs-toggle="dropdown" 
                                                    aria-expanded="false">
                                                <?= ucfirst(htmlspecialchars($user['status'])) ?>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="statusDropdown<?= $user['id'] ?>">
                                                <li>
                                                    <a class="dropdown-item <?= $user['status'] === 'active' ? 'active' : '' ?>" 
                                                       href="#" 
                                                       onclick="changeStatus(<?= $user['id'] ?>, 'active', '<?= htmlspecialchars($user['full_name']) ?>'); return false;">
                                                        <i class="bi bi-check-circle text-success"></i> Active
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?= $user['status'] === 'inactive' ? 'active' : '' ?>" 
                                                       href="#" 
                                                       onclick="changeStatus(<?= $user['id'] ?>, 'inactive', '<?= htmlspecialchars($user['full_name']) ?>'); return false;">
                                                        <i class="bi bi-x-circle text-secondary"></i> Inactive
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?= $user['status'] === 'suspended' ? 'active' : '' ?>" 
                                                       href="#" 
                                                       onclick="changeStatus(<?= $user['id'] ?>, 'suspended', '<?= htmlspecialchars($user['full_name']) ?>'); return false;">
                                                        <i class="bi bi-exclamation-circle text-warning"></i> Suspended
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-info" 
                                               title="View Details">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="edit_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-warning" 
                                               title="Edit User">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="delete_user.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"
                                               title="Delete User">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>



<script>
function changeStatus(userId, newStatus, userName) {
    // Confirm status change
    if (!confirm(`Are you sure you want to change ${userName}'s status to "${newStatus}"?`)) {
        return;
    }
    
    // Show loading state
    const dropdownBtn = document.getElementById('statusDropdown' + userId);
    const originalText = dropdownBtn.innerHTML;
    dropdownBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Updating...';
    dropdownBtn.disabled = true;
    
    // Send AJAX request
    fetch('user_list.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=change_status&user_id=${userId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button appearance
            dropdownBtn.className = 'btn btn-sm dropdown-toggle btn-' + 
                (newStatus === 'active' ? 'success' : (newStatus === 'inactive' ? 'secondary' : 'warning'));
            dropdownBtn.innerHTML = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            dropdownBtn.disabled = false;
            
            // Show success message
            showAlert('success', data.message);
        } else {
            // Restore original state
            dropdownBtn.innerHTML = originalText;
            dropdownBtn.disabled = false;
            
            // Show error message
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        // Restore original state
        dropdownBtn.innerHTML = originalText;
        dropdownBtn.disabled = false;
        
        // Show error message
        showAlert('danger', 'An error occurred while updating the status.');
        console.error('Error:', error);
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto dismiss after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<style>
.dropdown-item.active {
    background-color: #e9ecef;
    color: #212529;
    font-weight: bold;
}
</style>

<?php
$content = ob_get_clean();
$page_title = "User Management - LMS";
require_once include_file('templates/layout/master_base.php');
?>