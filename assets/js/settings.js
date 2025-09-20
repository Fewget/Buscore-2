// Settings page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Get saved tab from localStorage
    var savedTab = localStorage.getItem('settingsTab');
    
    // Get all tab links
    var tabLinks = document.querySelectorAll("a[data-bs-toggle='pill']");
    
    // If we have a saved tab, try to show it
    if (savedTab) {
        for (var i = 0; i < tabLinks.length; i++) {
            if (tabLinks[i].getAttribute('href') === savedTab) {
                var tab = new bootstrap.Tab(tabLinks[i]);
                tab.show();
                break;
            }
        }
    }
    
    // Save tab when a new one is shown
    for (var i = 0; i < tabLinks.length; i++) {
        tabLinks[i].addEventListener('shown.bs.tab', function (e) {
            localStorage.setItem('settingsTab', e.target.getAttribute('href'));
        });
    }
});
