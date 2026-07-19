<?php
// events.php - Public Events and Announcements Board

require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch all events
$events = [];
try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!-- Banner -->
<section class="hero-section" style="padding: 60px 0;">
    <div class="container">
        <h1>Events & Announcements</h1>
        <p>Stay informed about traditional durbars, council sessions, developmental forums, and annual festivals.</p>
    </div>
</section>

<!-- Events Board -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h3 class="mb-4 text-success royal-title"><i class="fas fa-calendar-alt text-warning me-2"></i> Scheduled Activities</h3>
                <?php if (empty($events)): ?>
                    <div class="text-center p-5 border rounded bg-white">
                        <p class="text-muted m-0">No active events or announcements recorded.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): 
                        $eventTime = strtotime($event['event_date']);
                        $day = date('d', $eventTime);
                        $month = date('M', $eventTime);
                        $year = date('Y', $eventTime);
                        
                        // Select border color based on category
                        $border = 'border-success';
                        if ($event['category'] == 'Festival') {
                            $border = 'border-warning';
                        } elseif ($event['category'] == 'Meeting') {
                            $border = 'border-primary';
                        }
                    ?>
                        <div class="card mb-4 border-0 border-start border-4 <?php echo $border; ?> shadow-sm">
                            <div class="card-body p-4 d-flex gap-4 align-items-center">
                                <div class="event-date-box" style="width: 80px; flex-shrink: 0; background: var(--primary);">
                                    <span class="day text-white" style="font-size: 1.8rem; font-weight: 700; display: block; text-align: center;"><?php echo $day; ?></span>
                                    <span class="month text-white-50" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; display: block; text-align: center;"><?php echo $month; ?></span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-light text-dark border font-weight-bold" style="font-size: 0.7rem; text-transform: uppercase;"><?php echo sanitize($event['category']); ?></span>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i> <?php echo date('l, F j, Y', $eventTime); ?></small>
                                    </div>
                                    <h4 class="card-title font-weight-bold m-0 mb-2" style="color: var(--primary);"><?php echo sanitize($event['title']); ?></h4>
                                    <p class="text-muted m-0 mb-3" style="font-size: 0.9rem; text-align: justify;"><?php echo sanitize($event['description']); ?></p>
                                    <div class="text-secondary" style="font-size: 0.8rem; font-weight: 500;">
                                        <i class="fas fa-location-dot text-warning me-1"></i> Location: <?php echo sanitize($event['location']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>
