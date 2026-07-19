<?php
// admin/settings.php - Manage Council Details and Settings

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission
require_login(['Administrator', 'Traditional Council Secretary']);

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $paramountcy = trim($_POST['paramountcy'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $history = trim($_POST['history'] ?? '');
    $vision = trim($_POST['vision'] ?? '');
    $mission = trim($_POST['mission'] ?? '');
    
    if (empty($name) || empty($paramountcy)) {
        $error = 'Traditional Area Name and Paramouncy Name are required.';
    } else {
        try {
            // Update traditional area details (assumes ID 1 exists)
            $stmt = $pdo->prepare("
                UPDATE traditional_area 
                SET name = ?, paramountcy = ?, location = ?, history = ?, vision = ?, mission = ? 
                WHERE id = 1
            ");
            $stmt->execute([$name, $paramountcy, $location, $history, $vision, $mission]);
            
            log_audit('settings_edit', "Updated traditional area general settings");
            $success = "Traditional Area configuration updated successfully.";
        } catch (PDOException $e) {
            $error = "Failed to update configurations: " . $e->getMessage();
        }
    }
}

// Fetch current configurations
$areaConfig = null;
try {
    $areaConfig = $pdo->query("SELECT * FROM traditional_area WHERE id = 1")->fetch();
} catch (PDOException $e) {}

if (!$areaConfig) {
    $error = "No configurations found. Run setup.php to initialize.";
}
?>

<div class="mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-gears text-warning me-2"></i> Traditional Area Configuration</h2>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<?php if ($areaConfig): ?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><i class="fas fa-sliders text-warning me-2"></i> Edit State Metadata</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="name">Traditional Area Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($areaConfig['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="paramountcy">Paramouncy Designation <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="paramountcy" name="paramountcy" value="<?php echo sanitize($areaConfig['paramountcy']); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="location">Physical Location / Municipality</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?php echo sanitize($areaConfig['location']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="vision">Paramouncy Vision Statement</label>
                    <textarea class="form-control" id="vision" name="vision" rows="3"><?php echo sanitize($areaConfig['vision']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="mission">Paramouncy Mission Statement</label>
                    <textarea class="form-control" id="mission" name="mission" rows="3"><?php echo sanitize($areaConfig['mission']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="history">Official State History</label>
                    <textarea class="form-control" id="history" name="history" rows="8"><?php echo sanitize($areaConfig['history']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> Update Configurations</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
