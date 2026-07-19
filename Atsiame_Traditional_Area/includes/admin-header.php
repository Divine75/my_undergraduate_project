<?php
// includes/admin-header.php - Admin Layout Header

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Enforce login for all admin pages
require_login();

$currentPage = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'Viewer';
$user_fullname = $_SESSION['user_fullname'] ?? 'Staff Member';

// Fetch traditional area info for branding
try {
    $stmt = $pdo->query("SELECT name FROM traditional_area LIMIT 1");
    $areaName = $stmt->fetchColumn();
} catch (PDOException $e) {
    $areaName = 'Atsiame Traditional Area';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATAMIS - Administrative Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/admin.css" rel="stylesheet">
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h3>ATAMIS</h3>
            <p>Atsiame Area Admin</p>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/index.php">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'clans.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/clans.php">
                    <i class="fas fa-shield-halved"></i> Great Ancestor / Clans
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'families.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/families.php">
                    <i class="fas fa-house-chimney"></i> Families
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'towns.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/towns.php">
                    <i class="fas fa-city"></i> Dukor/Towns
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'members.php' || $currentPage == 'member-details.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/members.php">
                    <i class="fas fa-users"></i> Member Registry
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'hierarchy.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/hierarchy.php">
                    <i class="fas fa-crown"></i> Leadership Hierarchy
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'succession.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/succession.php">
                    <i class="fas fa-arrow-right-arrow-left"></i> Succession History
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'documents.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/documents.php">
                    <i class="fas fa-file-contract"></i> Documents & Minutes
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'events.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/events.php">
                    <i class="fas fa-calendar-days"></i> Events & Meetings
                </a>
            </li>
            <li class="<?php echo ($currentPage == 'gallery.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/gallery.php">
                    <i class="fas fa-images"></i> Gallery Archives
                </a>
            </li>
            
            <?php if ($user_role === 'Administrator'): ?>
            <li class="<?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/users.php">
                    <i class="fas fa-user-gear"></i> System Users
                </a>
            </li>
            <?php endif; ?>
            
            <li class="<?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>admin/settings.php">
                    <i class="fas fa-gears"></i> General Settings
                </a>
            </li>
            
            <li class="mt-4 border-top border-secondary pt-3">
                <a href="<?php echo BASE_URL; ?>index.php" target="_blank">
                    <i class="fas fa-globe"></i> View Public Site
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URL; ?>logout.php" class="text-warning">
                    <i class="fas fa-right-from-bracket text-warning"></i> Log Out
                </a>
            </li>
        </ul>
    </div>

    <!-- Content -->
    <div class="admin-content">
        <!-- Top Navbar -->
        <div class="admin-navbar">
            <h2><?php echo sanitize($areaName); ?> System</h2>
            <div class="user-badge d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block">
                    <h6 class="m-0 font-weight-bold" style="font-size: 0.9rem;"><?php echo sanitize($user_fullname); ?></h6>
                    <small class="text-muted" style="font-size: 0.75rem;"><?php echo sanitize($user_role); ?></small>
                </div>
                <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 40px; height: 40px; font-weight: 600;">
                    <?php echo strtoupper(substr($user_fullname, 0, 1)); ?>
                </div>
            </div>
        </div>
        
        <div class="admin-body">
