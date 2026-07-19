<?php
// admin/documents.php - Manage State Documents & Minutes

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission
require_login(['Administrator', 'Traditional Council Secretary', 'Research Officer']);

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmtFile = $pdo->prepare("SELECT file_path, title FROM documents WHERE id = ?");
        $stmtFile->execute([$id]);
        $doc = $stmtFile->fetch();
        
        if ($doc) {
            // Delete actual file
            $localFile = '../' . $doc['file_path'];
            if (file_exists($localFile)) {
                unlink($localFile);
            }
            // Delete DB record
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            log_audit('document_delete', "Deleted document: {$doc['title']} (ID: $id)");
            $success = "Document successfully deleted.";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete document: " . $e->getMessage();
    }
    $action = 'list';
}

// Handle Form Submission for Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'General';
    
    // File validation
    $file_path = '';
    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['doc_file']['tmp_name'];
        $fileName = $_FILES['doc_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'png', 'jpg'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/documents/';
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $file_path = 'uploads/documents/' . $newFileName;
            } else {
                $error = 'Could not save the uploaded file on the server.';
            }
        } else {
            $error = 'Invalid file type. Only PDF, DOC, DOCX, TXT, PNG and JPG are allowed.';
        }
    } else {
        $error = 'Please select a file to upload.';
    }
    
    if (empty($title)) {
        $error = 'Document Title is required.';
    }
    
    if (empty($error) && !empty($file_path)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO documents (title, description, file_path, category, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $file_path, $category, $_SESSION['user_id']]);
            $newId = $pdo->lastInsertId();
            log_audit('document_upload', "Uploaded document: $title (ID: $newId)");
            $success = "Document uploaded successfully.";
            $action = 'list';
        } catch (PDOException $e) {
            $error = "Database write failed: " . $e->getMessage();
        }
    }
}

// Fetch documents for listing
$documents = [];
try {
    $documents = $pdo->query("
        SELECT d.*, u.full_name as uploader_name 
        FROM documents d 
        LEFT JOIN users u ON d.uploaded_by = u.id 
        ORDER BY d.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-file-contract text-warning me-2"></i> Documents & Minutes Archive</h2>
    <?php if ($action === 'list'): ?>
        <a href="documents.php?action=add" class="btn btn-royal"><i class="fas fa-file-arrow-up me-1"></i> Upload Document</a>
    <?php else: ?>
        <a href="documents.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Archive</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. ARCHIVE LISTING -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white mb-4">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Document Details</th>
                    <th>Category</th>
                    <th>Uploaded By</th>
                    <th>Date Added</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No documents registered in this archive yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documents as $doc): 
                        $ext = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                        $icon = 'fa-file-lines text-secondary';
                        if ($ext === 'pdf') $icon = 'fa-file-pdf text-danger';
                        elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word text-primary';
                        elseif (in_array($ext, ['png', 'jpg', 'jpeg'])) $icon = 'fa-file-image text-success';
                    ?>
                        <tr>
                            <td class="text-center">
                                <i class="far <?php echo $icon; ?> fa-2x"></i>
                            </td>
                            <td>
                                <strong><?php echo sanitize($doc['title']); ?></strong>
                                <small class="text-muted d-block" style="font-size: 0.8rem;"><?php echo sanitize($doc['description']); ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo sanitize($doc['category']); ?></span></td>
                            <td><small class="badge bg-success"><?php echo sanitize($doc['uploader_name'] ?? 'System'); ?></small></td>
                            <td><small><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></small></td>
                            <td class="text-end">
                                <a href="<?php echo BASE_URL . $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-royal me-2">
                                    <i class="fas fa-external-link-alt"></i> View File
                                </a>
                                <a href="documents.php?action=delete&id=<?php echo $doc['id']; ?>" onclick="return confirm('Are you sure you want to delete this document from the archives?');" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- 2. UPLOAD FORM -->
<?php elseif ($action === 'add'): ?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><i class="fas fa-cloud-arrow-up text-warning me-2"></i> Upload Document to Archives</h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="title">Document Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required placeholder="e.g. Traditional Council Minutes - January 2026">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="category">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="Minutes">Minutes of Council Meetings</option>
                            <option value="Legal">Legal & Land Declarations</option>
                            <option value="Historical">Historical Migration Records</option>
                            <option value="Customary">Customary Law Rites</option>
                            <option value="General" selected>General Announcement File</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="description">Short Abstract / Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter key topics discussed or summary of document..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="doc_file">Select Document File <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="doc_file" name="doc_file" required accept=".pdf, .doc, .docx, .txt, .png, .jpg, .jpeg">
                    <small class="text-muted d-block mt-1">Allowed formats: PDF, DOC, DOCX, TXT, PNG, JPG. Max size: 10MB.</small>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-upload me-1"></i> Upload File</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
