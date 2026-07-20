<?php
// includes/header.php - Public Header Layout

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Language Setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'ee' ? 'ee' : 'en';
    $_SESSION['lang'] = $lang;
} else {
    $lang = $_SESSION['lang'] ?? 'en';
}

$translations = [
    'en' => [
        'home' => 'Home',
        'clans' => 'Ancestors/Clans',
        'towns' => 'Towns',
        'chiefs' => 'Chieftaincy',
        'events' => 'Events',
        'gallery' => 'Gallery',
        'documents' => 'Archives',
        'login' => 'Log In',
        'logout' => 'Log Out',
        'welcome' => 'Woezor to Atsiame Traditional Area',
        'subtitle' => 'Customary seat of the Katsriku Stool, preserving our history, ancestry, and royal lineage.',
        'view_clans' => 'View Clan Registry',
        'view_history' => 'Read State History',
        'stat_clans' => 'Clans',
        'stat_members' => 'Registered Members',
        'stat_chiefs' => 'Stool Leadership',
        'stat_docs' => 'Archived Files'
    ],
    'ee' => [
        'home' => 'Aƒeme',
        'clans' => 'Togbuiwo/Tsiãwo',
        'towns' => 'Duwo',
        'chiefs' => 'Fiawo',
        'events' => 'Nudzɔdzɔwo',
        'gallery' => 'Nɔnɔmetatawo',
        'documents' => 'Agbalẽwo',
        'login' => 'Ge Đe Eme',
        'logout' => 'Do Go',
        'welcome' => 'Woezor yi Atsiame Mɔ̃ wo ƒe nuto me',
        'subtitle' => 'Katsriku Zikpui Kɔkɔe la ƒe nɔƒe kple dzɔtsoƒe kple fia kɔkɔe ƒe dzɔdzɔme dzadzraɖoƒe.',
        'view_clans' => 'Kpɔ Togbuiwo kple Tsiãwo',
        'view_history' => 'Xlẽ Nuto la ƒe Ŋutinya',
        'stat_clans' => 'Tsiãwo',
        'stat_members' => 'Mewowɔ ŋkɔwo',
        'stat_chiefs' => 'Fiawo / Zikpuitɔwo',
        'stat_docs' => 'Agbalẽwo'
    ]
];

if (!function_exists('__')) {
    function __($key) {
        global $translations, $lang;
        return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch traditional area info
try {
    $stmt = $pdo->query("SELECT * FROM traditional_area LIMIT 1");
    $areaInfo = $stmt->fetch();
} catch (PDOException $e) {
    $areaInfo = [
        'name' => 'Atsiame Traditional Area',
        'paramountcy' => 'Atsiame Paramouncy'
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($areaInfo['name']); ?> - Information System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-royal sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php">
            <div class="id-logo bg-warning text-dark rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 40px; height: 40px; font-weight: 700; border: 2px solid #fff; margin-right: 10px;">A</div>
            <div>
                <?php echo sanitize($areaInfo['name']); ?>
                <span class="d-block text-warning"><?php echo sanitize($areaInfo['paramountcy']); ?></span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#royalNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="royalNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>index.php"><?php echo __('home'); ?></a>
                </li>
                <li class="nav-item <?php echo ($currentPage == 'clans.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>clans.php"><?php echo __('clans'); ?></a>
                </li>
                <li class="nav-item <?php echo ($currentPage == 'towns.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>towns.php"><?php echo __('towns'); ?></a>
                </li>
                <li class="nav-item <?php echo ($currentPage == 'chiefs.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>chiefs.php"><?php echo __('chiefs'); ?></a>
                </li>
                <li class="nav-item <?php echo ($currentPage == 'events.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>events.php"><?php echo __('events'); ?></a>
                </li>
                <li class="nav-item <?php echo ($currentPage == 'gallery.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>gallery.php"><?php echo __('gallery'); ?></a>
                </li>
                <li class="nav-item <?php echo ($currentPage == 'documents.php') ? 'active' : ''; ?>">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>documents.php"><?php echo __('documents'); ?></a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="btn btn-accent ms-lg-3" href="<?php echo BASE_URL; ?>admin/index.php"><i class="fas fa-chart-line me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ms-lg-2 mt-2 mt-lg-0" href="<?php echo BASE_URL; ?>logout.php"><i class="fas fa-right-from-bracket me-1"></i> <?php echo __('logout'); ?></a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-accent ms-lg-3" href="<?php echo BASE_URL; ?>login.php"><i class="fas fa-right-to-bracket me-1"></i> <?php echo __('login'); ?></a>
                    </li>
                <?php endif; ?>
                <!-- Language Toggle Group -->
                <li class="nav-item d-flex align-items-center ms-lg-3 mt-2 mt-lg-0">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Language Toggle">
                        <a href="?lang=en" class="btn btn-sm <?php echo ($lang === 'en') ? 'btn-warning text-dark' : 'btn-outline-light'; ?>" style="font-size: 0.75rem; font-weight: 600;">EN</a>
                        <a href="?lang=ee" class="btn btn-sm <?php echo ($lang === 'ee') ? 'btn-warning text-dark' : 'btn-outline-light'; ?>" style="font-size: 0.75rem; font-weight: 600;">EE</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
