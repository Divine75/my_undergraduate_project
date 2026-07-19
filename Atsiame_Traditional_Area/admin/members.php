<?php
// admin/members.php - Manage Family Members

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission
if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit', 'delete'])) {
    require_login(['Administrator', 'Traditional Council Secretary', 'Data Entry Officer']);
}

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete Action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $name = get_member_name($id);
        $stmt = $pdo->prepare("DELETE FROM family_members WHERE id = ?");
        $stmt->execute([$id]);
        log_audit('member_delete', "Deleted member: $name (ID: $id)");
        $success = "Member successfully removed from registry.";
    } catch (PDOException $e) {
        $error = "Cannot delete member: They are referenced as a parent, spouse, or leader. Remove relations first.";
    }
    $action = 'list';
}

// Handle Form Submission (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $other_names = trim($_POST['other_names'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $place_of_birth = trim($_POST['place_of_birth'] ?? '');
    $status = $_POST['status'] ?? 'Alive';
    $date_of_death = !empty($_POST['date_of_death']) ? $_POST['date_of_death'] : null;
    $clan_id = !empty($_POST['clan_id']) ? intval($_POST['clan_id']) : null;
    $family_id = !empty($_POST['family_id']) ? intval($_POST['family_id']) : null;
    $father_id = !empty($_POST['father_id']) ? intval($_POST['father_id']) : null;
    $mother_id = !empty($_POST['mother_id']) ? intval($_POST['mother_id']) : null;
    $spouse_id = !empty($_POST['spouse_id']) ? intval($_POST['spouse_id']) : null;
    $father_name = !empty($_POST['father_name']) ? trim($_POST['father_name']) : null;
    $mother_name = !empty($_POST['mother_name']) ? trim($_POST['mother_name']) : null;
    $spouse_name = !empty($_POST['spouse_name']) ? trim($_POST['spouse_name']) : null;
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // File upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = '../uploads/photos/';
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $photo_path = 'uploads/photos/' . $newFileName;
            }
        }
    }
    
    if (empty($first_name) || empty($last_name) || empty($date_of_birth)) {
        $error = 'First Name, Last Name, and Date of Birth are required.';
    } else {
        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO family_members 
                    (first_name, last_name, other_names, gender, date_of_birth, place_of_birth, status, date_of_death, clan_id, family_id, father_id, mother_id, spouse_id, phone, email, address, photo, bio, father_name, mother_name, spouse_name) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $first_name, $last_name, $other_names, $gender, $date_of_birth, $place_of_birth, $status, $date_of_death, 
                    $clan_id, $family_id, $father_id, $mother_id, $spouse_id, $phone, $email, $address, $photo_path, $bio,
                    $father_name, $mother_name, $spouse_name
                ]);
                $newId = $pdo->lastInsertId();
                log_audit('member_add', "Added member: $first_name $last_name (ID: $newId)");
                $success = "Member successfully registered.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            try {
                // If a photo was uploaded, update photo, otherwise preserve existing
                if ($photo_path) {
                    $stmt = $pdo->prepare("UPDATE family_members SET photo = ? WHERE id = ?");
                    $stmt->execute([$photo_path, $id]);
                }
                
                $stmt = $pdo->prepare("
                    UPDATE family_members SET 
                    first_name = ?, last_name = ?, other_names = ?, gender = ?, date_of_birth = ?, place_of_birth = ?, 
                    status = ?, date_of_death = ?, clan_id = ?, family_id = ?, father_id = ?, mother_id = ?, spouse_id = ?, 
                    phone = ?, email = ?, address = ?, bio = ?, father_name = ?, mother_name = ?, spouse_name = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $first_name, $last_name, $other_names, $gender, $date_of_birth, $place_of_birth, 
                    $status, $date_of_death, $clan_id, $family_id, $father_id, $mother_id, $spouse_id, 
                    $phone, $email, $address, $bio, $father_name, $mother_name, $spouse_name, $id
                ]);
                
                log_audit('member_edit', "Updated member: $first_name $last_name (ID: $id)");
                $success = "Member profile updated.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = 'Update failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch member for editing
$editMem = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM family_members WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editMem = $stmt->fetch();
    if (!$editMem) {
        $error = 'Member not found.';
        $action = 'list';
    }
}

