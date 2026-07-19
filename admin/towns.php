<?php
// admin/towns.php - Manage Towns of the Traditional Area

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Allowed roles check: only Administrator, Secretary, Data Entry can write
$can_edit = in_array($user_role, ['Administrator', 'Traditional Council Secretary', 'Data Entry Officer']);

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Delete Action
if ($action === 'delete' && isset($_GET['id'])) {
    if ($user_role !== 'Administrator') {
        $error = 'Only administrators can delete towns.';
        $action = 'list';
    } else {
        $id = intval($_GET['id']);
        try {
            // Get name for logs
            $stmt = $pdo->prepare("SELECT name FROM towns WHERE id = ?");
            $stmt->execute([$id]);
            $tName = $stmt->fetchColumn();
            
            if ($tName) {
                $stmtDel = $pdo->prepare("DELETE FROM towns WHERE id = ?");
                $stmtDel->execute([$id]);
                log_audit('town_delete', "Deleted town: $tName (ID: $id)");
                $success = 'Town deleted successfully.';
            }
            $action = 'list';
        } catch (PDOException $e) {
            $error = 'Failed to delete town: ' . $e->getMessage();
            $action = 'list';
        }
    }
}

// Form Submission (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_town'])) {
    if (!$can_edit) {
        $error = 'You do not have permission to modify town registry.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $chief_id = !empty($_POST['chief_id']) ? intval($_POST['chief_id']) : null;
        $stool_name = trim($_POST['stool_name'] ?? '');
        $population = !empty($_POST['population']) ? intval($_POST['population']) : null;
        $livelihood = trim($_POST['livelihood'] ?? '');
        $landmark = trim($_POST['landmark'] ?? '');
        $coordinates = trim($_POST['coordinates'] ?? '400,250');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Town Name is required.';
        } else {
            if ($action === 'add') {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO towns (name, chief_id, stool_name, population, livelihood, landmark, coordinates, description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $chief_id, $stool_name, $population, $livelihood, $landmark, $coordinates, $description]);
                    $newId = $pdo->lastInsertId();
                    log_audit('town_add', "Added town: $name (ID: $newId)");
                    $success = "Town created successfully.";
                    $action = 'list';
                } catch (PDOException $e) {
                    $error = 'Failed to create town: ' . $e->getMessage();
                }
            } elseif ($action === 'edit' && isset($_GET['id'])) {
                $id = intval($_GET['id']);
                try {
                    $stmt = $pdo->prepare("
                        UPDATE towns 
                        SET name = ?, chief_id = ?, stool_name = ?, population = ?, livelihood = ?, landmark = ?, coordinates = ?, description = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $chief_id, $stool_name, $population, $livelihood, $landmark, $coordinates, $description, $id]);
                    log_audit('town_edit', "Edited town: $name (ID: $id)");
                    $success = "Town updated successfully.";
                    $action = 'list';
                } catch (PDOException $e) {
                    $error = 'Failed to update town: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Town for Editing
$editTown = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM towns WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editTown = $stmt->fetch();
    if (!$editTown) {
        $error = 'Town not found.';
        $action = 'list';
    }
}

// Fetch active members for Chief dropdown
$members = [];
try {
    $members = $pdo->query("SELECT id, first_name, last_name FROM family_members WHERE status = 'Alive' ORDER BY first_name ASC")->fetchAll();
} catch (PDOException $e) {}

