<?php
// admin/hierarchy.php - Manage Traditional Positions & Stool Appointments

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission (Only Administrator or Traditional Council Secretary)
require_login(['Administrator', 'Traditional Council Secretary']);

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle position delete
if ($action === 'delete_pos' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM traditional_positions WHERE id = ?");
        $stmt->execute([$id]);
        log_audit('position_delete', "Deleted traditional position ID: $id");
        $success = "Traditional position deleted successfully.";
    } catch (PDOException $e) {
        $error = "Cannot delete position: It contains active or past appointments.";
    }
    $action = 'list';
}

// Handle stool appointment destoolment / retirement
if ($action === 'retire' && isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $retire_status = $_GET['status'] ?? 'Retired'; // Retired, Deceased, Destooled
    if (!in_array($retire_status, ['Retired', 'Deceased', 'Destooled'])) {
        $retire_status = 'Retired';
    }
    
    try {
        // Update active appointment to retired
        $stmt = $pdo->prepare("UPDATE appointments SET end_date = CURDATE(), status = ? WHERE id = ?");
        $stmt->execute([$retire_status, $appointment_id]);
        
        // Fetch details for logging
        $stmtDetails = $pdo->prepare("SELECT position_id, member_id FROM appointments WHERE id = ?");
        $stmtDetails->execute([$appointment_id]);
        $app = $stmtDetails->fetch();
        
        if ($app) {
            log_audit('appointment_end', "Ended appointment (Status: $retire_status) for Member ID: {$app['member_id']} on Position ID: {$app['position_id']}");
        }
        
        $success = "Appointment successfully updated to $retire_status.";
    } catch (PDOException $e) {
        $error = "Failed to update appointment: " . $e->getMessage();
    }
    $action = 'list';
}

