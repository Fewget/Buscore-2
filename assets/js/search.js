document.addEventListener('DOMContentLoaded', function() {
    // Get all search forms on the page
    const searchForms = document.querySelectorAll('.search-form');
    
    // Add submit event listener to each form
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="q"]');
            if (searchInput && searchInput.value.trim() === '') {
                e.preventDefault(); // Prevent form submission if search is empty
                searchInput.focus();
            }
            // Let the form submit normally if there's a search term
        });
    });
});