// Fetch Towns for Listing
$towns = [];
try {
    $towns = $pdo->query("SELECT * FROM towns ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-city text-warning me-2"></i> Dukor/Towns Registry</h2>
    <?php if ($action === 'list'): ?>
        <?php if ($can_edit): ?>
            <a href="towns.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Add New Town</a>
        <?php endif; ?>
    <?php else: ?>
        <a href="towns.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2" role="alert"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2" role="alert"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. TOWNS LIST -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white rounded-3">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Town Name</th>
                    <th>Stool Name</th>
                    <th>Incumbent Chief</th>
                    <th>Population</th>
                    <th>Livelihood Badges</th>
                    <th>Key Landmark</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($towns)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No towns registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($towns as $t): 
                        $chiefName = 'Vacant / Regent';
                        if (!empty($t['chief_id'])) {
                            $cName = get_member_name($t['chief_id']);
                            $cTitle = get_member_title($t['chief_id']);
                            $chiefName = ($cTitle ? $cTitle . ' ' : '') . $cName;
                        }
                        $lList = !empty($t['livelihood']) ? explode(',', $t['livelihood']) : [];
                    ?>
                        <tr>
                            <td><strong><?php echo sanitize($t['name']); ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><?php echo sanitize($t['stool_name'] ? $t['stool_name'] : 'No Stool'); ?></span></td>
                            <td><?php echo sanitize($chiefName); ?></td>
                            <td><span class="badge bg-royal text-white"><?php echo number_format($t['population']); ?></span></td>
                            <td>
                                <?php foreach ($lList as $lbl): ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 0.7rem;"><?php echo sanitize(trim($lbl)); ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td><span class="text-muted"><?php echo sanitize($t['landmark'] ? $t['landmark'] : 'N/A'); ?></span></td>
                            <td class="text-end">
                                <?php if ($can_edit): ?>
                                    <a href="towns.php?action=edit&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php endif; ?>
                                <?php if ($user_role === 'Administrator'): ?>
                                    <a href="towns.php?action=delete&id=<?php echo $t['id']; ?>" onclick="return confirm('Are you sure you want to delete this town?');" class="btn btn-sm btn-outline-danger">
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
<?php elseif (($action === 'add' || $action === 'edit') && $can_edit): 
    $title = ($action === 'add') ? 'Register New Town' : 'Edit Town: ' . sanitize($editTown['name']);
    $submitText = ($action === 'add') ? 'Save Town' : 'Update Town Details';
    
    $nameVal = ($action === 'edit') ? $editTown['name'] : '';
    $chiefVal = ($action === 'edit') ? $editTown['chief_id'] : '';
    $stoolVal = ($action === 'edit') ? $editTown['stool_name'] : '';
    $popVal = ($action === 'edit') ? $editTown['population'] : '';
    $liveVal = ($action === 'edit') ? $editTown['livelihood'] : '';
    $landVal = ($action === 'edit') ? $editTown['landmark'] : '';
    $coordsVal = ($action === 'edit') ? $editTown['coordinates'] : '400,250';
    $descVal = ($action === 'edit') ? $editTown['description'] : '';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $title; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <input type="hidden" name="submit_town" value="1">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="name">Town Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($nameVal); ?>" required placeholder="e.g. Torve">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="chief_id">Incumbent Town Chief (Dufiga)</label>
                        <select class="form-select" id="chief_id" name="chief_id">
                            <option value="">-- Select Member --</option>
                            <?php foreach ($members as $m): 
                                $mName = ($m['title'] ? $m['title'] . ' ' : '') . $m['first_name'] . ' ' . $m['last_name'];
                            ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $chiefVal) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($mName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="stool_name">Stool Name</label>
                        <input type="text" class="form-control" id="stool_name" name="stool_name" value="<?php echo sanitize($stoolVal); ?>" placeholder="e.g. Torve Divisional Stool">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="population">Estimated Population</label>
                        <input type="number" class="form-control" id="population" name="population" value="<?php echo sanitize($popVal); ?>" placeholder="e.g. 2500">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="coordinates">Map Hotspot Coordinates (X,Y) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="coordinates" name="coordinates" value="<?php echo sanitize($coordsVal); ?>" required placeholder="e.g. 200,150">
                        <small class="text-muted">Place relative to SVG Canvas size 800x500.</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="livelihood">Primary Livelihoods (Comma-separated)</label>
                        <input type="text" class="form-control" id="livelihood" name="livelihood" value="<?php echo sanitize($liveVal); ?>" placeholder="e.g. Farming, Kente Weaving, Pottery">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="landmark">Key Landmarks</label>
                        <input type="text" class="form-control" id="landmark" name="landmark" value="<?php echo sanitize($landVal); ?>" placeholder="e.g. Torve Pottery Center">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="description">Town History / Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Document the settlement story and details of this town..."><?php echo sanitize($descVal); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
