<?php
// towns.php - Atsiame Dukor Towns Directory

require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch all towns from the database
$towns = [];
try {
    $stmt = $pdo->query("SELECT * FROM towns ORDER BY name ASC");
    $towns = $stmt->fetchAll();
} catch (PDOException $e) {}

// Admin Role Flags
$isAdmin = is_logged_in() && in_array($_SESSION['user_role'] ?? '', ['Administrator', 'Traditional Council Secretary', 'Data Entry Officer']);
$isFullAdmin = is_logged_in() && ($_SESSION['user_role'] ?? '') === 'Administrator';

// Fetch unique livelihoods for filter
$allLivelihoods = [];
foreach ($towns as $t) {
    if (!empty($t['livelihood'])) {
        $parts = explode(',', $t['livelihood']);
        foreach ($parts as $p) {
            $trimmed = trim($p);
            if (!in_array($trimmed, $allLivelihoods) && $trimmed !== '') {
                $allLivelihoods[] = $trimmed;
            }
        }
    }
}
sort($allLivelihoods);
?>

<!-- Premium Banner Section -->
<section class="hero-section" style="padding: 60px 0;">
    <div class="container">
        <h1 class="animate__animated animate__fadeInDown">Atsiame Dukor Towns</h1>
        <p class="lead animate__animated animate__fadeInUp">Explore the historic settlements, divisional stools, and local economies that define the Atsiame Paramouncy.</p>
    </div>
</section>