// Handle Form Submission for Traditional Position (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_position'])) {
    $title = trim($_POST['title'] ?? '');
    $hierarchy_level = intval($_POST['hierarchy_level'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        $error = 'Stool Title is required.';
    } else {
        if ($action === 'add_pos') {
            try {
                $stmt = $pdo->prepare("INSERT INTO traditional_positions (title, hierarchy_level, description) VALUES (?, ?, ?)");
                $stmt->execute([$title, $hierarchy_level, $description]);
                log_audit('position_add', "Added traditional position: $title");
                $success = "Stool position created successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Failed to create position: " . $e->getMessage();
            }
        } elseif ($action === 'edit_pos' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            try {
                $stmt = $pdo->prepare("UPDATE traditional_positions SET title = ?, hierarchy_level = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $hierarchy_level, $description, $id]);
                log_audit('position_edit', "Updated traditional position: $title (ID: $id)");
                $success = "Stool position updated successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Failed to update position: " . $e->getMessage();
            }
        }
    }
}

// Handle Form Submission for Stool Installation (Appointment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_installation'])) {
    $position_id = intval($_POST['position_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $installation_details = trim($_POST['installation_details'] ?? '');
    $serves_under_id = !empty($_POST['serves_under_id']) ? intval($_POST['serves_under_id']) : null;
    
    if (empty($position_id) || empty($member_id) || empty($start_date)) {
        $error = 'Stool Position, Member selection, and Installation Date are required.';
    } else {
        try {
            // Check if there is already an active chief on this stool
            $stmtCheck = $pdo->prepare("SELECT id, member_id FROM appointments WHERE position_id = ? AND status = 'Active'");
            $stmtCheck->execute([$position_id]);
            $activeApp = $stmtCheck->fetch();
            
            $pdo->beginTransaction();
            
            // If there's an active chief, we must end their appointment first (or prompt user, here we automate it)
            if ($activeApp) {
                $predecessorId = $activeApp['member_id'];
                
                // End predecessor's active appointment
                $stmtEnd = $pdo->prepare("UPDATE appointments SET end_date = ?, status = 'Retired' WHERE id = ?");
                $stmtEnd->execute([$start_date, $activeApp['id']]);
                
                // Record in succession history
                $stmtSucc = $pdo->prepare("INSERT INTO succession_history (position_id, predecessor_id, successor_id, succession_date, reason, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtSucc->execute([
                    $position_id, $predecessorId, $member_id, $start_date, 
                    'Succession following predecessor retirement', 
                    'Automatically recorded succession through dashboard installation.'
                ]);
            }
            
            // Insert new appointment
            $stmtInsert = $pdo->prepare("INSERT INTO appointments (position_id, member_id, start_date, status, installation_details, serves_under_id) VALUES (?, ?, ?, 'Active', ?, ?)");
            $stmtInsert->execute([$position_id, $member_id, $start_date, $installation_details, $serves_under_id]);
            
            $pdo->commit();
            
            $mName = get_member_name($member_id);
            log_audit('stool_installation', "Installed member $mName to position ID $position_id");
            $success = "Installation successfully completed and recorded.";
            $action = 'list';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Failed to complete installation: " . $e->getMessage();
        }
    }
}

// Fetch position details for editing
$editPos = null;
if ($action === 'edit_pos' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM traditional_positions WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editPos = $stmt->fetch();
    if (!$editPos) {
        $error = 'Stool position not found.';
        $action = 'list';
    }
}

// Fetch lists
$positions = $pdo->query("
    SELECT tp.*, 
           fm.id as member_id, fm.first_name, fm.last_name, fm.status as member_status,
           a.id as appointment_id, a.start_date, a.serves_under_id,
           su.first_name as su_first_name, su.last_name as su_last_name, sutp.title as su_title
    FROM traditional_positions tp
    LEFT JOIN appointments a ON tp.id = a.position_id AND a.status = 'Active'
    LEFT JOIN family_members fm ON a.member_id = fm.id
    LEFT JOIN family_members su ON a.serves_under_id = su.id
    LEFT JOIN appointments sua ON su.id = sua.member_id AND sua.status = 'Active'
    LEFT JOIN traditional_positions sutp ON sua.position_id = sutp.id
    ORDER BY tp.hierarchy_level ASC, tp.id ASC
")->fetchAll();

$eligibleMembers = $pdo->query("SELECT id, first_name, last_name FROM family_members WHERE status = 'Alive' ORDER BY first_name ASC")->fetchAll();

// Fetch active chiefs (Level 1 and 2) to serve under
$activeChiefs = [];
try {
    $activeChiefs = $pdo->query("
        SELECT a.id as appointment_id, fm.id as member_id, fm.first_name, fm.last_name, tp.title 
        FROM appointments a
        JOIN family_members fm ON a.member_id = fm.id
        JOIN traditional_positions tp ON a.position_id = tp.id
        WHERE a.status = 'Active' AND tp.hierarchy_level <= 2
        ORDER BY tp.hierarchy_level ASC, fm.first_name ASC
    ")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-crown text-warning me-2"></i> Chieftaincy Stools & Appointments</h2>
    <div>
        <?php if ($action === 'list'): ?>
            <a href="hierarchy.php?action=install" class="btn btn-royal me-2"><i class="fas fa-arrow-up-from-bracket me-1"></i> Install Stool Chief</a>
            <a href="hierarchy.php?action=add_pos" class="btn btn-outline-success"><i class="fas fa-plus me-1"></i> Create Stool Office</a>
        <?php else: ?>
            <a href="hierarchy.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Directory</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. STOOL DIRECTORY LISTING -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white mb-4">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Hierarchy Rank</th>
                    <th>Stool Office</th>
                    <th>Incumbent Leader & Serves Under</th>
                    <th>Installation Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($positions)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No stools or positions created yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($positions as $pos): 
                        $hasLeader = !empty($pos['member_id']);
                        $name = $hasLeader ? sanitize($pos['first_name'] . ' ' . $pos['last_name']) : '<em class="text-muted">Stool Vacant / Regent Rule</em>';
                    ?>
                        <tr>
                            <td>
                                <?php 
                                $levelLabels = [
                                    1 => 'Level 1: Paramount Paramouncy',
                                    2 => 'Level 2: Divisional/Wing Chiefs',
                                    3 => 'Level 3: Sub-Chief/Linguist',
                                    4 => 'Level 4: Family Heads/Elders',
                                    5 => 'Level 5: Principal & Notable Elders',
                                    6 => 'Level 6: Youth Leaders'
                                ];
                                ?>
                                <span class="badge bg-success"><?php echo $levelLabels[$pos['hierarchy_level']] ?? 'Level ' . $pos['hierarchy_level']; ?></span>
                            </td>
                            <td>
                                <strong><?php echo sanitize($pos['title']); ?></strong>
                                <small class="text-muted d-block" style="font-size: 0.8rem;"><?php echo sanitize(substr($pos['description'], 0, 80)) . '...'; ?></small>
                            </td>
                            <td>
                                <?php echo $name; ?>
                                <?php if ($hasLeader && !empty($pos['serves_under_id'])): ?>
                                    <div class="small text-muted mt-1">
                                        <i class="fas fa-arrow-turn-up fa-rotate-90 text-warning me-1"></i> 
                                        Serves: <?php echo sanitize(($pos['su_title'] ? $pos['su_title'] . ' ' : '') . $pos['su_first_name'] . ' ' . $pos['su_last_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $hasLeader ? date('M d, Y', strtotime($pos['start_date'])) : 'N/A'; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($hasLeader): ?>
                                    <div class="btn-group me-2">
                                        <button type="button" class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown">
                                            Manage Incumbent
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="hierarchy.php?action=retire&status=Retired&id=<?php echo $pos['appointment_id']; ?>">Retire / abdicate</a></li>
                                            <li><a class="dropdown-item" href="hierarchy.php?action=retire&status=Deceased&id=<?php echo $pos['appointment_id']; ?>">Decease of chief</a></li>
                                            <li><a class="dropdown-item text-danger" href="hierarchy.php?action=retire&status=Destooled&id=<?php echo $pos['appointment_id']; ?>" onclick="return confirm('Confirm destoolment action?');">Destool Chief</a></li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                <a href="hierarchy.php?action=edit_pos&id=<?php echo $pos['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="hierarchy.php?action=delete_pos&id=<?php echo $pos['id']; ?>" onclick="return confirm('Are you sure you want to delete this traditional position? All historical appointments will be deleted.');" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- 2. CREATE / EDIT STOOL OFFICE -->
<?php elseif ($action === 'add_pos' || $action === 'edit_pos'): 
    $title = ($action === 'add_pos') ? 'Create Stool Office' : 'Edit Stool Office: ' . sanitize($editPos['title']);
    $submitText = ($action === 'add_pos') ? 'Create Office' : 'Update Office';
    
    $titleVal = ($action === 'edit_pos') ? $editPos['title'] : '';
    $rankVal = ($action === 'edit_pos') ? $editPos['hierarchy_level'] : 1;
    $descVal = ($action === 'edit_pos') ? $editPos['description'] : '';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $title; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <input type="hidden" name="submit_position" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="title">Stool Title / Position Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo sanitize($titleVal); ?>" required placeholder="e.g. Right Wing Chief (Dusifiaga)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="hierarchy_level">Hierarchy level Rank <span class="text-danger">*</span></label>
                        <select class="form-select" id="hierarchy_level" name="hierarchy_level" required>
                            <option value="1" <?php echo ($rankVal == 1) ? 'selected' : ''; ?>>Level 1 (Paramount Paramouncy)</option>
                            <option value="2" <?php echo ($rankVal == 2) ? 'selected' : ''; ?>>Level 2 (Divisional / Wing Chiefs)</option>
                            <option value="3" <?php echo ($rankVal == 3) ? 'selected' : ''; ?>>Level 3 (Sub-Chief / Linguist / Stool Father)</option>
                            <option value="4" <?php echo ($rankVal == 4) ? 'selected' : ''; ?>>Level 4 (Family Heads / Elders)</option>
                            <option value="5" <?php echo ($rankVal == 5) ? 'selected' : ''; ?>>Level 5 (Principal & Notable Elders)</option>
                            <option value="6" <?php echo ($rankVal == 6) ? 'selected' : ''; ?>>Level 6 (Youth Leaders)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="description">Position Responsibilities & History</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Define the customary roles, stool history, and guidelines associated with this rank..."><?php echo sanitize($descVal); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>

<!-- 3. INSTALL STOOL CHIEF (APPOINTMENT) -->
<?php elseif ($action === 'install'): ?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><i class="fas fa-scroll text-warning me-2"></i> Custom Stool Installation Registry</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <input type="hidden" name="submit_installation" value="1">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="position_id">Stool Stool Office <span class="text-danger">*</span></label>
                        <select class="form-select" id="position_id" name="position_id" required>
                            <option value="">-- Select Office --</option>
                            <?php foreach ($positions as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo sanitize($p['title']); ?> (Level <?php echo $p['hierarchy_level']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Note: If there is an active chief on the selected stool, registering this installation will automatically archive the predecessor and log a succession record.</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="member_id">Nominated Royal Member <span class="text-danger">*</span></label>
                        <select class="form-select" id="member_id" name="member_id" required>
                            <option value="">-- Select Family Member --</option>
                            <?php foreach ($eligibleMembers as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="start_date">Installation Custom Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="serves_under_id">Serves Under Chief / Office (For Linguist / Assistant)</label>
                        <select class="form-select" id="serves_under_id" name="serves_under_id">
                            <option value="">-- None / Select Chief to serve under --</option>
                            <?php foreach ($activeChiefs as $ac): ?>
                                <option value="<?php echo $ac['member_id']; ?>">
                                    <?php echo sanitize(($ac['title'] ? $ac['title'] . ': ' : '') . $ac['first_name'] . ' ' . $ac['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Select the active ruling Chief that this Linguist (Tsiami) or companion (Agbotadua) is serving under.</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="installation_details">Installation Rites & Customary Notes</label>
                    <textarea class="form-control" id="installation_details" name="installation_details" rows="5" placeholder="Document the customary confinement, stool names given, kingmakers present, and community durbar events..."></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-check-double me-1"></i> Register Installation</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
