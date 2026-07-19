<?php
// index.php - Public Homepage

require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch quick stats
try {
    $totalClans = $pdo->query("SELECT COUNT(*) FROM clans")->fetchColumn();
    $totalMembers = $pdo->query("SELECT COUNT(*) FROM family_members WHERE status = 'Alive'")->fetchColumn();
    $totalChiefs = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Active'")->fetchColumn();
    $totalDocs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
} catch (PDOException $e) {
    $totalClans = $totalMembers = $totalChiefs = $totalDocs = 0;
}

// Fetch active leadership
$leaders = [];
try {
    $stmt = $pdo->query("
        SELECT fm.*, tp.title 
        FROM appointments a 
        JOIN family_members fm ON a.member_id = fm.id 
        JOIN traditional_positions tp ON a.position_id = tp.id 
        WHERE a.status = 'Active' AND tp.hierarchy_level <= 2 
        ORDER BY tp.hierarchy_level ASC, fm.id ASC
    ");
    $leaders = $stmt->fetchAll();
} catch (PDOException $e) {}

// Fetch 2 recent events
$recentEvents = [];
try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC LIMIT 2");
    $recentEvents = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!-- Hero Banner -->
<section class="hero-section">
    <div class="container">
        <h1><?php echo __('welcome'); ?></h1>
        <p><?php echo __('subtitle'); ?></p>
        <div class="mt-4">
            <a href="clans.php" class="btn btn-accent me-3"><i class="fas fa-shield-halved me-2"></i> Explore Clans</a>
            <a href="chiefs.php" class="btn btn-outline-light btn-lg px-4 py-2 border-2"><i class="fas fa-crown me-2"></i> Traditional Council</a>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="py-4 bg-white border-bottom shadow-sm">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-3 mb-md-0 border-end">
                <h3 class="text-success font-weight-bold m-0" style="font-family: 'Outfit', sans-serif; font-weight: 700;"><?php echo $totalClans; ?></h3>
                <small class="text-muted text-uppercase">Clans</small>
            </div>
            <div class="col-md-3 col-6 mb-3 mb-md-0 border-end">
                <h3 class="text-success font-weight-bold m-0" style="font-family: 'Outfit', sans-serif; font-weight: 700;"><?php echo $totalMembers; ?></h3>
                <small class="text-muted text-uppercase">Registered Members</small>
            </div>
            <div class="col-md-3 col-6 border-end">
                <h3 class="text-success font-weight-bold m-0" style="font-family: 'Outfit', sans-serif; font-weight: 700;"><?php echo $totalChiefs; ?></h3>
                <small class="text-muted text-uppercase">Stool Leadership</small>
            </div>
            <div class="col-md-3 col-6">
                <h3 class="text-success font-weight-bold m-0" style="font-family: 'Outfit', sans-serif; font-weight: 700;"><?php echo $totalDocs; ?></h3>
                <small class="text-muted text-uppercase">Archived Files</small>
            </div>
        </div>
    </div>
</section>

<!-- Message from the Paramouncy -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0 text-center">
                <div class="position-relative d-inline-block">
                    <img src="<?php echo BASE_URL; ?>assets/images/paramount_chief.jpg" onerror="this.src='https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=300&h=300'" class="img-fluid rounded-4 shadow-lg border" style="width: 320px; height: 380px; object-fit: cover;" alt="Torgbui Katsriku II">
                    <div class="bg-success text-white px-3 py-2 rounded shadow position-absolute bottom-0 start-50 translate-middle-x" style="width: 90%; border-bottom: 3px solid var(--accent);">
                        <h6 class="m-0">Torgbui Katsriku II</h6>
                        <small class="text-warning text-uppercase" style="font-size: 0.75rem;">Paramount Chief of Atsiame</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <h2 class="text-success mb-3 royal-title">Message from the Paramount Stool</h2>
                <p class="lead">"A nation that does not know its history and lineage is like a tree without roots."</p>
                <p>Welcome to the Atsiame Traditional Area Management Information System (ATAMIS). As custodians of our land, customs, and ancestral inheritance, it is our responsibility to pass down an accurate, unblemished record of our genealogy, leadership successions, and historical archives.</p>
                <p>Through this digital registry, we are cataloging our clans, celebrating our family structures, and ensuring that stool inheritance disputes are prevented through crystal-clear documentation. We invite all sons and daughters of Atsiame, both at home and in the diaspora, to ensure their family histories are recorded here.</p>
            </div>
        </div>
    </div>
</section>

<!-- History Section -->
<section class="py-5 bg-light border-top border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="text-success royal-title">Brief History of Atsiame</h2>
            <div class="mx-auto bg-warning" style="width: 80px; height: 3px;"></div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border-top border-warning border-3">
                    <h4>The Migration from Hogbe</h4>
                    <p>The history of Atsiame is deeply intertwined with the broader migration of the Ewe people from Notsie (Hogbe) in present-day Togo during the 17th century. Fleeing the tyrannical rule of King Agorkoli, our ancestors migrated under the guidance of royal stool elders, carrying their sacred stools and custom rites.</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border-top border-warning border-3">
                    <h4>Establishment and Settlement</h4>
                    <p>After crossing the Volta, the ancestors settled in the fertile lands of the Akatsi South Municipality. They established several clans (such as Adzovia, Like, and Tovi) and structured their local governance around the Paramouncy. Today, Atsiame stands as a key traditional state of the Anlo nation, rich in agricultural yields and traditional values.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Council Leadership -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="text-success royal-title">Traditional Stool Leadership</h2>
            <p class="text-muted">The core hierarchy administering the Atsiame Traditional State</p>
            <div class="mx-auto bg-warning" style="width: 80px; height: 3px;"></div>
        </div>
        
        <div class="row justify-content-center">
            <?php if (empty($leaders)): ?>
                <div class="col-md-6 text-center">
                    <p class="text-muted">No active council members found in records.</p>
                </div>
            <?php else: ?>
                <?php foreach ($leaders as $leader): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="chief-node">
                            <img src="<?php echo $leader['photo'] ? BASE_URL . $leader['photo'] : 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150&h=150'; ?>" class="chief-photo" alt="<?php echo sanitize($leader['first_name']); ?>">
                            <div class="chief-title text-uppercase font-weight-bold" style="font-size: 0.8rem; letter-spacing: 0.5px;"><?php echo sanitize($leader['title']); ?></div>
                            <div class="chief-name"><?php echo sanitize($leader['first_name'] . ' ' . $leader['last_name']); ?></div>
                            <small class="text-muted"><?php echo get_clan_name($leader['clan_id']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Events and Calendar -->
<section class="py-5 bg-light border-top">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h2 class="text-success mb-4 royal-title">Council Calendar & Festivals</h2>
                <?php if (empty($recentEvents)): ?>
                    <p class="text-muted">No upcoming events scheduled at this moment.</p>
                <?php else: ?>
                    <?php foreach ($recentEvents as $event): 
                        $eventTime = strtotime($event['event_date']);
                        $day = date('d', $eventTime);
                        $month = date('M', $eventTime);
                    ?>
                        <div class="event-item">
                            <div class="event-date-box">
                                <span class="day"><?php echo $day; ?></span>
                                <span class="month"><?php echo $month; ?></span>
                            </div>
                            <div>
                                <span class="badge bg-warning text-dark mb-2"><?php echo sanitize($event['category']); ?></span>
                                <h5 class="m-0 font-weight-bold" style="color: var(--primary);"><?php echo sanitize($event['title']); ?></h5>
                                <small class="text-muted d-block mb-2"><i class="fas fa-location-dot me-1"></i> <?php echo sanitize($event['location']); ?></small>
                                <p class="m-0 text-muted" style="font-size: 0.9rem;"><?php echo sanitize(substr($event['description'], 0, 120)) . '...'; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a href="events.php" class="btn btn-royal btn-sm mt-3">View All Events</a>
            </div>
            
            <div class="col-lg-6">
                <h2 class="text-success mb-4 royal-title">Traditions & Governance</h2>
                <div class="accordion accordion-flush rounded-3 border overflow-hidden shadow-sm" id="accordionHistory">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne">
                                The Clan Structure
                            </button>
                        </h2>
                        <div id="flush-collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionHistory">
                            <div class="accordion-body">Atsiame is organized into various clans, each descended from a single common ancestor. Every clan has its own sacred totem, boundaries of settlement, and role within the paramount traditional administration (e.g., stools of war, Left-Wing or Right-Wing defense).</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo">
                                Chieftaincy succession rules
                            </button>
                        </h2>
                        <div id="flush-collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionHistory">
                            <div class="accordion-body">Succession to any stool in Atsiame follows strict paternal lineage. Upon the destoolment or passing of a chief, the royal stool family schedules a council of elders to review claimants, present the nominee to the Kingmaker (Zikpuitor), and execute customary confinement and installation.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree">
                                The Yam Festival (Te Za)
                            </button>
                        </h2>
                        <div id="flush-collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionHistory">
                            <div class="accordion-body">Celebrated annually in September, the Te Za is a time of cleansing, harvest, and family reconciliation. It brings together descendants from all over the world to planning developments and projects in Atsiame.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