// Listing parameters (Search & filters)
$search = trim($_GET['search'] ?? '');
$clanFilter = !empty($_GET['clan_id']) ? intval($_GET['clan_id']) : null;
$familyFilter = !empty($_GET['family_id']) ? intval($_GET['family_id']) : null;
$genderFilter = $_GET['gender'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build Query
$query = "
    SELECT fm.*, c.name as clan_name, f.name as family_name 
    FROM family_members fm
    LEFT JOIN clans c ON fm.clan_id = c.id
    LEFT JOIN families f ON fm.family_id = f.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $query .= " AND (fm.first_name LIKE ? OR fm.last_name LIKE ? OR fm.other_names LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($clanFilter) {
    $query .= " AND fm.clan_id = ?";
    $params[] = $clanFilter;
}
if ($familyFilter) {
    $query .= " AND fm.family_id = ?";
    $params[] = $familyFilter;
}
if ($genderFilter !== '') {
    $query .= " AND fm.gender = ?";
    $params[] = $genderFilter;
}
if ($statusFilter !== '') {
    $query .= " AND fm.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY fm.first_name ASC";

// Pagination
$limit = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Count total
$countQuery = str_replace("SELECT fm.*, c.name as clan_name, f.name as family_name", "SELECT COUNT(*)", $query);
$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$query .= " LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Fetch auxiliary lists for dropdowns
$clans = $pdo->query("SELECT id, name FROM clans ORDER BY name ASC")->fetchAll();
$families = $pdo->query("SELECT id, name FROM families ORDER BY name ASC")->fetchAll();

// Fetch potential fathers, mothers, spouses (Alive or Deceased)
$allMaleMembers = $pdo->query("SELECT id, first_name, last_name FROM family_members WHERE gender = 'Male' ORDER BY first_name ASC")->fetchAll();
$allFemaleMembers = $pdo->query("SELECT id, first_name, last_name FROM family_members WHERE gender = 'Female' ORDER BY first_name ASC")->fetchAll();
$allMembers = $pdo->query("SELECT id, first_name, last_name FROM family_members ORDER BY first_name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-users text-warning me-2"></i> Family Member Registry</h2>
    <?php if ($action === 'list'): ?>
        <a href="members.php?action=add" class="btn btn-royal"><i class="fas fa-user-plus me-1"></i> Register Member</a>
    <?php else: ?>
        <a href="members.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. REGISTRY LISTING -->
<?php if ($action === 'list'): ?>
    <!-- Search / Filter Card -->
    <div class="card border border-light shadow-sm mb-4 bg-white rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="members.php" class="row g-2">
                <input type="hidden" name="action" value="list">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" value="<?php echo sanitize($search); ?>" placeholder="Search name...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="clan_id">
                        <option value="">All Clans</option>
                        <?php foreach ($clans as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $clanFilter) ? 'selected' : ''; ?>><?php echo sanitize($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="family_id">
                        <option value="">All Families</option>
                        <?php foreach ($families as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo ($f['id'] == $familyFilter) ? 'selected' : ''; ?>><?php echo sanitize($f['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="gender">
                        <option value="">All Genders</option>
                        <option value="Male" <?php echo ($genderFilter === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($genderFilter === 'Female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="Alive" <?php echo ($statusFilter === 'Alive') ? 'selected' : ''; ?>>Alive</option>
                        <option value="Deceased" <?php echo ($statusFilter === 'Deceased') ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Members Table -->
    <div class="table-royal table-responsive border shadow-sm bg-white mb-4">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Clan & Family</th>
                    <th>Parents</th>
                    <th>Gender & Age</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No matching members found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $m): 
                        // Compute age
                        $dob = new DateTime($m['date_of_birth']);
                        $diff = $dob->diff(new DateTime($m['date_of_death'] ?? 'now'));
                        $age = $diff->y;
                        
                        $mName = sanitize($m['first_name'] . ' ' . $m['last_name']);
                        $photoUrl = $m['photo'] ? BASE_URL . $m['photo'] : '';
                    ?>
                        <tr>
                            <td>
                                <img src="<?php echo $photoUrl; ?>" onerror="this.src='https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=40&h=40'" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;" alt="<?php echo $mName; ?>">
                            </td>
                            <td>
                                <strong><?php echo $mName; ?></strong>
                                <?php if (!empty($m['other_names'])): ?>
                                    <small class="text-muted d-block"><?php echo sanitize($m['other_names']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo $m['clan_name'] ? sanitize($m['clan_name']) : 'No Clan'; ?></span>
                                <span class="badge bg-light text-dark border"><?php echo $m['family_name'] ? sanitize($m['family_name']) : 'No Family'; ?></span>
                            </td>
                            <td>
                                <?php
                                $fNameStr = !empty($m['father_id']) ? get_member_name($m['father_id']) : (!empty($m['father_name']) ? $m['father_name'] : '');
                                $mNameStr = !empty($m['mother_id']) ? get_member_name($m['mother_id']) : (!empty($m['mother_name']) ? $m['mother_name'] : '');
                                $sNameStr = !empty($m['spouse_id']) ? get_member_name($m['spouse_id']) : (!empty($m['spouse_name']) ? $m['spouse_name'] : '');
                                
                                if ($sNameStr) {
                                    echo '<div class="small"><strong>Spouse:</strong> ' . sanitize($sNameStr) . '</div>';
                                }
                                if ($fNameStr) {
                                    echo '<div class="small"><strong>F:</strong> ' . sanitize($fNameStr) . '</div>';
                                }
                                if ($mNameStr) {
                                    echo '<div class="small"><strong>M:</strong> ' . sanitize($mNameStr) . '</div>';
                                }
                                if (!$sNameStr && !$fNameStr && !$mNameStr) {
                                    echo '<span class="text-muted small">Not recorded</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo sanitize($m['gender']); ?>
                                <small class="text-muted d-block">Age: <?php echo $age; ?> yrs</small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($m['status'] == 'Alive') ? 'success' : 'secondary'; ?> rounded-pill">
                                    <?php echo sanitize($m['status']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="member-details.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-royal me-1">
                                    <i class="fas fa-id-card"></i> Profile
                                </a>
                                <a href="members.php?action=edit&id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if ($user_role === 'Administrator'): ?>
                                    <a href="members.php?action=delete&id=<?php echo $m['id']; ?>" onclick="return confirm('Are you sure you want to delete this member profile?');" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link text-success" href="members.php?action=list&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&clan_id=<?php echo $clanFilter; ?>&family_id=<?php echo $familyFilter; ?>&gender=<?php echo $genderFilter; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

<!-- 2. ADD / EDIT REGISTRY FORM -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $title = ($action === 'add') ? 'Register Family Member' : 'Edit Member Profile: ' . get_member_name($editMem['id']);
    $submitText = ($action === 'add') ? 'Save Registry' : 'Update Profile';
    
    // Values
    $fname = ($action === 'edit') ? $editMem['first_name'] : '';
    $lname = ($action === 'edit') ? $editMem['last_name'] : '';
    $onames = ($action === 'edit') ? $editMem['other_names'] : '';
    $genderVal = ($action === 'edit') ? $editMem['gender'] : 'Male';
    $dobVal = ($action === 'edit') ? $editMem['date_of_birth'] : '';
    $pobVal = ($action === 'edit') ? $editMem['place_of_birth'] : '';
    $statusVal = ($action === 'edit') ? $editMem['status'] : 'Alive';
    $dodVal = ($action === 'edit') ? $editMem['date_of_death'] : '';
    $clanVal = ($action === 'edit') ? $editMem['clan_id'] : '';
    $familyVal = ($action === 'edit') ? $editMem['family_id'] : '';
    $fatherVal = ($action === 'edit') ? $editMem['father_id'] : '';
    $motherVal = ($action === 'edit') ? $editMem['mother_id'] : '';
    $spouseVal = ($action === 'edit') ? $editMem['spouse_id'] : '';
    $fatherNameVal = ($action === 'edit') ? $editMem['father_name'] : '';
    $motherNameVal = ($action === 'edit') ? $editMem['mother_name'] : '';
    $spouseNameVal = ($action === 'edit') ? $editMem['spouse_name'] : '';
    $phoneVal = ($action === 'edit') ? $editMem['phone'] : '';
    $emailVal = ($action === 'edit') ? $editMem['email'] : '';
    $addressVal = ($action === 'edit') ? $editMem['address'] : '';
    $bioVal = ($action === 'edit') ? $editMem['bio'] : '';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $title; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo sanitize($fname); ?>" required placeholder="e.g. Kwami">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo sanitize($lname); ?>" required placeholder="e.g. Katsriku">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="other_names">Other Names / Stool Titles</label>
                        <input type="text" class="form-control" id="other_names" name="other_names" value="<?php echo sanitize($onames); ?>" placeholder="e.g. Torgbui Katsriku II">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="gender">Gender <span class="text-danger">*</span></label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="Male" <?php echo ($genderVal == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($genderVal == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="date_of_birth">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo $dobVal; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="place_of_birth">Place of Birth</label>
                        <input type="text" class="form-control" id="place_of_birth" name="place_of_birth" value="<?php echo sanitize($pobVal); ?>" placeholder="e.g. Atsiame">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="status">Life Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Alive" <?php echo ($statusVal == 'Alive') ? 'selected' : ''; ?>>Alive</option>
                            <option value="Deceased" <?php echo ($statusVal == 'Deceased') ? 'selected' : ''; ?>>Deceased</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="date_of_death">Date of Death (If Deceased)</label>
                        <input type="date" class="form-control" id="date_of_death" name="date_of_death" value="<?php echo $dodVal; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="clan_id">Traditional Clan</label>
                        <select class="form-select" id="clan_id" name="clan_id">
                            <option value="">-- Select Clan --</option>
                            <?php foreach ($clans as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $clanVal) ? 'selected' : ''; ?>><?php echo sanitize($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="family_id">Traditional Family</label>
                        <select class="form-select" id="family_id" name="family_id">
                            <option value="">-- Select Family --</option>
                            <?php foreach ($families as $f): ?>
                                <option value="<?php echo $f['id']; ?>" <?php echo ($f['id'] == $familyVal) ? 'selected' : ''; ?>><?php echo sanitize($f['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row border-top border-bottom py-3 my-4 bg-light">
                    <h6 class="text-success font-weight-bold mb-3"><i class="fas fa-sitemap me-2"></i> Genealogy & Lineage (Relations)</h6>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="father_id">Father (Registered)</label>
                        <select class="form-select mb-2" id="father_id" name="father_id">
                            <option value="">-- Select Registered Father --</option>
                            <?php foreach ($allMaleMembers as $m): 
                                if ($action === 'edit' && $m['id'] == $editMem['id']) continue; // skip self
                            ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $fatherVal) ? 'selected' : ''; ?>><?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="form-label small text-muted mb-1" for="father_name">Or Father's Name (if unregistered):</label>
                        <input type="text" class="form-control form-control-sm" id="father_name" name="father_name" value="<?php echo sanitize($fatherNameVal); ?>" placeholder="e.g. Yao Katsriku">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="mother_id">Mother (Registered)</label>
                        <select class="form-select mb-2" id="mother_id" name="mother_id">
                            <option value="">-- Select Registered Mother --</option>
                            <?php foreach ($allFemaleMembers as $m): 
                                if ($action === 'edit' && $m['id'] == $editMem['id']) continue; // skip self
                            ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $motherVal) ? 'selected' : ''; ?>><?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="form-label small text-muted mb-1" for="mother_name">Or Mother's Name (if unregistered):</label>
                        <input type="text" class="form-control form-control-sm" id="mother_name" name="mother_name" value="<?php echo sanitize($motherNameVal); ?>" placeholder="e.g. Abla Katsriku">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold" for="spouse_id">Spouse (Registered)</label>
                        <select class="form-select mb-2" id="spouse_id" name="spouse_id">
                            <option value="">-- Select Registered Spouse --</option>
                            <?php foreach ($allMembers as $m): 
                                if ($action === 'edit' && $m['id'] == $editMem['id']) continue; // skip self
                            ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $spouseVal) ? 'selected' : ''; ?>><?php echo sanitize($m['first_name'] . ' ' . $m['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="form-label small text-muted mb-1" for="spouse_name">Or Spouse's Name (if unregistered):</label>
                        <input type="text" class="form-control form-control-sm" id="spouse_name" name="spouse_name" value="<?php echo sanitize($spouseNameVal); ?>" placeholder="e.g. Ama Katsriku">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="phone">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo sanitize($phoneVal); ?>" placeholder="e.g. 0244123456">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitize($emailVal); ?>" placeholder="e.g. member@email.com">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="address">Residential Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?php echo sanitize($addressVal); ?>" placeholder="e.g. 15 Stool Road, Atsiame">
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-bold" for="photo">Profile Photo</label>
                    <input type="file" class="form-control" id="photo" name="photo" accept="image/png, image/jpeg, image/jpg">
                    <?php if ($action === 'edit' && $editMem['photo']): ?>
                        <small class="text-success d-block mt-1">Existing photo: <?php echo sanitize($editMem['photo']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="bio">Biography / Historical Achievements</label>
                    <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Enter details about their role, achievements, or notable history..."><?php echo sanitize($bioVal); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
