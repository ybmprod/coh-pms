document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-availability-endpoint]');
    if (!form) {
        return;
    }

    const venue = form.querySelector('#venue_id');
    const date = form.querySelector('#booking_date');
    const start = form.querySelector('#start_time');
    const end = form.querySelector('#end_time');
    const result = document.querySelector('#booking-live-result');
    const message = document.querySelector('#availability-message');
    const standardCharge = document.querySelector('#standard-charge');
    const ruleName = document.querySelector('#pricing-rule');
    const adjustment = document.querySelector('#adjustment-amount');
    const totalCharge = document.querySelector('#total-charge');
    const fields = [venue, date, start, end];
    let requestNumber = 0;

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }
        return `US$ ${Number(value).toFixed(2)}`;
    }

    function setLoadingState(isLoading) {
        if (!result) return;
        result.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        [standardCharge, ruleName, adjustment, totalCharge].forEach((el) => {
            if (!el) return;
            el.classList.toggle('skeleton', isLoading);
            if (isLoading) {
                el.textContent = 'US$ 00.00';
            }
        });
    }

    function showPrice(data) {
        setLoadingState(false);
        standardCharge.textContent = formatMoney(data.standard_charge);
        ruleName.textContent = data.rule_name || 'No matching rule';
        adjustment.textContent = formatMoney(data.adjustment_amount);
        totalCharge.textContent = formatMoney(data.total_charge);
    }

    async function checkAvailability() {
        fields.forEach((f) => f && f.removeAttribute('aria-invalid'));

        if (fields.some((field) => !field || !field.value)) {
            setLoadingState(false);
            message.innerHTML = '<i class="fi fi-sr-info" aria-hidden="true"></i> Select a venue, date, start time, and end time.';
            result.classList.remove('flash-success', 'flash-error');
            standardCharge.textContent = '-';
            ruleName.textContent = '-';
            adjustment.textContent = '-';
            totalCharge.textContent = '-';
            return;
        }

        if (start && end && start.value && end.value && end.value <= start.value) {
            setLoadingState(false);
            end.setAttribute('aria-invalid', 'true');
            result.classList.remove('flash-success');
            result.classList.add('flash-error');
            message.innerHTML = '<span class="status-indicator unavailable"><i class="fi fi-sr-cross-circle" aria-hidden="true"></i> End time must be later than start time.</span>';
            showPrice({});
            return;
        }

        const currentRequest = ++requestNumber;
        const endpoint = new URL(form.dataset.availabilityEndpoint, window.location.href);
        endpoint.searchParams.set('venue_id', venue.value);
        endpoint.searchParams.set('booking_date', date.value);
        endpoint.searchParams.set('start_time', start.value);
        endpoint.searchParams.set('end_time', end.value);

        message.innerHTML = '<span class="checking-indicator"><i class="fi fi-sr-spinner spin-anim" aria-hidden="true"></i> Checking availability & pricing...</span>';
        result.classList.remove('flash-success', 'flash-error');
        setLoadingState(true);

        try {
            const response = await fetch(endpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            if (currentRequest !== requestNumber) {
                return;
            }

            const isAvailable = data.available === true;
            result.classList.toggle('flash-success', isAvailable);
            result.classList.toggle('flash-error', !isAvailable);

            if (isAvailable) {
                message.innerHTML = `<span class="status-indicator available"><i class="fi fi-sr-check-circle" aria-hidden="true"></i> ${data.message || 'Slot is available!'}</span>`;
            } else {
                message.innerHTML = `<span class="status-indicator unavailable"><i class="fi fi-sr-cross-circle" aria-hidden="true"></i> ${data.message || 'Selected slot is unavailable.'}</span>`;
            }

            showPrice(data);
        } catch (error) {
            if (currentRequest !== requestNumber) {
                return;
            }
            message.innerHTML = '<span class="status-indicator unavailable"><i class="fi fi-sr-cross-circle" aria-hidden="true"></i> Could not check availability. Please try again.</span>';
            result.classList.remove('flash-success');
            result.classList.add('flash-error');
            showPrice({});
        }
    }

    fields.forEach((field) => {
        if (!field) return;
        field.addEventListener('input', checkAvailability);
        field.addEventListener('change', checkAvailability);
    });

    [start, end].forEach((field) => {
        if (field) {
            field.addEventListener('input', function () {
                if (this.value && this.value.length >= 5) {
                    this.blur();
                }
            });
        }
    });

    if (fields.every((field) => field && field.value)) {
        checkAvailability();
    }
});