<div class="container py-5">
    <div class="row">
        <!-- Interactive SVG Map / Visualizer (Left Column) -->
        <div class="col-lg-5 mb-5">
            <div class="card border border-light shadow-sm h-100" style="border-radius: 14px; overflow: hidden;">
                <div class="card-header text-white d-flex align-items-center justify-content-between py-3" style="background-color: var(--primary); border-bottom: 2px solid var(--accent);">
                    <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><i class="fas fa-map-location-dot text-warning me-2"></i> Interactive Dukor Map</h5>
                    <span class="badge bg-warning text-dark px-3 py-2" style="font-size: 0.75rem; font-weight: 600;">Interactive Canvas</span>
                </div>
                <div class="card-body bg-dark text-white p-4 d-flex align-items-center justify-content-center position-relative" style="min-height: 400px; background: linear-gradient(135deg, #071627 0%, #112d4e 100%);">
                    
                    <!-- Decorative Kente Canvas Grid -->
                    <div style="position: absolute; top:0; left:0; right:0; bottom:0; opacity: 0.03; background: url('assets/images/card_pattern.png') repeat;"></div>
                    
                    <svg viewBox="0 0 800 500" class="w-100 h-auto" style="z-index: 2;">
                        <!-- Styled abstract boundary of Atsiame Dukor -->
                        <path d="M 150,200 C 180,80 350,50 480,80 C 620,110 700,200 680,320 C 650,420 480,480 320,450 C 180,420 120,320 150,200 Z" 
                              fill="rgba(212, 175, 55, 0.05)" 
                              stroke="var(--accent)" 
                              stroke-width="3" 
                              stroke-dasharray="2,2" />
                        
                        <text x="250" y="70" fill="rgba(255,255,255,0.2)" font-size="28" font-family="'Playfair Display', serif" letter-spacing="3">ATSIAME DUKOR</text>
                        <text x="250" y="440" fill="rgba(255,255,255,0.15)" font-size="12" font-family="'Outfit', sans-serif" letter-spacing="1">VOLTA REGION - GHANA</text>
                        
                        <!-- Map Connections -->
                        <line x1="400" y1="200" x2="200" y2="150" stroke="rgba(212, 175, 55, 0.3)" stroke-width="1.5" stroke-dasharray="4,4" />
                        <line x1="400" y1="200" x2="550" y2="280" stroke="rgba(212, 175, 55, 0.3)" stroke-width="1.5" stroke-dasharray="4,4" />
                        <line x1="400" y1="200" x2="280" y2="350" stroke="rgba(212, 175, 55, 0.3)" stroke-width="1.5" stroke-dasharray="4,4" />
                        <line x1="400" y1="200" x2="480" y2="120" stroke="rgba(212, 175, 55, 0.3)" stroke-width="1.5" stroke-dasharray="4,4" />
                        
                        <!-- Hotspots -->
                        <?php foreach ($towns as $t): 
                            if (empty($t['coordinates'])) continue;
                            list($x, $y) = explode(',', $t['coordinates']);
                        ?>
                            <g class="map-node" data-id="<?php echo $t['id']; ?>" style="cursor: pointer;">
                                <!-- Pulsing Outer Glow -->
                                <circle cx="<?php echo $x; ?>" cy="<?php echo $y; ?>" r="14" fill="rgba(212, 175, 55, 0.2)" class="pulse-ring" />
                                <!-- Core Dot -->
                                <circle cx="<?php echo $x; ?>" cy="<?php echo $y; ?>" r="6" fill="var(--accent)" stroke="#ffffff" stroke-width="1.5" id="node-dot-<?php echo $t['id']; ?>" />
                                <!-- Label -->
                                <text x="<?php echo $x; ?>" y="<?php echo $y - 12; ?>" fill="#ffffff" font-size="12" font-family="'Outfit', sans-serif" font-weight="600" text-anchor="middle" class="map-label">
                                    <?php echo sanitize($t['name']); ?>
                                </text>
                            </g>
                        <?php endforeach; ?>
                    </svg>
                    
                    <style>
                        .pulse-ring {
                            transform-origin: center;
                            animation: pulse 2s infinite;
                        }
                        @keyframes pulse {
                            0% { transform: scale(0.8); opacity: 0.8; }
                            50% { transform: scale(1.3); opacity: 0.3; }
                            100% { transform: scale(0.8); opacity: 0.8; }
                        }
                        .map-node:hover .pulse-ring {
                            fill: rgba(255, 255, 255, 0.4);
                        }
                        .map-node:hover text {
                            fill: var(--accent) !important;
                        }
                        .map-node.active .pulse-ring {
                            fill: rgba(255, 255, 255, 0.5);
                            stroke: #ffffff;
                            stroke-width: 1px;
                        }
                        .map-node.active text {
                            fill: var(--accent) !important;
                            font-size: 14px;
                        }
                        .town-card-highlight {
                            border: 2px solid var(--accent) !important;
                            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.2) !important;
                            transform: translateY(-5px);
                        }
                    </style>
                </div>
            </div>
        </div>

        <!-- Filter, Sort, and Town Profiles Column (Right Column) -->
        <div class="col-lg-7">
            <!-- Filter Tool Controls -->
            <div class="card border border-light shadow-sm p-4 mb-4" style="border-radius: 12px; background-color: #ffffff;">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label font-weight-bold text-dark"><i class="fas fa-search me-1 text-warning"></i> Search Towns</label>
                        <input type="text" id="search-input" class="form-control" placeholder="Search by name, landmark...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold text-dark"><i class="fas fa-filter me-1 text-warning"></i> Livelihood</label>
                        <select id="livelihood-filter" class="form-select">
                            <option value="">All Livelihoods</option>
                            <?php foreach ($allLivelihoods as $l): ?>
                                <option value="<?php echo sanitize($l); ?>"><?php echo sanitize($l); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold text-dark"><i class="fas fa-arrow-down-a-z me-1 text-warning"></i> Sort By</label>
                        <select id="sort-filter" class="form-select">
                            <option value="name-asc">Name (A-Z)</option>
                            <option value="name-desc">Name (Z-A)</option>
                            <option value="pop-desc">Population (High-Low)</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <?php if ($isAdmin): ?>
                <div class="mb-4 text-end">
                    <a href="admin/towns.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Add New Town (Admin Panel)</a>
                </div>
            <?php endif; ?>

            <!-- Town Listing Grid -->
            <div id="towns-container" class="row">
                <?php if (empty($towns)): ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fas fa-city fa-3x mb-3 text-warning"></i>
                        <h5>No towns registered in the system yet.</h5>
                    </div>
                <?php else: ?>
                    <?php foreach ($towns as $t): 
                        $lBadgeList = !empty($t['livelihood']) ? explode(',', $t['livelihood']) : [];
                        $chiefName = 'Vacant / Regent Council';
                        if (!empty($t['chief_id'])) {
                            $cName = get_member_name($t['chief_id']);
                            $cTitle = get_member_title($t['chief_id']);
                            $chiefName = ($cTitle ? $cTitle . ' ' : '') . $cName;
                        }
                    ?>
                        <div class="col-12 mb-4 town-item" 
                             data-id="<?php echo $t['id']; ?>"
                             data-name="<?php echo strtolower($t['name']); ?>"
                             data-livelihoods="<?php echo strtolower($t['livelihood']); ?>"
                             data-landmark="<?php echo strtolower($t['landmark']); ?>"
                             data-pop="<?php echo $t['population']; ?>">
                            
                            <div class="card h-100 border border-light shadow-sm card-royal" id="town-card-<?php echo $t['id']; ?>" style="border-radius: 12px; transition: all 0.3s ease;">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h3 class="mb-1 text-success font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo sanitize($t['name']); ?></h3>
                                            <span class="badge bg-light text-dark border"><i class="fas fa-monument text-warning me-1"></i> <?php echo sanitize($t['stool_name'] ? $t['stool_name'] : 'No Divisional Stool'); ?></span>
                                        </div>
                                        <span class="badge bg-royal text-white px-3 py-2" style="font-size: 0.85rem;"><i class="fas fa-users me-1"></i> Pop: <?php echo number_format($t['population']); ?></span>
                                    </div>
                                    
                                    <p class="text-dark" style="text-align: justify; font-size: 0.95rem; line-height: 1.5;">
                                        <?php echo sanitize($t['description']); ?>
                                    </p>
                                    
                                    <div class="row mt-3 g-2">
                                        <div class="col-sm-6">
                                            <small class="d-block text-muted"><strong><i class="fas fa-crown text-warning me-1"></i> Ruling Dufiga/Chief:</strong></small>
                                            <span class="text-dark"><?php echo sanitize($chiefName); ?></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <small class="d-block text-muted"><strong><i class="fas fa-map-pin text-warning me-1"></i> Key Landmark:</strong></small>
                                            <span class="text-dark"><?php echo sanitize($t['landmark'] ? $t['landmark'] : 'N/A'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($lBadgeList)): ?>
                                        <div class="mt-3 pt-3 border-top">
                                            <small class="d-block text-muted mb-2"><strong>Economic Livelihoods:</strong></small>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($lBadgeList as $lbl): ?>
                                                    <span class="badge bg-success-light text-success border-success-subtle" style="font-size: 0.75rem; background-color: rgba(15, 48, 87, 0.05); color: var(--primary) !important; font-weight: 500;"><?php echo sanitize(trim($lbl)); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($isAdmin): ?>
                                        <div class="mt-3 pt-3 border-top d-flex justify-content-end gap-2">
                                            <a href="admin/towns.php?action=edit&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i> Edit Town</a>
                                            <?php if ($isFullAdmin): ?>
                                                <a href="admin/towns.php?action=delete&id=<?php echo $t['id']; ?>" onclick="return confirm('Are you sure you want to delete this town?');" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i> Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Interactive JS Logic linking SVG Map and cards -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("search-input");
    const livelihoodFilter = document.getElementById("livelihood-filter");
    const sortFilter = document.getElementById("sort-filter");
    const townsContainer = document.getElementById("towns-container");
    const townItems = Array.from(document.querySelectorAll(".town-item"));
    const mapNodes = document.querySelectorAll(".map-node");

    // 1. Interactive map clicks
    mapNodes.forEach(node => {
        node.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            highlightTown(id, true);
        });
    });

    // 2. Card highlighting when hovered or clicked
    townItems.forEach(item => {
        item.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            highlightTown(id, false);
        });
    });

    function highlightTown(id, scroll) {
        // Reset all map nodes
        mapNodes.forEach(n => n.classList.remove("active"));
        // Reset all cards
        townItems.forEach(card => {
            const innerCard = card.querySelector(".card");
            innerCard.classList.remove("town-card-highlight");
        });

        // Set active map node
        const activeNode = document.querySelector(`.map-node[data-id="${id}"]`);
        if (activeNode) activeNode.classList.add("active");

        // Set active card
        const activeCard = document.getElementById(`town-card-${id}`);
        if (activeCard) {
            activeCard.classList.add("town-card-highlight");
            if (scroll) {
                activeCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    // 3. Search and Filtering functions
    function filterTowns() {
        const query = searchInput.value.toLowerCase().trim();
        const livelihood = livelihoodFilter.value.toLowerCase();
        
        townItems.forEach(item => {
            const name = item.getAttribute("data-name");
            const livelihoods = item.getAttribute("data-livelihoods");
            const landmark = item.getAttribute("data-landmark");
            
            const matchesQuery = name.includes(query) || landmark.includes(query);
            const matchesLivelihood = livelihood === "" || livelihoods.includes(livelihood);
            
            if (matchesQuery && matchesLivelihood) {
                item.style.display = "block";
                // Show corresponding map node
                const id = item.getAttribute("data-id");
                const mapNode = document.querySelector(`.map-node[data-id="${id}"]`);
                if (mapNode) mapNode.style.display = "block";
            } else {
                item.style.display = "none";
                // Hide corresponding map node
                const id = item.getAttribute("data-id");
                const mapNode = document.querySelector(`.map-node[data-id="${id}"]`);
                if (mapNode) mapNode.style.display = "none";
            }
        });
        
        sortTowns();
    }

    // 4. Sorting function
    function sortTowns() {
        const val = sortFilter.value;
        const visibleItems = townItems.filter(item => item.style.display !== "none");
        
        visibleItems.sort((a, b) => {
            if (val === "name-asc") {
                return a.getAttribute("data-name").localeCompare(b.getAttribute("data-name"));
            } else if (val === "name-desc") {
                return b.getAttribute("data-name").localeCompare(a.getAttribute("data-name"));
            } else if (val === "pop-desc") {
                return parseInt(b.getAttribute("data-pop")) - parseInt(a.getAttribute("data-pop"));
            }
            return 0;
        });
        
        // Re-append sorted elements
        visibleItems.forEach(item => townsContainer.appendChild(item));
    }

    searchInput.addEventListener("input", filterTowns);
    livelihoodFilter.addEventListener("change", filterTowns);
    sortFilter.addEventListener("change", sortTowns);
    
    // Trigger initial sort
    sortTowns();
});
</script>

<?php
require_once 'includes/footer.php';
?>
