<?php
// includes/functions.php - Helper Functions

require_once __DIR__ . '/db.php';

// Safe sanitization
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Log admin action to audit trails
function log_audit($action, $details = '') {
    global $pdo;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO `audit_logs` (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (PDOException $e) {
        // Fail silently or log to error log
        error_log("Failed to log audit: " . $e->getMessage());
    }
}

// Get full name of member
function get_member_name($id) {
    global $pdo;
    if (!$id) return '';
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, other_names FROM family_members WHERE id = ?");
        $stmt->execute([$id]);
        $m = $stmt->fetch();
        if ($m) {
            $other = $m['other_names'] ? " (" . $m['other_names'] . ")" : "";
            return sanitize($m['first_name'] . ' ' . $m['last_name'] . $other);
        }
    } catch (PDOException $e) {}
    return 'Unknown Member';
}

// Check if member is a chief or holds a title
function get_member_title($id) {
    global $pdo;
    if (!$id) return '';
    try {
        $stmt = $pdo->prepare("SELECT tp.title FROM appointments a JOIN traditional_positions tp ON a.position_id = tp.id WHERE a.member_id = ? AND a.status = 'Active' LIMIT 1");
        $stmt->execute([$id]);
        $title = $stmt->fetchColumn();
        return $title ? sanitize($title) : '';
    } catch (PDOException $e) {}
    return '';
}

// Fetch Clan Name
function get_clan_name($id) {
    global $pdo;
    if (!$id) return 'No Clan';
    try {
        $stmt = $pdo->prepare("SELECT name FROM clans WHERE id = ?");
        $stmt->execute([$id]);
        return sanitize($stmt->fetchColumn());
    } catch (PDOException $e) {}
    return 'Unknown Clan';
}

// Fetch Family Name
function get_family_name($id) {
    global $pdo;
    if (!$id) return 'No Family';
    try {
        $stmt = $pdo->prepare("SELECT name FROM families WHERE id = ?");
        $stmt->execute([$id]);
        return sanitize($stmt->fetchColumn());
    } catch (PDOException $e) {}
    return 'Unknown Family';
}

// Pack genealogy relations for rendering
function get_genealogy_tree_nodes($member_id) {
    global $pdo;
    
    // Fetch root member
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, other_names, gender, status, photo, father_id, mother_id, spouse_id, date_of_birth FROM family_members WHERE id = ?");
    $stmt->execute([$member_id]);
    $root = $stmt->fetch();
    if (!$root) return [];
    
    $members_to_fetch = [$root['id']];
    if ($root['father_id']) $members_to_fetch[] = $root['father_id'];
    if ($root['mother_id']) $members_to_fetch[] = $root['mother_id'];
    if ($root['spouse_id']) $members_to_fetch[] = $root['spouse_id'];
    
    // Fetch children
    $stmt_children = $pdo->prepare("SELECT id FROM family_members WHERE father_id = ? OR mother_id = ?");
    $stmt_children->execute([$root['id'], $root['id']]);
    $children_ids = $stmt_children->fetchAll(PDO::FETCH_COLUMN);
    $members_to_fetch = array_merge($members_to_fetch, $children_ids);
    
    // Fetch father's parents (paternal grandparents)
    if ($root['father_id']) {
        $stmt_parents = $pdo->prepare("SELECT father_id, mother_id FROM family_members WHERE id = ?");
        $stmt_parents->execute([$root['father_id']]);
        $fp = $stmt_parents->fetch();
        if ($fp) {
            if ($fp['father_id']) $members_to_fetch[] = $fp['father_id'];
            if ($fp['mother_id']) $members_to_fetch[] = $fp['mother_id'];
        }
    }
    
    // Fetch mother's parents (maternal grandparents)
    if ($root['mother_id']) {
        $stmt_parents = $pdo->prepare("SELECT father_id, mother_id FROM family_members WHERE id = ?");
        $stmt_parents->execute([$root['mother_id']]);
        $mp = $stmt_parents->fetch();
        if ($mp) {
            if ($mp['father_id']) $members_to_fetch[] = $mp['father_id'];
            if ($mp['mother_id']) $members_to_fetch[] = $mp['mother_id'];
        }
    }
    
    $members_to_fetch = array_unique($members_to_fetch);
    if (empty($members_to_fetch)) return [];
    
    $in = str_repeat('?,', count($members_to_fetch) - 1) . '?';
    $stmt_all = $pdo->prepare("SELECT id, first_name, last_name, other_names, gender, status, photo, father_id, mother_id, spouse_id, date_of_birth FROM family_members WHERE id IN ($in)");
    $stmt_all->execute(array_values($members_to_fetch));
    $nodes = $stmt_all->fetchAll();
    
    // Format node details
    foreach ($nodes as &$node) {
        $node['name'] = $node['first_name'] . ' ' . $node['last_name'];
        $node['title'] = get_member_title($node['id']);
        $node['photo_url'] = $node['photo'] ? BASE_URL . $node['photo'] : '';
    }
    
    return $nodes;
}
?>
