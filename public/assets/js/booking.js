document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-availability-endpoint]');
    if (!form) {
        return;
    }

    const venue = form.querySelector('#venue_id');
    const date = form.querySelector('#booking_date');
    const start = form.querySelector('#start_time');
    const end = form.querySelector('#end_time');
    const startPeriodBadge = form.querySelector('#start-time-period');
    const endPeriodBadge = form.querySelector('#end-time-period');
    const result = document.querySelector('#booking-live-result');
    const message = document.querySelector('#availability-message');
    const standardCharge = document.querySelector('#standard-charge');
    const ruleName = document.querySelector('#pricing-rule');
    const adjustment = document.querySelector('#adjustment-amount');
    const totalCharge = document.querySelector('#total-charge');
    const eventType = form.querySelector('#event_type');
    const eventTypeCharge = document.querySelector('#event-type-charge');
    const eventTypeRow = document.querySelector('#event-type-charge-row');

    const heroHeader = document.querySelector('#booking-venue-hero');
    const heroEyebrow = document.querySelector('#booking-hero-eyebrow');
    const heroTitle = document.querySelector('#booking-request-title');
    const heroSubtitle = document.querySelector('#booking-hero-subtitle');

    const fields = [venue, date, start, end];
    let requestNumber = 0;

    function formatMoney(value) {
        if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
            return '-';
        }
        const num = Number(value);
        if (num < 0) {
            return `-US$ ${Math.abs(num).toFixed(2)}`;
        }
        return `US$ ${num.toFixed(2)}`;
    }

    function formatTimeWithMeridiem(timeValue) {
        if (!timeValue || !/^\d{2}:\d{2}/.test(timeValue)) {
            return '';
        }
        const [hStr, mStr] = timeValue.split(':');
        const h = parseInt(hStr, 10);
        const period = h >= 12 ? 'PM' : 'AM';
        return `${hStr}:${mStr} ${period}`;
    }

    function updateTimePeriodBadges() {
        const startVal = start ? start.value : '';
        const endVal = end ? end.value : '';
        const isOvernight = Boolean(startVal && endVal && endVal < startVal && startVal >= '18:00' && endVal <= '09:00');

        if (startPeriodBadge) {
            if (startVal) {
                startPeriodBadge.textContent = formatTimeWithMeridiem(startVal);
                startPeriodBadge.style.display = 'inline-block';
            } else {
                startPeriodBadge.style.display = 'none';
            }
        }

        if (endPeriodBadge) {
            if (endVal) {
                const label = formatTimeWithMeridiem(endVal);
                endPeriodBadge.textContent = isOvernight ? `${label} (Next day)` : label;
                endPeriodBadge.style.display = 'inline-block';
            } else {
                endPeriodBadge.style.display = 'none';
            }
        }
    }

    function isValidTimeWindow(startVal, endVal) {
        if (!startVal || !endVal) {
            return { valid: true, overnight: false };
        }
        if (endVal > startVal) {
            return { valid: true, overnight: false };
        }
        if (endVal === startVal) {
            return {
                valid: false,
                overnight: false,
                reason: 'Start time and end time cannot be the same.'
            };
        }
        // Cross-midnight (endVal < startVal): only valid when starting in the evening/night (18:00–23:59 PM) and ending the next morning (00:00–09:00 AM)
        if (startVal >= '18:00' && endVal <= '09:00') {
            return { valid: true, overnight: true };
        }
        return {
            valid: false,
            overnight: false,
            reason: 'End time cannot be earlier than start time. Overnight bookings are only allowed from evening (18:00 PM) into the next morning (up to 09:00 AM).'
        };
    }

    function setLoadingState(isLoading) {
        if (!result) return;
        result.setAttribute('aria-busy', isLoading ? 'true' : 'false');
        [standardCharge, ruleName, adjustment, totalCharge].forEach((el) => {
            if (!el) return;
            el.classList.toggle('skeleton', isLoading);
        });
    }

    function getSelectedVenueOption() {
        if (!venue || venue.selectedIndex < 0) {
            return null;
        }
        const opt = venue.options[venue.selectedIndex];
        return opt && opt.value ? opt : null;
    }

    function getClientEventTypeExtra(baseStandardPrice) {
        if (!eventType || eventType.selectedIndex < 0) {
            return 0;
        }
        const opt = eventType.options[eventType.selectedIndex];
        if (!opt || !opt.value) {
            return 0;
        }
        const chargeType = opt.dataset.chargeType || 'Fixed Amount';
        const chargeVal = Number(opt.dataset.chargeVal || 0);
        if (Number.isNaN(chargeVal) || chargeVal <= 0) {
            return 0;
        }
        if (chargeType === 'Percentage') {
            return Math.round(baseStandardPrice * (chargeVal / 100) * 100) / 100;
        }
        return Math.round(chargeVal * 100) / 100;
    }

    function updateVenueHeroBackground() {
        if (!heroHeader) {
            return;
        }
        const opt = getSelectedVenueOption();
        if (!opt) {
            heroHeader.classList.remove('is-venue-selected', 'has-no-image');
            heroHeader.style.backgroundImage = '';
            if (heroEyebrow) heroEyebrow.textContent = 'Book a Venue';
            if (heroTitle) heroTitle.textContent = 'Venue Booking Request';
            if (heroSubtitle) heroSubtitle.textContent = 'Select a venue and provide event details to submit a request.';
            return;
        }

        const vName = opt.dataset.name || opt.textContent.trim();
        const vLocation = opt.dataset.location || '';
        const vType = opt.dataset.type || 'Venue';
        const vCapacity = opt.dataset.capacity ? `${Number(opt.dataset.capacity).toLocaleString()} capacity` : '';
        const vImage = opt.dataset.image || '';

        heroHeader.classList.add('is-venue-selected');
        if (vImage) {
            heroHeader.classList.remove('has-no-image');
            heroHeader.style.backgroundImage = `linear-gradient(180deg, rgba(8, 45, 70, 0.25) 0%, rgba(8, 45, 70, 0.58) 48%, rgba(7, 38, 60, 0.94) 100%), url('${vImage}')`;
        } else {
            heroHeader.classList.add('has-no-image');
            heroHeader.style.backgroundImage = '';
        }

        if (heroEyebrow) {
            heroEyebrow.textContent = vType;
        }
        if (heroTitle) {
            heroTitle.textContent = vName;
        }
        if (heroSubtitle) {
            const parts = [vLocation, vCapacity].filter(Boolean);
            heroSubtitle.textContent = parts.length > 0
                ? parts.join(' • ')
                : 'Provide event details below to complete your reservation request.';
        }
    }

    function showPrice(data) {
        setLoadingState(false);
        standardCharge.textContent = formatMoney(data.standard_charge);
        if (eventTypeRow && eventTypeCharge) {
            const etc = Number(data.event_type_charge || 0);
            const hasEventSelected = Boolean(eventType && eventType.value);
            if (etc > 0 || (hasEventSelected && data.standard_charge !== null && data.standard_charge !== undefined)) {
                eventTypeRow.style.display = 'flex';
                eventTypeCharge.textContent = etc > 0 ? `+${formatMoney(etc)}` : 'US$ 0.00';
            } else {
                eventTypeRow.style.display = 'none';
                eventTypeCharge.textContent = '-';
            }
        }
        ruleName.textContent = data.rule_name || 'Standard rate applied';
        adjustment.textContent = formatMoney(data.adjustment_amount);
        totalCharge.textContent = formatMoney(data.total_charge);
    }

    function updateInstantClientEstimate() {
        const venueOpt = getSelectedVenueOption();
        if (!venueOpt) {
            setLoadingState(false);
            if (eventTypeRow) eventTypeRow.style.display = 'none';
            standardCharge.textContent = '-';
            ruleName.textContent = '-';
            adjustment.textContent = '-';
            totalCharge.textContent = '-';
            return false;
        }

        const basePrice = Number(venueOpt.dataset.price || 0);
        const extraCharge = getClientEventTypeExtra(basePrice);
        const runningTotal = Math.max(0, basePrice + extraCharge);

        showPrice({
            standard_charge: basePrice,
            event_type_charge: extraCharge,
            rule_name: 'Pending date & time selection',
            adjustment_amount: 0,
            total_charge: runningTotal
        });
        return true;
    }

    async function checkAvailability() {
        fields.forEach((f) => f && f.removeAttribute('aria-invalid'));
        updateVenueHeroBackground();
        updateTimePeriodBadges();

        const hasAllScheduleFields = fields.every((field) => field && field.value);

        if (!hasAllScheduleFields) {
            requestNumber++;
            const hasVenue = updateInstantClientEstimate();
            result.classList.remove('flash-success', 'flash-error');
            if (hasVenue) {
                message.innerHTML = '<i class="fi fi-sr-info" aria-hidden="true"></i> Standard charge loaded. Select booking date, start time, and end time to check slot availability and pricing rules.';
            } else {
                message.innerHTML = '<i class="fi fi-sr-info" aria-hidden="true"></i> Select a venue, date, start time, and end time.';
            }
            return;
        }

        const timeWindow = isValidTimeWindow(start.value, end.value);
        if (!timeWindow.valid) {
            requestNumber++;
            setLoadingState(false);
            end.setAttribute('aria-invalid', 'true');
            result.classList.remove('flash-success');
            result.classList.add('flash-error');
            message.innerHTML = `<span class="status-indicator unavailable"><i class="fi fi-sr-cross-circle" aria-hidden="true"></i> ${timeWindow.reason}</span>`;
            updateInstantClientEstimate();
            return;
        }

        // Immediately reflect venue + event type base estimate while AJAX fetches rule adjustments
        updateInstantClientEstimate();

        const isOvernight = timeWindow.overnight;
        const currentRequest = ++requestNumber;
        const endpoint = new URL(form.dataset.availabilityEndpoint, window.location.href);
        endpoint.searchParams.set('venue_id', venue.value);
        endpoint.searchParams.set('booking_date', date.value);
        endpoint.searchParams.set('start_time', start.value);
        endpoint.searchParams.set('end_time', end.value);
        if (eventType && eventType.value) {
            endpoint.searchParams.set('event_type', eventType.value);
        }

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

            const overnightDetail = isOvernight
                ? ` (${formatTimeWithMeridiem(start.value)} to ${formatTimeWithMeridiem(end.value)} next day)`
                : '';
            if (isAvailable) {
                message.innerHTML = `<span class="status-indicator available"><i class="fi fi-sr-check-circle" aria-hidden="true"></i> ${(data.message || 'Slot is available!') + overnightDetail}</span>`;
            } else {
                message.innerHTML = `<span class="status-indicator unavailable"><i class="fi fi-sr-cross-circle" aria-hidden="true"></i> ${(data.message || 'Selected slot is unavailable.') + overnightDetail}</span>`;
            }

            showPrice(data);
        } catch (error) {
            if (currentRequest !== requestNumber) {
                return;
            }
            message.innerHTML = '<span class="status-indicator unavailable"><i class="fi fi-sr-cross-circle" aria-hidden="true"></i> Could not check availability. Please try again.</span>';
            result.classList.remove('flash-success');
            result.classList.add('flash-error');
            updateInstantClientEstimate();
        }
    }

    fields.forEach((field) => {
        if (!field) return;
        field.addEventListener('input', checkAvailability);
        field.addEventListener('change', checkAvailability);
    });

    if (eventType) {
        eventType.addEventListener('input', checkAvailability);
        eventType.addEventListener('change', checkAvailability);
    }

    // Trigger initial render on load (handles preselected ?venue_id= or prefilled fields)
    checkAvailability();
});
