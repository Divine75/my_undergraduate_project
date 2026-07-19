<?php
// admin/member-details.php - Member Profile, ID Card & Genealogy Chart

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch member details
$stmt = $pdo->prepare("
    SELECT fm.*, c.name as clan_name, f.name as family_name 
    FROM family_members fm
    LEFT JOIN clans c ON fm.clan_id = c.id
    LEFT JOIN families f ON fm.family_id = f.id
    WHERE fm.id = ?
");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    echo "<div class='alert alert-danger'>Member profile not found in registry.</div>";
    require_once '../includes/admin-footer.php';
    exit;
}

// Fetch family tree nodes (relational data)
$genealogyNodes = get_genealogy_tree_nodes($id);

// Format names
$fullName = sanitize($member['first_name'] . ' ' . $member['last_name']);
$otherNames = $member['other_names'] ? sanitize($member['other_names']) : '';
$dob = new DateTime($member['date_of_birth']);
$diff = $dob->diff(new DateTime($member['date_of_death'] ?? 'now'));
$age = $diff->y;
$title = get_member_title($member['id']);

$photoUrl = $member['photo'] ? BASE_URL . $member['photo'] : '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-id-card text-warning me-2"></i> Profile Directory</h2>
    <div>
        <a href="members.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-1"></i> Registry</a>
        <button onclick="window.print();" class="btn btn-royal"><i class="fas fa-print me-1"></i> Print ID Card</button>
    </div>
</div>

