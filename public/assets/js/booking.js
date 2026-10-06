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

    function showPrice(data) {
        standardCharge.textContent = formatMoney(data.standard_charge);
        ruleName.textContent = data.rule_name || 'No matching rule';
        adjustment.textContent = formatMoney(data.adjustment_amount);
        totalCharge.textContent = formatMoney(data.total_charge);
    }

    async function checkAvailability() {
        if (fields.some((field) => !field.value)) {
            message.textContent = 'Select a venue, date, start time, and end time.';
            result.classList.remove('flash-success', 'flash-error');
            standardCharge.textContent = '-';
            ruleName.textContent = '-';
            adjustment.textContent = '-';
            totalCharge.textContent = '-';
            return;
        }

        const currentRequest = ++requestNumber;
        const endpoint = new URL(form.dataset.availabilityEndpoint, window.location.href);
        endpoint.searchParams.set('venue_id', venue.value);
        endpoint.searchParams.set('booking_date', date.value);
        endpoint.searchParams.set('start_time', start.value);
        endpoint.searchParams.set('end_time', end.value);
        message.textContent = 'Checking availability...';

        try {
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (currentRequest !== requestNumber) {
                return;
            }

            message.textContent = data.message || 'Availability could not be checked.';
            result.classList.toggle('flash-success', data.available === true);
            result.classList.toggle('flash-error', data.available !== true);
            showPrice(data);
        } catch (error) {
            if (currentRequest !== requestNumber) {
                return;
            }
            message.textContent = 'Availability could not be checked. Please try again.';
            result.classList.remove('flash-success');
            result.classList.add('flash-error');
            showPrice({});
        }
    }

    fields.forEach((field) => {
        field.addEventListener('input', checkAvailability);
        field.addEventListener('change', checkAvailability);
    });
});