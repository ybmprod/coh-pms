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
    <div class="button-row dialog-actions">
        <button type="button" class="button ghost" id="coh-confirm-cancel">Keep current state</button>
        <button type="button" class="button primary" id="coh-confirm-accept">Confirm</button>
    </div>
</dialog>

<script nonce="<?php echo e(csp_nonce()); ?>">
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-image-fallback]').forEach(function (img) {
        var showFallback = function () {
            img.style.display = 'none';
            if (img.nextElementSibling) {
                img.nextElementSibling.style.display = 'flex';
            }
        };
        if (img.complete && img.naturalWidth === 0) {
            showFallback();
        } else {
            img.addEventListener('error', showFallback);
        }
    });

    document.querySelectorAll('[data-select-on-click]').forEach(function (input) {
        input.addEventListener('click', function () {
            input.select();
        });
    });

    document.querySelectorAll('input[type="password"]').forEach(function (input) {
        if (input.dataset.pwToggleInitialized === 'true') return;
        input.dataset.pwToggleInitialized = 'true';

        var wrapper = document.createElement('div');
        wrapper.className = 'pw-field';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'pw-toggle';
        toggleBtn.innerHTML = '<i class="fi fi-sr-eye" aria-hidden="true"></i>';
        toggleBtn.setAttribute('aria-label', 'Show password');
        toggleBtn.setAttribute('aria-pressed', 'false');

        toggleBtn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggleBtn.innerHTML = '<i class="fi ' + (show ? 'fi-sr-eye-crossed' : 'fi-sr-eye') + '" aria-hidden="true"></i>';
            toggleBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            toggleBtn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });

        wrapper.appendChild(toggleBtn);
    });

    document.querySelectorAll('[data-print]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });

    var currentPath = window.location.search.split('#')[0] || 'default';
    var savedScroll = sessionStorage.getItem('scrollPos_' + currentPath);
    if (savedScroll) {
        window.scrollTo({ top: parseInt(savedScroll, 10), behavior: 'instant' });
        sessionStorage.removeItem('scrollPos_' + currentPath);
    }

    window.addEventListener('beforeunload', function () {
        sessionStorage.setItem('scrollPos_' + currentPath, window.scrollY);
    });

    var dialog = document.getElementById('coh-confirm-dialog');
    var dialogTitle = document.getElementById('coh-confirm-title');
    var dialogDesc = document.getElementById('coh-confirm-desc');
    var cancelBtn = document.getElementById('coh-confirm-cancel');
    var acceptBtn = document.getElementById('coh-confirm-accept');
    var pendingForm = null;

    if (dialog && cancelBtn && acceptBtn) {
        cancelBtn.addEventListener('click', function () {
            pendingForm = null;
            dialog.close();
        });

        acceptBtn.addEventListener('click', function () {
            if (pendingForm) {
                pendingForm.dataset.confirmed = 'true';
                dialog.close();
                pendingForm.requestSubmit();
            }
        });
    }

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) return;

            var confirmMsg = form.dataset.confirm;
            if (confirmMsg && form.dataset.confirmed !== 'true' && dialog && typeof dialog.showModal === 'function') {
                event.preventDefault();
                pendingForm = form;
                dialogTitle.textContent = form.dataset.confirmTitle || 'Confirm action';
                dialogDesc.textContent = confirmMsg;
                dialog.showModal();
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.dataset.processing) {
                submitBtn.dataset.processing = 'true';
                submitBtn.setAttribute('aria-busy', 'true');
                submitBtn.style.minWidth = submitBtn.offsetWidth + 'px';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="btn-processing"><i class="fi fi-sr-spinner spin-anim" aria-hidden="true"></i> Processing...</span>';
            }
        });
    });
});
</script>
