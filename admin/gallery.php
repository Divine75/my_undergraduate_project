<?php
// admin/gallery.php - Manage Gallery Photos

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission
require_login(['Administrator', 'Traditional Council Secretary', 'Data Entry Officer']);

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmtImg = $pdo->prepare("SELECT image_path, title FROM gallery WHERE id = ?");
        $stmtImg->execute([$id]);
        $img = $stmtImg->fetch();
        
        if ($img) {
            // Delete file local copy
            $localFile = '../' . $img['image_path'];
            if (file_exists($localFile)) {
                unlink($localFile);
            }
            // Delete DB row
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt->execute([$id]);
            log_audit('gallery_delete', "Deleted gallery photo: {$img['title']} (ID: $id)");
            $success = "Image deleted from gallery.";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete image: " . $e->getMessage();
    }
    $action = 'list';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    
    // File validation
    $image_path = '';
    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo_file']['tmp_name'];
        $fileName = $_FILES['photo_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/photos/';
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_path = 'uploads/photos/' . $newFileName;
            } else {
                $error = 'Failed to write photo file to disk.';
            }
        } else {
            $error = 'Invalid image type. Only JPG, JPEG, PNG and WEBP are supported.';
        }
    } else {
        $error = 'Please select a photo to upload.';
    }
    
    if (empty($title)) {
        $error = 'Photo Title is required.';
    }
    
    if (empty($error) && !empty($image_path)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO gallery (title, description, image_path, category, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $image_path, $category, $_SESSION['user_id']]);
            $newId = $pdo->lastInsertId();
            log_audit('gallery_upload', "Uploaded photo: $title (ID: $newId)");
            $success = "Photo successfully added to public gallery.";
            $action = 'list';
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch photos for listing
$photos = [];
try {
    $photos = $pdo->query("
        SELECT g.*, u.full_name as uploader_name 
        FROM gallery g 
        LEFT JOIN users u ON g.uploaded_by = u.id 
        ORDER BY g.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-images text-warning me-2"></i> Photo Gallery Management</h2>
    <?php if ($action === 'list'): ?>
        <a href="gallery.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Add Photo</a>
    <?php else: ?>
        <a href="gallery.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Gallery</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. GALLERY LISTING -->
<?php if ($action === 'list'): ?>
    <div class="row">
        <?php if (empty($photos)): ?>
            <div class="col-md-12 text-center py-5 text-muted">No images in the gallery database yet.</div>
        <?php else: ?>
            <?php foreach ($photos as $p): 
                $imgUrl = $p['image_path'];
                if (strpos($imgUrl, 'http') === false) {
                    $imgUrl = BASE_URL . $imgUrl;
                }
            ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card border rounded-3 overflow-hidden shadow-sm h-100 bg-white">
                        <div style="height: 180px; position: relative;">
                            <img src="<?php echo $imgUrl; ?>" onerror="this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&q=80&w=300&h=200'" class="w-100 h-100 object-fit-cover" alt="<?php echo sanitize($p['title']); ?>">
                            <span class="badge bg-success position-absolute top-2 start-2" style="font-size: 0.7rem;"><?php echo sanitize($p['category']); ?></span>
                        </div>
                        <div class="card-body p-3">
                            <h6 class="font-weight-bold mb-1" style="color: var(--primary);"><?php echo sanitize($p['title']); ?></h6>
                            <p class="text-muted small mb-2" style="font-size: 0.75rem; min-height: 35px;"><?php echo sanitize($p['description']); ?></p>
                            <small class="text-muted d-block mb-3" style="font-size: 0.7rem;">By: <?php echo sanitize($p['uploader_name'] ?? 'System'); ?></small>
                            <div class="text-end">
                                <a href="gallery.php?action=delete&id=<?php echo $p['id']; ?>" onclick="return confirm('Delete this image from public view?');" class="btn btn-sm btn-outline-danger w-100 py-1">
                                    <i class="fas fa-trash me-1"></i> Delete Image
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<!-- 2. ADD FORM -->
<?php elseif ($action === 'add'): ?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><i class="fas fa-cloud-arrow-up text-warning me-2"></i> Upload Photo to Gallery</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="title">Photo Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="e.g. Festival Durbar Procession">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="category">Category / Album</label>
                        <select class="form-select" id="category" name="category">
                            <option value="Festival">Festival Celebration</option>
                            <option value="Meeting">Council Assembly</option>
                            <option value="Historical">Historical Sites & Monuments</option>
                            <option value="General" selected>General Event</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="description">Caption / Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Provide details about the photo (people shown, location, year)..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="photo_file">Select Photo File <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="photo_file" name="photo_file" required accept="image/png, image/jpeg, image/jpg, image/webp">
                    <small class="text-muted d-block mt-1">Allowed formats: PNG, JPG, JPEG, WEBP. Max size: 5MB.</small>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-upload me-1"></i> Upload Image</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
