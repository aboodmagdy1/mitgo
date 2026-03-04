// GrapesJS Form Submission Fix
console.log('GrapesJS Form Fix loaded');

document.addEventListener('DOMContentLoaded', function() {
    
    // Function to prevent form submission when using GrapesJS
    function preventGrapesJSFormSubmission() {
        // Find all forms containing GrapesJS editors
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            const grapesJSContainers = form.querySelectorAll('.filament-grapesjs, .grapesjs-wrapper');
            
            if (grapesJSContainers.length > 0) {
                console.log('Found form with GrapesJS, adding Enter key prevention');
                
                // Prevent Enter key from submitting form when inside GrapesJS
                form.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        const target = e.target;
                        
                        // Check if the target is inside a GrapesJS container
                        const isInGrapesJS = target.closest('.filament-grapesjs') || 
                                           target.closest('.grapesjs-wrapper') ||
                                           target.closest('.gjs-cv-canvas') ||
                                           target.closest('.gjs-frame-wrapper');
                        
                        if (isInGrapesJS) {
                            console.log('Enter key pressed in GrapesJS, preventing form submission');
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            return false;
                        }
                    }
                });
                
                // Additional prevention for specific GrapesJS containers
                grapesJSContainers.forEach(container => {
                    container.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.stopPropagation();
                        }
                    }, true); // Use capture phase
                });
            }
        });
    }
    
    // Run immediately
    preventGrapesJSFormSubmission();
    
    // Also run when new content is loaded (for dynamic forms)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                preventGrapesJSFormSubmission();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Livewire hook (if using Livewire)
    if (window.Livewire) {
        document.addEventListener('livewire:navigated', preventGrapesJSFormSubmission);
        document.addEventListener('livewire:load', preventGrapesJSFormSubmission);
    }
    
    // Alpine.js hook (since Filament uses Alpine)
    document.addEventListener('alpine:init', function() {
        setTimeout(preventGrapesJSFormSubmission, 100);
    });
    
    console.log('GrapesJS Form Fix initialized');
}); 