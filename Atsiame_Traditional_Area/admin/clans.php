<?php
// admin/clans.php - Manage Clans

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
        $stmtName = $pdo->prepare("SELECT name FROM clans WHERE id = ?");
        $stmtName->execute([$id]);
        $clanName = $stmtName->fetchColumn();
        
        if ($clanName) {
            $stmt = $pdo->prepare("DELETE FROM clans WHERE id = ?");
            $stmt->execute([$id]);
            log_audit('clan_delete', "Deleted clan: $clanName (ID: $id)");
            $success = "Clan successfully deleted.";
        }
    } catch (PDOException $e) {
        $error = "Cannot delete clan: It may have families or members linked to it.";
    }
    $action = 'list';
}

// Handle Form Submission for Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $totem = trim($_POST['totem'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $history = trim($_POST['history'] ?? '');
    $ancestor_name = trim($_POST['ancestor_name'] ?? '');
    $clan_head_id = !empty($_POST['clan_head_id']) ? intval($_POST['clan_head_id']) : null;
    $stool_father_id = !empty($_POST['stool_father_id']) ? intval($_POST['stool_father_id']) : null;
    
    if (empty($name) || empty($totem) || empty($ancestor_name)) {
        $error = 'Great Ancestor, Clan Name, and Totem are required.';
    } else {
        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO clans (name, totem, description, history, clan_head_id, stool_father_id, ancestor_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $totem, $description, $history, $clan_head_id, $stool_father_id, $ancestor_name]);
                $newId = $pdo->lastInsertId();
                log_audit('clan_add', "Added clan: $name (ID: $newId)");
                $success = "Great Ancestor / Clan created successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Failed to create record: ' . $e->getMessage();
            }
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            try {
                $stmt = $pdo->prepare("UPDATE clans SET name = ?, totem = ?, description = ?, history = ?, clan_head_id = ?, stool_father_id = ?, ancestor_name = ? WHERE id = ?");
                $stmt->execute([$name, $totem, $description, $history, $clan_head_id, $stool_father_id, $ancestor_name, $id]);
                log_audit('clan_edit', "Edited clan: $name (ID: $id)");
                $success = "Great Ancestor / Clan updated successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Failed to update record: ' . $e->getMessage();
            }
        }
    }
}

// Fetch Clan Details for Editing
$editClan = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clans WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editClan = $stmt->fetch();
    if (!$editClan) {
        $error = 'Clan not found.';
        $action = 'list';
    }
}

// Fetch active members to populate clan head dropdown
$members = [];
try {
    $members = $pdo->query("SELECT id, first_name, last_name FROM family_members WHERE status = 'Alive' ORDER BY first_name ASC")->fetchAll();
} catch (PDOException $e) {}

// Fetch Clans for Listing
$clans = [];
try {
    $clans = $pdo->query("SELECT * FROM clans ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-shield-halved text-warning me-2"></i> Great Ancestor / Clan Registry</h2>
    <?php if ($action === 'list'): ?>
        <a href="clans.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Add Great Ancestor / Clan</a>
    <?php else: ?>
        <a href="clans.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2" role="alert"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2" role="alert"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. CLAN LIST -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Great Ancestor</th>
                    <th>Clan Name</th>
                    <th>Totem</th>
                    <th>Head of Clan</th>
                    <th>Stool Father</th>
                    <th>Stool Families</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clans)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No records registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clans as $clan): 
                        // Count families
                        $fCount = 0;
                        try {
                            $stmtF = $pdo->prepare("SELECT COUNT(*) FROM families WHERE clan_id = ?");
                            $stmtF->execute([$clan['id']]);
                            $fCount = $stmtF->fetchColumn();
                        } catch (PDOException $e) {}
                    ?>
                        <tr>
                            <td><strong><?php echo sanitize($clan['ancestor_name'] ? $clan['ancestor_name'] : 'N/A'); ?></strong></td>
                            <td><strong><?php echo sanitize($clan['name']); ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><?php echo sanitize($clan['totem']); ?></span></td>
                            <td><?php echo $clan['clan_head_id'] ? get_member_name($clan['clan_head_id']) : '<em class="text-muted">Not Assigned</em>'; ?></td>
                            <td><?php echo $clan['stool_father_id'] ? get_member_name($clan['stool_father_id']) : '<em class="text-muted">Not Assigned</em>'; ?></td>
                            <td><span class="badge bg-success"><?php echo $fCount; ?> Families</span></td>
                            <td class="text-end">
                                <a href="clans.php?action=edit&id=<?php echo $clan['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if ($user_role === 'Administrator'): ?>
                                    <a href="clans.php?action=delete&id=<?php echo $clan['id']; ?>" onclick="return confirm('Are you sure you want to delete this clan? All sub-families will be removed.');" class="btn btn-sm btn-outline-danger">
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
    $title = ($action === 'add') ? 'Add New Great Ancestor / Clan' : 'Edit Great Ancestor / Clan: ' . sanitize($editClan['name']);
    $submitText = ($action === 'add') ? 'Save Record' : 'Update Record';
    
    $nameVal = ($action === 'edit') ? $editClan['name'] : '';
    $totemVal = ($action === 'edit') ? $editClan['totem'] : '';
    $descVal = ($action === 'edit') ? $editClan['description'] : '';
    $histVal = ($action === 'edit') ? $editClan['history'] : '';
    $ancestorVal = ($action === 'edit') ? $editClan['ancestor_name'] : '';
    $headVal = ($action === 'edit') ? $editClan['clan_head_id'] : '';
    $stoolFatherVal = ($action === 'edit') ? $editClan['stool_father_id'] : '';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $title; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="ancestor_name">Great Ancestor Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ancestor_name" name="ancestor_name" value="<?php echo sanitize($ancestorVal); ?>" required placeholder="e.g. Torgbui Atsiame">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="name">Clan Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($nameVal); ?>" required placeholder="e.g. Atsiame Clan">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="totem">Totem (Ewe and English) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="totem" name="totem" value="<?php echo sanitize($totemVal); ?>" required placeholder="e.g. Leopard (Lakle)">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="clan_head_id">Head of Clan (Incumbent)</label>
                        <select class="form-select" id="clan_head_id" name="clan_head_id">
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

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="description">General Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Describe the clan traits, roles, and boundaries..."><?php echo sanitize($descVal); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="history">Clan History & Migration Story</label>
                    <textarea class="form-control" id="history" name="history" rows="5" placeholder="Document the migration and historical settlement..."><?php echo sanitize($histVal); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
