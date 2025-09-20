    </main>
<?php
// Ensure config is included if not already included
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}
?>
<footer class="main-footer">
    <div class="container">
        <div class="row justify-content-center">
            <!-- About Section -->
            <div class="col-md-4 mb-5 mb-md-0">
                <div class="h-100 d-flex flex-column align-items-center text-center">
                    <h3 class="mb-3">About BuScore</h3>
                    <p class="mb-4">BuScore helps you find and rate bus services to make informed travel decisions across Sri Lanka.</p>
                    <div class="mt-auto text-center text-md-start">
                        <div class="d-inline-flex align-items-center">
                            <i class="fas fa-bus me-2" style="font-size: 1.25rem;"></i>
                            <span style="font-size: 1.25rem; font-weight: 500;">BuScore</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links Section -->
            <div class="col-md-3 mb-5 mb-md-0">
                <div class="d-flex flex-column align-items-center text-center">
                    <h3 class="mb-3">Quick Links</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/BS/">Home</a></li>
                        <li class="mb-2"><a href="/BS/about.php">About Us</a></li>
                        <li class="mb-2"><a href="/BS/privacy.php">Privacy Policy</a></li>
                        <li class="mb-2"><a href="/BS/terms.php">Terms of Service</a></li>
                        <li class="mb-2"><a href="/BS/contact.php">Contact Us</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="col-md-4">
                <div class="d-flex flex-column align-items-center text-center">
                    <h3 class="mb-3">Get In Touch</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 17/4, Beligaswatta, Kotadeniyawa</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +94 70 769 1616</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@buscore.lk</li>
                    </ul>
                    <div class="social-links justify-content-center">
                        <a href="https://www.facebook.com/share/1CqcFYHhCj/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom text-center mt-4 pt-3 border-top">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Initialize tooltips and popovers -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize all popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    });
    </script>
</body>
</html>
