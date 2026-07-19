<?php
// admin/families.php - Manage Families

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission (Only Admin or Secretary can edit)
if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit', 'delete'])) {
    require_login(['Administrator', 'Traditional Council Secretary', 'Data Entry Officer']);
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete Action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        // Retrieve name for audit logging
        $stmtName = $pdo->prepare("SELECT name FROM families WHERE id = ?");
        $stmtName->execute([$id]);
        $famName = $stmtName->fetchColumn();
        
        if ($famName) {
            $stmt = $pdo->prepare("DELETE FROM families WHERE id = ?");
            $stmt->execute([$id]);
            log_audit('family_delete', "Deleted family: $famName (ID: $id)");
            $success = "Family successfully deleted.";
        }
    } catch (PDOException $e) {
        $error = "Cannot delete family: It may have members linked to it.";
    }
    $action = 'list';
}

// Handle Form Submission for Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $clan_id = intval($_POST['clan_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $family_head_id = !empty($_POST['family_head_id']) ? intval($_POST['family_head_id']) : null;
    $stool_father_id = !empty($_POST['stool_father_id']) ? intval($_POST['stool_father_id']) : null;
    $town_id = !empty($_POST['town_id']) ? intval($_POST['town_id']) : null;
    
    if (empty($name) || empty($clan_id)) {
        $error = 'Family Name and Clan selection are required.';
    } else {
        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO families (name, clan_id, description, family_head_id, stool_father_id, town_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $clan_id, $description, $family_head_id, $stool_father_id, $town_id]);
                $newId = $pdo->lastInsertId();
                log_audit('family_add', "Added family: $name (ID: $newId)");
                $success = "Family created successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Failed to create family: ' . $e->getMessage();
            }
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            try {
                $stmt = $pdo->prepare("UPDATE families SET name = ?, clan_id = ?, description = ?, family_head_id = ?, stool_father_id = ?, town_id = ? WHERE id = ?");
                $stmt->execute([$name, $clan_id, $description, $family_head_id, $stool_father_id, $town_id, $id]);
                log_audit('family_edit', "Edited family: $name (ID: $id)");
                $success = "Family updated successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Failed to update family: ' . $e->getMessage();
            }
        }
    }
}

// Fetch Family Details for Editing
$editFamily = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM families WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editFamily = $stmt->fetch();
    if (!$editFamily) {
        $error = 'Family not found.';
        $action = 'list';
    }
}

// Fetch active members to populate family head dropdown
$members = [];
try {
    $members = $pdo->query("SELECT id, first_name, last_name FROM family_members WHERE status = 'Alive' ORDER BY first_name ASC")->fetchAll();
} catch (PDOException $e) {}

// Fetch Clans to populate dropdown
$clans = [];
try {
    $clans = $pdo->query("SELECT id, name, ancestor_name FROM clans ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {}

// Fetch Families for Listing
$families = [];
try {
    $families = $pdo->query("
        SELECT f.*, c.name as clan_name, c.ancestor_name as clan_ancestor, t.name as town_name 
        FROM families f 
        JOIN clans c ON f.clan_id = c.id 
        LEFT JOIN towns t ON f.town_id = t.id
        ORDER BY f.name ASC
    ")->fetchAll();
} catch (PDOException $e) {}

// Fetch Towns to populate dropdown
$towns = [];
try {
    $towns = $pdo->query("SELECT id, name FROM towns ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-house-chimney text-warning me-2"></i> Family Registry</h2>
    <?php if ($action === 'list'): ?>
        <a href="families.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Add Family</a>
    <?php else: ?>
        <a href="families.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2" role="alert"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2" role="alert"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. FAMILY LIST -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Family Name</th>
                    <th>Parent Clan</th>
                    <th>Home Town</th>
                    <th>Head of Family</th>
                    <th>Stool Father</th>
                    <th>Registered Members</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($families)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No families registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($families as $fam): 
                        // Count family members
                        $mCount = 0;
                        try {
                            $stmtM = $pdo->prepare("SELECT COUNT(*) FROM family_members WHERE family_id = ?");
                            $stmtM->execute([$fam['id']]);
                            $mCount = $stmtM->fetchColumn();
                        } catch (PDOException $e) {}
                    ?>
                        <tr>
                            <td><strong><?php echo sanitize($fam['name']); ?></strong></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo sanitize($fam['clan_name']); ?></span>
                                <?php if (!empty($fam['clan_ancestor'])): ?>
                                    <div class="small text-muted mt-1"><i class="fas fa-user-friends me-1"></i> Ancestor: <?php echo sanitize($fam['clan_ancestor']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($fam['town_name'])): ?>
                                    <span class="badge bg-light text-success border-success-subtle"><i class="fas fa-map-pin me-1 text-warning"></i> <?php echo sanitize($fam['town_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $fam['family_head_id'] ? get_member_name($fam['family_head_id']) : '<em class="text-muted">Not Assigned</em>'; ?></td>
                            <td><?php echo $fam['stool_father_id'] ? get_member_name($fam['stool_father_id']) : '<em class="text-muted">Not Assigned</em>'; ?></td>
                            <td><span class="badge bg-success"><?php echo $mCount; ?> Members</span></td>
                            <td class="text-end">
                                <a href="families.php?action=edit&id=<?php echo $fam['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if ($user_role === 'Administrator'): ?>
                                    <a href="families.php?action=delete&id=<?php echo $fam['id']; ?>" onclick="return confirm('Are you sure you want to delete this family?');" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- 2. ADD / EDIT FORM -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $title = ($action === 'add') ? 'Add New Family' : 'Edit Family: ' . sanitize($editFamily['name']);
    $submitText = ($action === 'add') ? 'Save Family' : 'Update Family';
    
    $nameVal = ($action === 'edit') ? $editFamily['name'] : '';
    $clanVal = ($action === 'edit') ? $editFamily['clan_id'] : '';
    $descVal = ($action === 'edit') ? $editFamily['description'] : '';
    $headVal = ($action === 'edit') ? $editFamily['family_head_id'] : '';
    $stoolFatherVal = ($action === 'edit') ? $editFamily['stool_father_id'] : '';
    $townVal = ($action === 'edit') ? $editFamily['town_id'] : '';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $title; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="name">Family Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($nameVal); ?>" required placeholder="e.g. Katsriku Family">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="clan_id">Parent Clan / Great Ancestor <span class="text-danger">*</span></label>
                        <select class="form-select mb-3" id="clan_id" name="clan_id" required>
                            <option value="">-- Select Clan / Great Ancestor --</option>
                            <?php foreach ($clans as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $clanVal) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($c['name']); ?><?php echo !empty($c['ancestor_name']) ? ' (Ancestor: ' . sanitize($c['ancestor_name']) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label class="form-label font-weight-bold" for="town_id">Originating Town / Settlement</label>
                        <select class="form-select" id="town_id" name="town_id">
                            <option value="">-- Select Town --</option>
                            <?php foreach ($towns as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($t['id'] == $townVal) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($t['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Select the main settlement/town where this family stool house is located.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="family_head_id">Head of Family</label>
                        <select class="form-select" id="family_head_id" name="family_head_id">
                            <option value="">-- Select Member --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $headVal) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="stool_father_id">Stool Father (Zikpuitor)</label>
                        <select class="form-select" id="stool_father_id" name="stool_father_id">
                            <option value="">-- Select Member --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $stoolFatherVal) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="description">Family Description / Notes</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter custom notes or history for this stool family..."><?php echo sanitize($descVal); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
