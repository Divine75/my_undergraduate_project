<?php
// admin/users.php - System User Accounts Management

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// strictly enforce Administrator role
require_login(['Administrator']);

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete Action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Prevent self-deletion
    if ($id === intval($_SESSION['user_id'])) {
        $error = "You cannot delete your own administrator account.";
    } else {
        try {
            $stmtName = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmtName->execute([$id]);
            $username = $stmtName->fetchColumn();
            
            if ($username) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                log_audit('user_delete', "Deleted user account: $username (ID: $id)");
                $success = "User account successfully deleted.";
            }
        } catch (PDOException $e) {
            $error = "Cannot delete user: They may have uploaded files or written audit records.";
        }
    }
    $action = 'list';
}

// Handle Form Submission (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'Viewer';
    $status = $_POST['status'] ?? 'Active';
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($email) || empty($full_name)) {
        $error = 'Username, Email, and Full Name are required.';
    } else {
        if ($action === 'add') {
            if (empty($password)) {
                $error = 'Password is required for new accounts.';
            } else {
                try {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $passwordHash, $email, $full_name, $role, $status]);
                    $newId = $pdo->lastInsertId();
                    log_audit('user_add', "Created user: $username (ID: $newId)");
                    $success = "User account created successfully.";
                    $action = 'list';
                } catch (PDOException $e) {
                    $error = "Failed to create user (Username or Email may already exist).";
                }
            }
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            try {
                // Base update
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, role = ?, status = ? WHERE id = ?");
                $stmt->execute([$username, $email, $full_name, $role, $status, $id]);
                
                // If password was provided, update it too
                if (!empty($password)) {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmtPass->execute([$passwordHash, $id]);
                }
                
                log_audit('user_edit', "Updated user: $username (ID: $id)");
                $success = "User account updated successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Failed to update user details.";
            }
        }
    }
}

// Fetch user details for editing
$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editUser = $stmt->fetch();
    if (!$editUser) {
        $error = 'User account not found.';
        $action = 'list';
    }
}

// Fetch all users
$users = [];
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY role ASC, username ASC")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-user-gear text-warning me-2"></i> System User Registry</h2>
    <?php if ($action === 'list'): ?>
        <a href="users.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Create Account</a>
    <?php else: ?>
        <a href="users.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Listing</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. USERS LISTING -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white mb-4">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Username</th>
                    <th>Email Address</th>
                    <th>Role Group</th>
                    <th>Account Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?php echo sanitize($u['full_name']); ?></strong></td>
                        <td><code><?php echo sanitize($u['username']); ?></code></td>
                        <td><?php echo sanitize($u['email']); ?></td>
                        <td><span class="badge bg-success"><?php echo sanitize($u['role']); ?></span></td>
                        <td>
                            <span class="badge bg-<?php echo ($u['status'] === 'Active') ? 'success' : 'secondary'; ?> rounded-pill">
                                <?php echo sanitize($u['status']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-user-pen"></i> Edit
                            </a>
                            <?php if (intval($u['id']) !== intval($_SESSION['user_id'])): ?>
                                <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" onclick="return confirm('Confirm permanent deletion of this account?');" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-user-xmark"></i> Delete
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<!-- 2. ADD / EDIT USER FORM -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $titleHeader = ($action === 'add') ? 'Create System Account' : 'Edit Account: ' . sanitize($editUser['username']);
    $submitText = ($action === 'add') ? 'Create User' : 'Update User';
    
    $userVal = ($action === 'edit') ? $editUser['username'] : '';
    $emailVal = ($action === 'edit') ? $editUser['email'] : '';
    $nameVal = ($action === 'edit') ? $editUser['full_name'] : '';
    $roleVal = ($action === 'edit') ? $editUser['role'] : 'Viewer';
    $statusVal = ($action === 'edit') ? $editUser['status'] : 'Active';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $titleHeader; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="username">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo sanitize($userVal); ?>" required placeholder="e.g. secretary_atsiame">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitize($emailVal); ?>" required placeholder="e.g. secretary@atsiame.org">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="full_name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo sanitize($nameVal); ?>" required placeholder="e.g. Kofi Secretary">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="password">Password <?php echo ($action === 'add') ? '<span class="text-danger">*</span>' : '(Leave blank to keep unchanged)'; ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo ($action === 'add') ? 'required' : ''; ?> placeholder="Choose a strong password">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="role">Role Permission Group</label>
                        <select class="form-select" id="role" name="role">
                            <option value="Administrator" <?php echo ($roleVal === 'Administrator') ? 'selected' : ''; ?>>Administrator (Full System Access)</option>
                            <option value="Traditional Council Secretary" <?php echo ($roleVal === 'Traditional Council Secretary') ? 'selected' : ''; ?>>Traditional Council Secretary</option>
                            <option value="Data Entry Officer" <?php echo ($roleVal === 'Data Entry Officer') ? 'selected' : ''; ?>>Data Entry Officer</option>
                            <option value="Research Officer" <?php echo ($roleVal === 'Research Officer') ? 'selected' : ''; ?>>Research Officer (Archives & Genealogy)</option>
                            <option value="Council Member" <?php echo ($roleVal === 'Council Member') ? 'selected' : ''; ?>>Council Member (Read Only)</option>
                            <option value="Viewer" <?php echo ($roleVal === 'Viewer') ? 'selected' : ''; ?>>Viewer / Guest</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label font-weight-bold" for="status">Account Access Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Active" <?php echo ($statusVal === 'Active') ? 'selected' : ''; ?>>Active (Approved to log in)</option>
                            <option value="Inactive" <?php echo ($statusVal === 'Inactive') ? 'selected' : ''; ?>>Inactive (Suspended)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
