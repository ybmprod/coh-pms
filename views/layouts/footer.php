<?php
declare(strict_types=1);
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <p>&copy; <?php echo date('Y'); ?> City of Harare Municipal Property Management System</p>
        <p>Department of Housing &amp; Community Services &bull; Harare, Zimbabwe</p>
    </div>
</footer>

<dialog id="coh-confirm-dialog" class="confirm-dialog" aria-labelledby="coh-confirm-title" aria-describedby="coh-confirm-desc">
    <h2 id="coh-confirm-title">Confirm action</h2>
    <p id="coh-confirm-desc">Are you sure you want to proceed with this action?</p>
    <div class="button-row" style="justify-content: flex-end; margin-top: 20px;">
        <button type="button" class="button ghost" id="coh-confirm-cancel">Keep current state</button>
        <button type="button" class="button primary" id="coh-confirm-accept">Confirm</button>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Accessible Password Visibility Toggle (universal across Chrome, Firefox, Edge, Safari)
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.dataset.pwToggleInitialized === 'true') return;
        input.dataset.pwToggleInitialized = 'true';

        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        input.style.width = '100%';
        input.style.paddingRight = '44px';

        const eyeIcon = `<i class="fi fi-sr-eye" aria-hidden="true" style="color: var(--primary-dark); font-size: 16px;"></i>`;
        const eyeOffIcon = `<i class="fi fi-sr-eye-crossed" aria-hidden="true" style="color: var(--primary-dark); font-size: 16px;"></i>`;

        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = eyeIcon;
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '10px';
        toggleBtn.style.background = 'transparent';
        toggleBtn.style.border = 'none';
        toggleBtn.style.borderRadius = '6px';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.padding = '4px';
        toggleBtn.style.display = 'flex';
        toggleBtn.style.alignItems = 'center';
        toggleBtn.style.justifyContent = 'center';
        toggleBtn.setAttribute('aria-label', 'Show password');
        toggleBtn.setAttribute('aria-pressed', 'false');

        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (input.type === 'password') {
                input.type = 'text';
                toggleBtn.innerHTML = eyeOffIcon;
                toggleBtn.setAttribute('aria-label', 'Hide password');
                toggleBtn.setAttribute('aria-pressed', 'true');
            } else {
                input.type = 'password';
                toggleBtn.innerHTML = eyeIcon;
                toggleBtn.setAttribute('aria-label', 'Show password');
                toggleBtn.setAttribute('aria-pressed', 'false');
            }
        });

        wrapper.appendChild(toggleBtn);
    });

    // 2. Scroll Position Restoration
    const currentPath = window.location.search.split('#')[0] || 'default';
    const savedScroll = sessionStorage.getItem('scrollPos_' + currentPath);
    if (savedScroll) {
        window.scrollTo({ top: parseInt(savedScroll, 10), behavior: 'instant' });
        sessionStorage.removeItem('scrollPos_' + currentPath);
    }

    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('scrollPos_' + currentPath, window.scrollY);
    });

    // 3. Top-Layer Confirmation Modal & Form Submit Loading State
    const dialog = document.getElementById('coh-confirm-dialog');
    const dialogTitle = document.getElementById('coh-confirm-title');
    const dialogDesc = document.getElementById('coh-confirm-desc');
    const cancelBtn = document.getElementById('coh-confirm-cancel');
    const acceptBtn = document.getElementById('coh-confirm-accept');
    let pendingForm = null;

    if (dialog && cancelBtn && acceptBtn) {
        cancelBtn.addEventListener('click', () => {
            pendingForm = null;
            dialog.close();
        });

        acceptBtn.addEventListener('click', () => {
            if (pendingForm) {
                pendingForm.dataset.confirmed = 'true';
                dialog.close();
                pendingForm.requestSubmit();
            }
        });
    }

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) return;

            const confirmMsg = form.dataset.confirm;
            if (confirmMsg && form.dataset.confirmed !== 'true' && dialog && typeof dialog.showModal === 'function') {
                e.preventDefault();
                pendingForm = form;
                dialogTitle.textContent = form.dataset.confirmTitle || 'Confirm action';
                dialogDesc.textContent = confirmMsg;
                dialog.showModal();
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.dataset.processing) {
                submitBtn.dataset.processing = "true";
                submitBtn.setAttribute('aria-busy', 'true');
                const width = submitBtn.offsetWidth;
                submitBtn.style.minWidth = width + 'px';
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span style="display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="fi fi-sr-spinner spin-anim" aria-hidden="true"></i>
                    Processing...
                </span>`;
            }
        });
    });
});
</script>
