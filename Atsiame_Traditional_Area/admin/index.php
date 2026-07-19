<?php
// admin/index.php - Admin Dashboard Home

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Fetch quick stats
$totalClans = $pdo->query("SELECT COUNT(*) FROM clans")->fetchColumn();
$totalFamilies = $pdo->query("SELECT COUNT(*) FROM families")->fetchColumn();
$totalMembers = $pdo->query("SELECT COUNT(*) FROM family_members")->fetchColumn();
$totalPositions = $pdo->query("SELECT COUNT(*) FROM traditional_positions")->fetchColumn();
$totalDocs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();

// Fetch 5 recent audit logs
$auditLogs = [];
try {
    $stmt = $pdo->query("
        SELECT al.*, u.full_name 
        FROM audit_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 5
    ");
    $auditLogs = $stmt->fetchAll();
} catch (PDOException $e) {}

// Fetch recently added family members
$recentMembers = [];
try {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, gender, status, created_at 
        FROM family_members 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentMembers = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="bg-success text-white p-4 rounded-4 shadow-sm border" style="border-bottom: 4px solid var(--accent) !important;">
            <h1 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;">Welcome to ATAMIS Staff Portal</h1>
            <p class="m-0 mt-1 opacity-75">Traditional Administration portal of the Atsiame Traditional Area. You are logged in as <?php echo sanitize($user_role); ?>.</p>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="row mb-5">
    <div class="col-lg col-md-4 col-sm-6 col-6 mb-4">
        <div class="stat-card">
            <div class="stat-info">
                <h6>Clans</h6>
                <h2><?php echo $totalClans; ?></h2>
            </div>
            <div class="stat-icon"><i class="fas fa-shield-halved"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6 col-6 mb-4">
        <div class="stat-card">
            <div class="stat-info">
                <h6>Families</h6>
                <h2><?php echo $totalFamilies; ?></h2>
            </div>
            <div class="stat-icon"><i class="fas fa-house-chimney"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6 col-6 mb-4">
        <div class="stat-card">
            <div class="stat-info">
                <h6>Members</h6>
                <h2><?php echo $totalMembers; ?></h2>
            </div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-6 col-sm-6 col-6 mb-4">
        <div class="stat-card">
            <div class="stat-info">
                <h6>Positions</h6>
                <h2><?php echo $totalPositions; ?></h2>
            </div>
            <div class="stat-icon"><i class="fas fa-crown"></i></div>
        </div>
    </div>
    <div class="col-lg col-md-6 col-sm-6 col-6 mb-4">
        <div class="stat-card">
            <div class="stat-info">
                <h6>Documents</h6>
                <h2><?php echo $totalDocs; ?></h2>
            </div>
            <div class="stat-icon"><i class="fas fa-file-contract"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 font-weight-bold" style="color: var(--primary); border-bottom: 2px solid var(--accent);">
                <h5 class="m-0"><i class="fas fa-bolt text-warning me-2"></i> Quick Administrative Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <a href="members.php?action=add" class="btn btn-outline-success w-100 py-3 text-center d-block rounded-3 border-2">
                            <i class="fas fa-user-plus fa-2x mb-2 d-block text-warning"></i>
                            Register Member
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="clans.php?action=add" class="btn btn-outline-success w-100 py-3 text-center d-block rounded-3 border-2">
                            <i class="fas fa-shield-halved fa-2x mb-2 d-block text-warning"></i>
                            Add New Clan
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="events.php?action=add" class="btn btn-outline-success w-100 py-3 text-center d-block rounded-3 border-2">
                            <i class="fas fa-calendar-plus fa-2x mb-2 d-block text-warning"></i>
                            Schedule Event
                        </a>
                    </div>
                    <div class="col-6 mb-3">
                        <a href="documents.php?action=add" class="btn btn-outline-success w-100 py-3 text-center d-block rounded-3 border-2">
                            <i class="fas fa-file-arrow-up fa-2x mb-2 d-block text-warning"></i>
                            Upload Document
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Member Registrations -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 font-weight-bold" style="color: var(--primary); border-bottom: 2px solid var(--accent);">
                <h5 class="m-0"><i class="fas fa-users-line text-warning me-2"></i> Recently Registered Members</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentMembers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No members registered yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentMembers as $mem): ?>
                                    <tr>
                                        <td>
                                            <h6 class="m-0 font-weight-bold"><?php echo sanitize($mem['first_name'] . ' ' . $mem['last_name']); ?></h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Added: <?php echo date('M d, Y', strtotime($mem['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo sanitize($mem['gender']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($mem['status'] == 'Alive') ? 'success' : 'secondary'; ?> rounded-pill" style="font-size: 0.7rem;">
                                                <?php echo sanitize($mem['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="member-details.php?id=<?php echo $mem['id']; ?>" class="btn btn-sm btn-royal">
                                                <i class="fas fa-eye"></i> Profile
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
</div>

<div class="row">
    <!-- Audit Trail Logs -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 font-weight-bold" style="color: var(--primary); border-bottom: 2px solid var(--accent);">
                <h5 class="m-0"><i class="fas fa-clock-rotate-left text-warning me-2"></i> Recent Audit Logs (System Activity)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Officer</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($auditLogs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No activity logs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td><small><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                        <td>
                                            <span class="badge bg-success" style="font-size: 0.75rem;">
                                                <?php echo $log['full_name'] ? sanitize($log['full_name']) : 'System'; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo sanitize($log['action']); ?></strong></td>
                                        <td><span style="font-size: 0.9rem;"><?php echo sanitize($log['details']); ?></span></td>
                                        <td><small class="text-muted"><?php echo sanitize($log['ip_address']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/admin-footer.php';
?>
