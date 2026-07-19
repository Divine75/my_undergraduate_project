<?php
// admin/events.php - Manage Events & Council Calendar

require_once '../includes/db.php';
require_once '../includes/admin-header.php';

// Enforce role permission
require_login(['Administrator', 'Traditional Council Secretary', 'Data Entry Officer']);

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle Delete Action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmtName = $pdo->prepare("SELECT title FROM events WHERE id = ?");
        $stmtName->execute([$id]);
        $title = $stmtName->fetchColumn();
        
        if ($title) {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            log_audit('event_delete', "Deleted event: $title (ID: $id)");
            $success = "Event successfully deleted.";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete event: " . $e->getMessage();
    }
    $action = 'list';
}

// Handle Form Submission (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $category = $_POST['category'] ?? 'General';
    
    if (empty($title) || empty($event_date)) {
        $error = 'Event Title and Date are required.';
    } else {
        if ($action === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, category) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $event_date, $location, $category]);
                $newId = $pdo->lastInsertId();
                log_audit('event_add', "Created event: $title (ID: $newId)");
                $success = "Event scheduled successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Failed to create event: " . $e->getMessage();
            }
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            try {
                $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, location = ?, category = ? WHERE id = ?");
                $stmt->execute([$title, $description, $event_date, $location, $category, $id]);
                log_audit('event_edit', "Updated event: $title (ID: $id)");
                $success = "Event details updated successfully.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Failed to update event: " . $e->getMessage();
            }
        }
    }
}

// Fetch event for editing
$editEvent = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([intval($_GET['id'])]);
    $editEvent = $stmt->fetch();
    if (!$editEvent) {
        $error = 'Event not found.';
        $action = 'list';
    }
}

// Fetch all events
$events = [];
try {
    $events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();
} catch (PDOException $e) {}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0 text-success" style="font-family: 'Playfair Display', serif;"><i class="fas fa-calendar-days text-warning me-2"></i> Calendar & Announcements</h2>
    <?php if ($action === 'list'): ?>
        <a href="events.php?action=add" class="btn btn-royal"><i class="fas fa-plus me-1"></i> Schedule Event</a>
    <?php else: ?>
        <a href="events.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Calendar</a>
    <?php endif; ?>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2"><i class="fas fa-check-circle me-2"></i> <?php echo sanitize($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo sanitize($error); ?></div>
<?php endif; ?>

<!-- 1. EVENTS LISTING -->
<?php if ($action === 'list'): ?>
    <div class="table-royal table-responsive border shadow-sm bg-white mb-4">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event Details</th>
                    <th>Location</th>
                    <th>Category</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No scheduled activities.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('M d, Y', strtotime($event['event_date'])); ?></strong>
                                <small class="text-muted d-block"><?php echo date('l', strtotime($event['event_date'])); ?></small>
                            </td>
                            <td>
                                <strong><?php echo sanitize($event['title']); ?></strong>
                                <small class="text-muted d-block" style="font-size: 0.8rem;"><?php echo sanitize(substr($event['description'], 0, 100)) . '...'; ?></small>
                            </td>
                            <td><?php echo sanitize($event['location']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo sanitize($event['category']); ?></span></td>
                            <td class="text-end">
                                <a href="events.php?action=edit&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="events.php?action=delete&id=<?php echo $event['id']; ?>" onclick="return confirm('Confirm event cancellation?');" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- 2. ADD / EDIT EVENT FORM -->
<?php elseif ($action === 'add' || $action === 'edit'): 
    $titleHeader = ($action === 'add') ? 'Schedule Traditional Event' : 'Edit Event: ' . sanitize($editEvent['title']);
    $submitText = ($action === 'add') ? 'Schedule Event' : 'Update Event';
    
    $titleVal = ($action === 'edit') ? $editEvent['title'] : '';
    $descVal = ($action === 'edit') ? $editEvent['description'] : '';
    $dateVal = ($action === 'edit') ? $editEvent['event_date'] : date('Y-m-d');
    $locVal = ($action === 'edit') ? $editEvent['location'] : '';
    $catVal = ($action === 'edit') ? $editEvent['category'] : 'General';
?>
    <div class="card border border-light shadow-sm bg-white rounded-3 mb-5">
        <div class="card-header bg-success text-white py-3">
            <h5 class="m-0 font-weight-bold" style="font-family: 'Playfair Display', serif;"><?php echo $titleHeader; ?></h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="title">Event Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo sanitize($titleVal); ?>" required placeholder="e.g. Traditional Council Assembly">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="event_date">Date Scheduled <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="event_date" name="event_date" value="<?php echo $dateVal; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="location">Venue / Location</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo sanitize($locVal); ?>" placeholder="e.g. Paramount Durbar Grounds">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label font-weight-bold" for="category">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="Festival" <?php echo ($catVal === 'Festival') ? 'selected' : ''; ?>>Festival Celebration</option>
                            <option value="Meeting" <?php echo ($catVal === 'Meeting') ? 'selected' : ''; ?>>Council Meeting / Forum</option>
                            <option value="Funeral" <?php echo ($catVal === 'Funeral') ? 'selected' : ''; ?>>Funeral Announcement</option>
                            <option value="Customary" <?php echo ($catVal === 'Customary') ? 'selected' : ''; ?>>Custom Rites / Rites</option>
                            <option value="General" <?php echo ($catVal === 'General') ? 'selected' : ''; ?>>General Area Notice</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label font-weight-bold" for="description">Event Details / Announcement Text</label>
                    <textarea class="form-control" id="description" name="description" rows="5" placeholder="Enter agenda, dress codes, guidelines, or details..."><?php echo sanitize($descVal); ?></textarea>
                </div>

                <button type="submit" class="btn btn-royal"><i class="fas fa-save me-1"></i> <?php echo $submitText; ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../includes/admin-footer.php';
?>
