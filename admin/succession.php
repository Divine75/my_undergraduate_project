<?php
// admin/succession.php - Manage Chieftaincy Succession History

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission
require_login(['Administrator', 'Traditional Council Secretary']);

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM succession_history WHERE id = ?");
        $stmt->execute([$id]);
        log_audit('succession_delete', "Deleted succession log ID: $id");
        $success = "Succession log deleted successfully.";
    } catch (PDOException $e) {
        $error = "Failed to delete succession log: " . $e->getMessage();
    }
    $action = 'list';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position_id = intval($_POST['position_id'] ?? 0);
    $predecessor_id = !empty($_POST['predecessor_id']) ? intval($_POST['predecessor_id']) : null;
    $successor_id = intval($_POST['successor_id'] ?? 0);
    $succession_date = $_POST['succession_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($position_id) || empty($successor_id) || empty($succession_date)) {
        $error = 'Stool position, Successor selection, and Succession Date are required.';
    } else {
        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO succession_history (position_id, predecessor_id, successor_id, succession_date, reason, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$position_id, $predecessor_id, $successor_id, $succession_date, $reason, $notes]);
                $newId = $pdo->lastInsertId();
                log_audit('succession_add', "Recorded succession for Stool ID: $position_id, log ID: $newId");
                $success = "Succession history recorded successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Failed to add succession record: " . $e->getMessage();
            }
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            try {
                $stmt = $pdo->prepare("
                    UPDATE succession_history SET 
                    position_id = ?, predecessor_id = ?, successor_id = ?, succession_date = ?, reason = ?, notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$position_id, $predecessor_id, $successor_id, $succession_date, $reason, $notes, $id]);
                log_audit('succession_edit', "Edited succession log ID: $id");
                $success = "Succession history updated successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Failed to update succession record: " . $e->getMessage();
            }
        }
    }
}

// Fetch record for editing
$editLog = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM succession_history WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editLog = $stmt->fetch();
    if (!$editLog) {
        $error = 'Succession log not found.';
        $action = 'list';
    }
}

// Fetch lists
$successionLogs = [];
try {
    $successionLogs = $pdo->query("
        SELECT sh.*, tp.title as position_title 
        FROM succession_history sh 
        JOIN traditional_positions tp ON sh.position_id = tp.id 
        ORDER BY sh.succession_date DESC
    ")->fetchAll();
} catch (PDOException $e) {}

$positions = $pdo->query("SELECT id, title FROM traditional_positions ORDER BY hierarchy_level ASC")->fetchAll();
$allMembers = $pdo->query("SELECT id, first_name, last_name FROM family_members ORDER BY first_name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-arrow-right-arrow-left text-warning me-2"></i> Chieftaincy Succession Logs</h2>
    <?php if ($action === 'list'): ?>
        <a href="succession.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Log Succession</a>
    <?php else: ?>
        <a href="succession.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Logs</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. SUCCESSION LOG LISTING -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white mb-4">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Stool Office</th>
                    <th>Predecessor</th>
                    <th>Successor</th>
                    <th>Succession Date</th>
                    <th>Reason</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($successionLogs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No succession entries recorded on this stool timeline yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($successionLogs as $log): ?>
                        <tr>
                            <td><strong><?php echo sanitize($log['position_title']); ?></strong></td>
                            <td><?php echo $log['predecessor_id'] ? get_member_name($log['predecessor_id']) : '<em class="text-muted">Origin / Ancestor Stool</em>'; ?></td>
                            <td><?php echo get_member_name($log['successor_id']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo date('M d, Y', strtotime($log['succession_date'])); ?></span></td>
                            <td><span style="font-size: 0.9rem;"><?php echo sanitize($log['reason']); ?></span></td>
                            <td class="text-end">
                                <a href="succession.php?action=edit&id=<?php echo $log['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="succession.php?action=delete&id=<?php echo $log['id']; ?>" onclick="return confirm('Are you sure you want to delete this succession record?');" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- 2. CREATE / EDIT SUCCESSION ENTRY -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $title = ($action === 'add') ? 'Log Chieftaincy Succession' : 'Edit Succession Entry';
    $submitText = ($action === 'add') ? 'Save Succession Record' : 'Update Succession Record';
    
    $posVal = ($action === 'edit') ? $editLog['position_id'] : '';
    $predVal = ($action === 'edit') ? $editLog['predecessor_id'] : '';
    $succVal = ($action === 'edit') ? $editLog['successor_id'] : '';
    $dateVal = ($action === 'edit') ? $editLog['succession_date'] : date('Y-m-d');
    $reasonVal = ($action === 'edit') ? $editLog['reason'] : '';
    $notesVal = ($action === 'edit') ? $editLog['notes'] : '';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $title; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="position_id">Stool Position / Office <span class="text-danger">*</span></label>
                        <select class="form-select" id="position_id" name="position_id" required>
                            <option value="">-- Select Stool --</option>
                            <?php foreach ($positions as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $posVal) ? 'selected' : ''; ?>><?php echo sanitize($p['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="predecessor_id">Predecessor</label>
                        <select class="form-select" id="predecessor_id" name="predecessor_id">
                            <option value="">-- Select Predecessor (If any) --</option>
                            <?php foreach ($allMembers as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $predVal) ? 'selected' : ''; ?>><?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="successor_id">Successor <span class="text-danger">*</span></label>
                        <select class="form-select" id="successor_id" name="successor_id" required>
                            <option value="">-- Select Successor --</option>
                            <?php foreach ($allMembers as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $succVal) ? 'selected' : ''; ?>><?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="succession_date">Date of Succession <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="succession_date" name="succession_date" value="<?php echo $dateVal; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="reason">Reason for Succession</label>
                        <select class="form-select" id="reason" name="reason">
                            <option value="Decease of predecessor" <?php echo ($reasonVal === 'Decease of predecessor') ? 'selected' : ''; ?>>Decease of predecessor</option>
                            <option value="Abdication" <?php echo ($reasonVal === 'Abdication') ? 'selected' : ''; ?>>Abdication</option>
                            <option value="Destoolment" <?php echo ($reasonVal === 'Destoolment') ? 'selected' : ''; ?>>Destoolment</option>
                            <option value="Regency Handover" <?php echo ($reasonVal === 'Regency Handover') ? 'selected' : ''; ?>>Regency Handover</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="notes">Customary Succession Notes / Confinement History</label>
                    <textarea class="form-control" id="notes" name="notes" rows="5" placeholder="Document the transition, stool name adjustments, and notable events here..."><?php echo sanitize($notesVal); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
