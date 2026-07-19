<?php
// includes/footer.php - Public Footer Layout

require_once __DIR__ . '/db.php';
?>
<!-- Footer -->
<footer class="footer-royal">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h4>Atsiame Traditional Area</h4>
                <p>Preserving history, tracking lineages, and facilitating local council administration for the Atsiame State in the Akatsi South Municipality, Volta Region, Ghana.</p>
                <div class="mt-3">
                    <a href="#" class="me-3 text-warning"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="me-3 text-warning"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-warning"><i class="fab fa-instagram fa-lg"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h4>Traditional Links</h4>
                <ul class="list-unstyled">
                    <li><a href="<?php echo BASE_URL; ?>index.php"><i class="fas fa-chevron-right me-2 text-warning"></i> Home Portal</a></li>
                    <li><a href="<?php echo BASE_URL; ?>clans.php"><i class="fas fa-chevron-right me-2 text-warning"></i> Clans & Totems</a></li>
                    <li><a href="<?php echo BASE_URL; ?>chiefs.php"><i class="fas fa-chevron-right me-2 text-warning"></i> Traditional Hierarchy</a></li>
                    <li><a href="<?php echo BASE_URL; ?>events.php"><i class="fas fa-chevron-right me-2 text-warning"></i> Announcements & Festivals</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h4>Administrative Portal</h4>
                <p>Are you a Traditional Council Secretary, Researcher, or Data Entry Officer?</p>
                <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-accent btn-sm mt-2">
                    <i class="fas fa-lock me-2"></i> Staff Portal Access
                </a>
            </div>
        </div>
        
        <div class="row footer-bottom text-center">
            <div class="col-md-12">
                <p>&copy; <?php echo date('Y'); ?> Atsiame Traditional Council. All rights reserved. | Developed for the Atsiame Traditional Area Management Information System (ATAMIS).</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