<div class="row">
    <!-- Profile & Printable Card -->
    <div class="col-lg-4 mb-4 d-print-block">
        <!-- Printable Membership Card -->
        <div class="card border shadow-sm mb-4 bg-white rounded-3 d-print-none">
            <div class="card-header bg-success text-white font-weight-bold">
                <h6 class="m-0"><i class="fas fa-address-card text-warning me-2"></i> Traditional Membership Card</h6>
            </div>
            <div class="card-body p-3 bg-light">
                <div class="id-card-container">
                    <div class="traditional-id-card">
                        <div class="id-header">
                            <div class="id-logo">A</div>
                            <div class="id-title-text">
                                <h5>ATAMIS REGISTRY</h5>
                                <p>Atsiame Traditional Area</p>
                            </div>
                        </div>
                        <div class="id-body">
                            <div class="id-photo-box">
                                <img src="<?php echo $photoUrl; ?>" onerror="this.src='https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=100&h=100'" alt="ID Photo">
                            </div>
                            <div class="id-details">
                                <table>
                                    <tr>
                                        <td class="id-label">Name:</td>
                                        <td class="id-value"><?php echo $fullName; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="id-label">Office:</td>
                                        <td class="id-value"><?php echo $title ? $title : 'Citizen'; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="id-label">Clan:</td>
                                        <td class="id-value"><?php echo $member['clan_name'] ? sanitize($member['clan_name']) : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="id-label">Stool ID:</td>
                                        <td class="id-value">ATS-<?php echo str_pad($member['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="id-footer">
                            <span>Status: <?php echo strtoupper(sanitize($member['status'])); ?></span>
                            <!-- Mock QR Code representing profile URL -->
                            <div class="id-qr text-dark d-flex justify-content-center align-items-center" style="font-size: 0.45rem; font-weight: 700;">QR</div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-2 text-muted" style="font-size: 0.8rem;">
                    <i class="fas fa-info-circle me-1 text-success"></i> Fits on standard card printing layout.
                </div>
            </div>
        </div>

        <!-- Profile details -->
        <div class="card border shadow-sm bg-white rounded-3">
            <div class="card-body p-4 text-center">
                <img src="<?php echo $photoUrl; ?>" onerror="this.src='https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=150&h=150'" class="rounded-circle border mb-3" style="width: 130px; height: 130px; object-fit: cover; border: 4px solid var(--accent) !important;" alt="<?php echo $fullName; ?>">
                <h4 class="mb-1 font-weight-bold" style="color: var(--primary);"><?php echo $fullName; ?></h4>
                <?php if ($otherNames): ?>
                    <p class="text-muted small mb-2"><?php echo $otherNames; ?></p>
                <?php endif; ?>
                
                <span class="badge bg-<?php echo ($member['status'] == 'Alive') ? 'success' : 'secondary'; ?> mb-3 px-3 py-2 rounded-pill">
                    <?php echo sanitize($member['status']); ?>
                </span>
                
                <hr class="border-secondary opacity-25">
                
                <div class="text-start">
                    <table class="table table-borderless table-sm mb-0 small">
                        <tr>
                            <td class="font-weight-bold text-success" style="width: 110px;">Stool ID:</td>
                            <td>ATS-<?php echo str_pad($member['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-success">Gender:</td>
                            <td><?php echo sanitize($member['gender']); ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-success">Date of Birth:</td>
                            <td><?php echo date('M d, Y', strtotime($member['date_of_birth'])); ?></td>
                        </tr>
                        <?php if ($member['status'] === 'Deceased'): ?>
                            <tr>
                                <td class="font-weight-bold text-danger">Date of Death:</td>
                                <td><?php echo date('M d, Y', strtotime($member['date_of_death'])); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="font-weight-bold text-success">Age:</td>
                            <td><?php echo $age; ?> years</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-success">Clan:</td>
                            <td><?php echo $member['clan_name'] ? sanitize($member['clan_name']) : '<em class="text-muted">Not Set</em>'; ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-success">Family:</td>
                            <td><?php echo $member['family_name'] ? sanitize($member['family_name']) : '<em class="text-muted">Not Set</em>'; ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-success">Phone:</td>
                            <td><?php echo $member['phone'] ? sanitize($member['phone']) : '<em class="text-muted">None</em>'; ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-success">Email:</td>
                            <td><?php echo $member['email'] ? sanitize($member['email']) : '<em class="text-muted">None</em>'; ?></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-success">Address:</td>
                            <td><?php echo $member['address'] ? sanitize($member['address']) : '<em class="text-muted">None</em>'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Biography & Interactive Genealogy Tree -->
    <div class="col-lg-8 mb-4 d-print-none">
        <!-- Bio Card -->
        <div class="card border shadow-sm mb-4 bg-white rounded-3">
            <div class="card-header bg-success text-white py-3">
                <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><i class="fas fa-book-open text-warning me-2"></i> Traditional Biography & Stool Achievements</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($member['bio'])): ?>
                    <p style="text-align: justify; line-height: 1.7;"><?php echo nl2br(sanitize($member['bio'])); ?></p>
                <?php else: ?>
                    <p class="text-muted m-0">No biographical files or records have been uploaded for this member.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Interactive Genealogy SVG Tree -->
        <div class="card border shadow-sm bg-white rounded-3">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><i class="fas fa-sitemap text-warning me-2"></i> Paternal & Maternal Lineage Chart (Interactive)</h5>
                <span class="badge bg-warning text-dark font-weight-bold">SVG Live Visualizer</span>
            </div>
            <div class="card-body p-3 bg-light text-center">
                <!-- SVG Canvas Container -->
                <div class="svg-container border rounded bg-white overflow-auto shadow-inner" style="height: 480px; position: relative;">
                    <svg id="genealogySvg" width="800" height="450" style="min-width: 800px; display: block; margin: 0 auto;"></svg>
                </div>
                <div class="text-muted mt-2 small">
                    <i class="fas fa-info-circle text-success me-1"></i> Traces Grandparents, Parents, Spouses, and Children automatically based on database links.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inject dynamic JSON data for JS renderer -->
<script>
    const ROOT_MEMBER_ID = <?php echo $id; ?>;
    const GENEALOGY_NODES = <?php echo json_encode($genealogyNodes); ?>;
</script>
<!-- SVG Genealogy drawing script -->
<script src="<?php echo BASE_URL; ?>assets/js/genealogy.js"></script>

<?php
require_once '../includes/admin-footer.php';
?>
