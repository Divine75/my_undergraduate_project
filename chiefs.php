<?php
// chiefs.php - Public Chieftaincy Hierarchy

require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch all positions and their current active appointments
$positions = [];
try {
    $stmt = $pdo->query("
        SELECT tp.*, 
               fm.first_name, fm.last_name, fm.other_names, fm.photo, fm.id as member_id,
               a.start_date, a.installation_details
        FROM traditional_positions tp
        LEFT JOIN appointments a ON tp.id = a.position_id AND a.status = 'Active'
        LEFT JOIN family_members fm ON a.member_id = fm.id
        ORDER BY tp.hierarchy_level ASC, tp.id ASC
    ");
    $positions = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!-- Banner -->
<section class="hero-section" style="padding: 60px 0;">
    <div class="container">
        <h1>Traditional Leadership Hierarchy</h1>
        <p>The structural order and incumbent leaders of the royal stools governing the Atsiame State.</p>
    </div>
</section>

<!-- Hierarchy Flow -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="text-success royal-title">The Atsiame Royal Stools</h2>
            <div class="mx-auto bg-warning" style="width: 80px; height: 3px;"></div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="timeline-hierarchy">
                    <?php if (empty($positions)): ?>
                        <div class="text-center text-muted">
                            <p>No traditional positions or appointments recorded in database.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($positions as $pos): 
                            $hasLeader = !empty($pos['member_id']);
                            $name = $hasLeader ? sanitize($pos['first_name'] . ' ' . $pos['last_name']) : 'Stool Vacant / Regent Rule';
                            $details = $hasLeader ? sanitize($pos['installation_details']) : 'No active installation details on record.';
                            $photo = $hasLeader && $pos['photo'] ? BASE_URL . $pos['photo'] : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=100&h=100';
                            
                            // Determine rank level styling
                            $borderCol = 'border-warning';
                            if ($pos['hierarchy_level'] == 1) {
                                $borderCol = 'border-danger border-4'; // Paramount
                            } elseif ($pos['hierarchy_level'] == 2) {
                                $borderCol = 'border-warning border-3'; // Divisional / Wing
                            }
                        ?>
                            <div class="card mb-4 shadow-sm border-start <?php echo $borderCol; ?>" style="border-radius: 0 12px 12px 0;">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                                            <img src="<?php echo $photo; ?>" class="rounded-circle border" style="width: 80px; height: 80px; object-fit: cover; border: 3px solid var(--accent) !important;" alt="<?php echo $name; ?>">
                                        </div>
                                        <div class="col-md-7">
                                            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                                <span class="badge bg-success text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                                    <?php 
                                                    $levelLabels = [
                                                        1 => 'Level 1: Paramount Paramouncy',
                                                        2 => 'Level 2: Divisional/Wing Chiefs',
                                                        3 => 'Level 3: Sub-Chief/Linguist',
                                                        4 => 'Level 4: Family Heads/Elders',
                                                        5 => 'Level 5: Principal & Notable Elders',
                                                        6 => 'Level 6: Youth Leaders'
                                                    ];
                                                    echo sanitize($levelLabels[$pos['hierarchy_level']] ?? 'Level ' . $pos['hierarchy_level']);
                                                    ?>
                                                </span>
                                                <?php if ($hasLeader): ?>
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.75rem;"><i class="fas fa-calendar-check me-1"></i> Since <?php echo date('M Y', strtotime($pos['start_date'])); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary text-white" style="font-size: 0.75rem;">Vacant / Pending</span>
                                                <?php endif; ?>
                                            </div>
                                            <h4 class="m-0 font-weight-bold" style="color: var(--primary);"><?php echo sanitize($pos['title']); ?></h4>
                                            <h5 class="text-dark font-weight-light mt-1" style="font-family: 'Outfit', sans-serif; font-size: 1.1rem;"><?php echo $name; ?></h5>
                                            <p class="text-muted m-0 mt-2" style="font-size: 0.85rem; text-align: justify;"><?php echo sanitize($pos['description']); ?></p>
                                        </div>
                                        <div class="col-md-3 border-start ps-4 d-none d-md-block">
                                            <small class="text-muted text-uppercase d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Installation Details</small>
                                            <p class="m-0 text-muted" style="font-size: 0.8rem; font-style: italic; line-height: 1.4;">
                                                <?php echo $details; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
