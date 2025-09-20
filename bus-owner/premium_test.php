<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
check_bus_owner_access();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Modal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <button id="testButton" class="btn btn-primary">Test Modal</button>
        
        <!-- Simple Modal -->
        <div class="modal fade" id="testModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Test Modal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        This is a test modal.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const button = document.getElementById('testButton');
        const modalEl = document.getElementById('testModal');
        
        button.addEventListener('click', function() {
            console.log('Button clicked');
            const modal = new bootstrap.Modal(modalEl);
            console.log('Modal instance created');
            modal.show();
            console.log('Modal show called');
        });
        
        // Log modal events
        modalEl.addEventListener('show.bs.modal', () => console.log('Modal show event'));
        modalEl.addEventListener('shown.bs.modal', () => console.log('Modal shown event'));
    });
    </script>
</body>
</html>
