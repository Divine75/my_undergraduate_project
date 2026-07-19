<?php
// documents.php - Public Document Archives

require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch all documents
$docs = [];
try {
    $stmt = $pdo->query("SELECT * FROM documents ORDER BY created_at DESC");
    $docs = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!-- Banner -->
<section class="hero-section" style="padding: 60px 0;">
    <div class="container">
        <h1>Historical & Legal Archives</h1>
        <p>A digital library of customary laws, council declarations, historical guidelines, and minutes of public assemblies.</p>
    </div>
</section>

<!-- Documents List -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="table-royal table-responsive border shadow-sm">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Icon</th>
                                <th>Document Title</th>
                                <th>Category</th>
                                <th>Date Archived</th>
                                <th style="width: 150px;" class="text-end">Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($docs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No documents have been archived for public access.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($docs as $doc): ?>
                                    <tr>
                                        <td class="text-center text-success">
                                            <i class="far fa-file-pdf fa-2x text-danger"></i>
                                        </td>
                                        <td>
                                            <h6 class="m-0 font-weight-bold" style="color: var(--primary);"><?php echo sanitize($doc['title']); ?></h6>
                                            <p class="m-0 text-muted" style="font-size: 0.8rem;"><?php echo sanitize($doc['description']); ?></p>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.7rem;"><?php echo sanitize($doc['category']); ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></small>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo BASE_URL . $doc['file_path']; ?>" target="_blank" class="btn btn-royal btn-sm px-3">
                                                <i class="fas fa-download me-1"></i> View/Download
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
