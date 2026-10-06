<?php
declare(strict_types=1);
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <p>&copy; <?php echo date('Y'); ?> City of Harare</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        input.style.width = '100%';
        input.style.paddingRight = '40px';
        
        const eyeIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
        const eyeOffIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = eyeIcon;
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '12px';
        toggleBtn.style.background = 'transparent';
        toggleBtn.style.border = 'none';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.opacity = '0.6';
        toggleBtn.style.padding = '0';
        toggleBtn.style.display = 'flex';
        toggleBtn.style.alignItems = 'center';
        toggleBtn.style.justifyContent = 'center';
        toggleBtn.style.transition = 'opacity 150ms ease';
        toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
        
        toggleBtn.addEventListener('mouseenter', () => toggleBtn.style.opacity = '1');
        toggleBtn.addEventListener('mouseleave', () => {
            if (input.type === 'password') toggleBtn.style.opacity = '0.6';
        });

        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (input.type === 'password') {
                input.type = 'text';
                toggleBtn.innerHTML = eyeOffIcon;
                toggleBtn.style.opacity = '1';
            } else {
                input.type = 'password';
                toggleBtn.innerHTML = eyeIcon;
                toggleBtn.style.opacity = '0.6';
            }
        });
        
        wrapper.appendChild(toggleBtn);
    });
});
// Smart UI Enhancer
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.search.split('#')[0] || 'default';
    
    // 1. Restore scroll position automatically
    const savedScroll = sessionStorage.getItem('scrollPos_' + currentPath);
    if (savedScroll) {
        window.scrollTo({ top: parseInt(savedScroll, 10), behavior: 'instant' });
        sessionStorage.removeItem('scrollPos_' + currentPath);
    }

    // Save scroll on unload (page reload or form submit)
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('scrollPos_' + currentPath, window.scrollY);
    });

    // 2. Smart Form Button Animations
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Let the browser handle HTML5 validation first
            if (!form.checkValidity()) return;
            
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.dataset.processing) {
                submitBtn.dataset.processing = "true";
                submitBtn.style.transition = 'all 0.2s ease';
                submitBtn.style.opacity = '0.85';
                submitBtn.style.pointerEvents = 'none';
                
                // Keep the button width stable to prevent layout shifting
                const width = submitBtn.offsetWidth;
                submitBtn.style.minWidth = width + 'px';
                
                submitBtn.innerHTML = `<span style="display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin-anim"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
                    Processing...
                </span>`;
            }
        });
    });
});
</script>


