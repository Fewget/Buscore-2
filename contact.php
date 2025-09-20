<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us - ' . SITE_NAME;

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-4 text-center">Contact Us</h1>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <!-- Company Details (Primary) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0">Contact Serendib Labs</h3>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded" style="width: 120px; height: 120px; margin: 0 auto; border: 3px solid #f8f9fa; box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);">
                                        <i class="fas fa-building fa-3x text-primary"></i>
                                    </div>
                                </div>
                                <div class="col-md-8
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="icon-lg bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="min-width: 40px; height: 40px;">
                                                    <i class="fas fa-envelope"></i>
                                                </div>
                                                <div>
                                                    <h5 class="h6 mb-1">Email</h5>
                                                    <a href="mailto:labs.serendib@gmail.com" class="text-reset">labs.serendib@gmail.com</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="icon-lg bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="min-width: 40px; height: 40px;">
                                                    <i class="fas fa-phone"></i>
                                                </div>
                                                <div>
                                                    <h5 class="h6 mb-1">Phone</h5>
                                                    <a href="tel:+94707691616" class="text-reset">+94 70 769 1616</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CEO Details (Secondary) -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h4 class="mb-0">Our CEO</h4>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center mb-3 mb-md-0">
                                    <div class="rounded-circle overflow-hidden d-inline-block" style="width: 120px; height: 120px; border: 3px solid #f8f9fa; box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);">
                                        <img src="/BS/assets/Images/1ceo.jpg" alt="Gavesha Dissanayaka" class="img-fluid h-100 w-100" style="object-fit: cover;">
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <h3 class="h5 mb-1">Gavesha Dissanayaka</h3>
                                    <p class="text-muted mb-2">Chief Executive Officer</p>
                                    <p class="mb-2">
                                        <i class="fas fa-graduation-cap me-2 text-primary"></i>BSc. Engineering Undergraduate<br>
                                        <span class="ms-4">University of Ruhuna</span>
                                    </p>
                                    <div class="d-flex flex-wrap gap-3 mt-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-envelope me-2 text-primary"></i>
                                            <a href="mailto:dissanayakagavesha@gmail.com" class="text-reset">dissanayakagavesha@gmail.com</a>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-phone me-2 text-primary"></i>
                                            <a href="tel:+94717416866" class="text-reset">+94 71 741 6866</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Send us a Message</h5>
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="name" placeholder="Your Name" required>
                                    <label for="name">Your Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" placeholder="Your Email" required>
                                    <label for="email">Email address</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="subject" placeholder="Subject" required>
                            <label for="subject">Subject</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" placeholder="Your Message" id="message" style="height: 150px" required></textarea>
                            <label for="message">Your Message</label>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-paper-plane me-2"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
