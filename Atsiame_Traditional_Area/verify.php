<?php
// verify.php - Automated Verification Test Suite

$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'atamis_db';

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>";

echo "=== ATAMIS VERIFICATION TEST SUITE ===" . $nl . $nl;

$failed = false;

// 1. Test Database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ [PASS] Connected to database `$dbName` successfully." . $nl;
} catch (PDOException $e) {
    echo "✗ [FAIL] Database connection failed: " . $e->getMessage() . $nl;
    $failed = true;
}

if (!$failed) {
    // 2. Test Tables existence
    $requiredTables = [
        'traditional_area', 'users', 'clans', 'families', 'family_members',
        'marriages', 'traditional_positions', 'appointments', 'succession_history',
        'documents', 'gallery', 'events', 'audit_logs', 'settings', 'towns'
    ];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            echo "✓ [PASS] Table `$table` verified." . $nl;
        } catch (PDOException $e) {
            echo "✗ [FAIL] Table `$table` is missing: " . $e->getMessage() . $nl;
            $failed = true;
        }
    }
    
    // 3. Test Admin credentials exists and is valid
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminPassHash = $stmt->fetchColumn();
        
        if ($adminPassHash && password_verify('AdminPassword123', $adminPassHash)) {
            echo "✓ [PASS] Default Administrator user and password verification succeeded." . $nl;
        } else {
            echo "✗ [FAIL] Default Administrator user not found or password verification failed." . $nl;
            $failed = true;
        }
    } catch (PDOException $e) {
        echo "✗ [FAIL] Users verification error: " . $e->getMessage() . $nl;
        $failed = true;
    }
}

// 4. Lint important files for syntax checks
$filesToLint = [
    'includes/db.php',
    'includes/auth.php',
    'includes/functions.php',
    'index.php',
    'clans.php',
    'chiefs.php',
    'events.php',
    'gallery.php',
    'documents.php',
    'login.php',
    'logout.php',
    'admin/index.php',
    'admin/clans.php',
    'admin/families.php',
    'admin/members.php',
    'admin/member-details.php',
    'admin/hierarchy.php',
    'admin/succession.php',
    'admin/documents.php',
    'admin/gallery.php',
    'admin/events.php',
    'admin/users.php',
    'admin/settings.php',
    'towns.php',
    'admin/towns.php'
];

echo $nl . "--- LINTING FILES ---" . $nl;
foreach ($filesToLint as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (!file_exists($fullPath)) {
        echo "✗ [FAIL] File missing: $file" . $nl;
        $failed = true;
        continue;
    }
    
    // Call PHP CLI linting
    $output = [];
    $retVal = 0;
    // Escaping file path for shell
    $escapedFile = escapeshellarg($fullPath);
    exec("c:\\xa\\php\\php.exe -l $escapedFile 2>&1", $output, $retVal);
    
    if ($retVal === 0) {
        echo "✓ [PASS] $file syntax valid." . $nl;
    } else {
        echo "✗ [FAIL] $file syntax error: " . implode(" | ", $output) . $nl;
        $failed = true;
    }
}

echo $nl . "======================================" . $nl;
if ($failed) {
    echo "✗ SUMMARY: VERIFICATION SUITE FAILED." . $nl;
    exit(1);
} else {
    echo "✓ SUMMARY: ALL TESTS PASSED SUCCESSFULLY!" . $nl;
    exit(0);
}
?>
