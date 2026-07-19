<?php
// clans.php - Public Clan Directory

require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch all clans
$clans = [];
try {
    $stmt = $pdo->query("SELECT * FROM clans WHERE name LIKE '%Tsiame%' OR name LIKE '%Atsiame%' ORDER BY name ASC");
    $clans = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!-- Banner -->
<section class="hero-section" style="padding: 60px 0;">
    <div class="container">
        <h1>Traditional Clans & Totems</h1>
        <p>Explore the foundational pillars of the Atsiame State, their historical origins, and corresponding stool families.</p>
    </div>
</section>

<!-- Clans List -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <?php if (empty($clans)): ?>
                <div class="col-md-12 text-center">
                    <p class="text-muted">No clans have been registered in the database yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($clans as $clan): 
                    // Fetch families for this clan
                    $families = [];
                    try {
                        $fStmt = $pdo->prepare("SELECT name FROM families WHERE clan_id = ? ORDER BY name ASC");
                        $fStmt->execute([$clan['id']]);
                        $families = $fStmt->fetchAll(PDO::FETCH_COLUMN);
                    } catch (PDOException $e) {}
                    
                    // Fetch head of clan name
                    $headName = $clan['clan_head_id'] ? get_member_name($clan['clan_head_id']) : 'Stool Vacant / Not Assigned';
                    // Fetch stool father name
                    $stoolFatherName = $clan['stool_father_id'] ? get_member_name($clan['stool_father_id']) : 'Stool Vacant / Not Assigned';
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card card-royal h-100">
                            <!-- Totem / Visual Header -->
                            <div class="card-header-img" style="background: linear-gradient(rgba(15,48,87,0.7), rgba(15,48,87,0.9)), url('<?php echo $clan['totem_image'] ? BASE_URL . $clan['totem_image'] : 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?auto=format&fit=crop&q=80&w=200&h=200'; ?>') center/cover;">
                                <div class="clan-totem-tag">
                                    <i class="fas fa-paw me-1"></i> Totem: <?php echo sanitize($clan['totem']); ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h3 class="card-title"><?php echo sanitize($clan['name']); ?></h3>
                                <?php if (!empty($clan['ancestor_name'])): ?>
                                    <div class="mb-2"><span class="badge bg-royal text-white" style="font-size: 0.85rem;"><i class="fas fa-user-friends me-1"></i> Great Ancestor: <?php echo sanitize($clan['ancestor_name']); ?></span></div>
                                <?php endif; ?>
                                <div class="mb-3" style="font-size: 0.9rem;">
                                    <span class="d-block text-muted"><strong><i class="fas fa-crown text-warning me-1"></i> Head of Clan:</strong> <?php echo $headName; ?></span>
                                    <span class="d-block text-muted mt-1"><strong><i class="fas fa-shield-halved text-warning me-1"></i> Stool Father:</strong> <?php echo $stoolFatherName; ?></span>
                                </div>
                                
                                <p class="card-text mb-4" style="font-size: 0.95rem; text-align: justify;">
                                    <?php echo sanitize($clan['description']); ?>
                                </p>
                                
                                <hr class="border-secondary opacity-25">
                                
                                <h6 class="text-success font-weight-bold"><i class="fas fa-house-chimney me-1 text-warning"></i> Stool Families:</h6>
                                <?php if (empty($families)): ?>
                                    <small class="text-muted">No families registered under this clan.</small>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <?php foreach ($families as $fam): ?>
                                            <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.75rem; font-weight: 500;"><?php echo sanitize($fam); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
