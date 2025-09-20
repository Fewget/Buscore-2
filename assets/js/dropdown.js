// Initialize dropdowns when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    
    dropdownElementList.forEach(function(dropdownToggleEl) {
        // Only initialize if not already initialized
        if (!dropdownToggleEl._dropdown) {
            var dropdown = new bootstrap.Dropdown(dropdownToggleEl, {
                offset: [0, 5],
                boundary: 'clippingParents',
                reference: 'toggle',
                display: 'dynamic'
            });
            // Store reference to dropdown instance
            dropdownToggleEl._dropdown = dropdown;
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.matches('.dropdown-toggle') && !event.target.closest('.dropdown-menu')) {
            dropdownElementList.forEach(function(dropdownToggleEl) {
                var dropdown = bootstrap.Dropdown.getInstance(dropdownToggleEl);
                if (dropdown) {
                    dropdown.hide();
                }
            });
        }
    });
});
