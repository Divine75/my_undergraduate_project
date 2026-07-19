<?php
// gallery.php - Public Photo Gallery

require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch all gallery items
$photos = [];
try {
    $stmt = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC");
    $photos = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!-- Banner -->
<section class="hero-section" style="padding: 60px 0;">
    <div class="container">
        <h1>Traditional Photo Gallery</h1>
        <p>A visual archive celebrating the festivals, council durbars, and cultural heritage of Atsiame.</p>
    </div>
</section>

<!-- Gallery Grid -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <?php if (empty($photos)): ?>
                <div class="col-md-12 text-center p-5 border rounded bg-white">
                    <p class="text-muted m-0">No photographs have been archived in the gallery yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($photos as $photo): 
                    // Fallback to stock image if file does not exist
                    $imgUrl = $photo['image_path'];
                    // If it is local file, prepend base URL. But in seeding we set it directly as uploads/photos/...
                    // We can check if it starts with http or not
                    if (strpos($imgUrl, 'http') === false) {
                        $imgUrl = BASE_URL . $imgUrl;
                    }
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border border-light shadow-sm rounded-3 overflow-hidden h-100">
                            <div class="position-relative overflow-hidden" style="height: 250px;">
                                <img src="<?php echo $imgUrl; ?>" onerror="this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&q=80&w=400&h=300'" class="w-100 h-100 object-fit-cover transition" style="transition: transform 0.5s ease;" alt="<?php echo sanitize($photo['title']); ?>">
                                <div class="badge bg-success position-absolute top-3 start-3 shadow" style="font-size: 0.75rem; text-transform: uppercase;"><?php echo sanitize($photo['category']); ?></div>
                            </div>
                            <div class="card-body p-3">
                                <h5 class="card-title font-weight-bold mb-1" style="color: var(--primary);"><?php echo sanitize($photo['title']); ?></h5>
                                <p class="card-text text-muted mb-2" style="font-size: 0.85rem;"><?php echo sanitize($photo['description']); ?></p>
                                <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="far fa-calendar-alt me-1 text-warning"></i> Uploaded: <?php echo date('F j, Y', strtotime($photo['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Custom Styling for Hover Effect -->
<style>
.card:hover img {
    transform: scale(1.08);
}
</style>

<?php
require_once 'includes/footer.php';
?>
