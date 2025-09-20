/**
 * Main JavaScript file for Buscore
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips and popovers
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Rating stars interaction
    const ratingContainers = document.querySelectorAll('.rating-input');
    
    ratingContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const hiddenInput = container.querySelector('input[type="hidden"]');
        
        if (stars.length && hiddenInput) {
            stars.forEach((star, index) => {
                // Set initial active state
                if (index < hiddenInput.value) {
                    star.classList.add('active');
                }
                
                // Hover effect
                star.addEventListener('mouseover', () => {
                    // Remove active class from all stars
                    stars.forEach(s => s.classList.remove('active'));
                    // Add active class to current and previous stars
                    for (let i = 0; i <= index; i++) {
                        stars[i].classList.add('active');
                    }
                });
                
                // Click event
                star.addEventListener('click', () => {
                    const value = index + 1;
                    hiddenInput.value = value;
                    // Update any visual feedback if needed
                });
            });
            
            // Reset stars on mouse leave if no selection made
            container.addEventListener('mouseleave', () => {
                const currentValue = parseInt(hiddenInput.value) || 0;
                stars.forEach((star, index) => {
                    star.classList.toggle('active', index < currentValue);
                });
            });
        }
    });
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // Add error message if not exists
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = field.dataset.error || 'This field is required';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                } else {
                    field.classList.remove('error');
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                    
                    // Additional validation for email fields
                    if (field.type === 'email' && !isValidEmail(field.value)) {
                        isValid = false;
                        field.classList.add('error');
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-message';
                        errorMsg.textContent = 'Please enter a valid email address';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // Helper function to validate email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
    
    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.alert');
    
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease';
            message.style.opacity = '0';
            
            // Remove from DOM after animation
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
    
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80, // Adjust for fixed header
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Initialize any tooltips
    const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
    
    tooltipTriggers.forEach(trigger => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = trigger.dataset.tooltip;
        
        trigger.style.position = 'relative';
        trigger.appendChild(tooltip);
        
        trigger.addEventListener('mouseenter', () => {
            tooltip.classList.add('active');
        });
        
        trigger.addEventListener('mouseleave', () => {
            tooltip.classList.remove('active');
        });
    });
});

// AJAX helper function
function fetchData(url, options = {}) {
    return fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers
        },
        ...options
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    });
}

// Debounce function for performance optimization
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
