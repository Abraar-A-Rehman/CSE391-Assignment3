

document.addEventListener('DOMContentLoaded', () => {
    const bookingForm = document.getElementById('bookingForm');
    const dateInput = document.getElementById('appointment_date');
    const mechanicSelect = document.getElementById('mechanic_id');
    const mechanicsGrid = document.getElementById('mechanicsGrid');
    const statusBanner = document.getElementById('statusBanner');

    // Help Modal Controls
    const helpBtn = document.getElementById('openHelpBtn');
    const closeHelpBtn = document.getElementById('closeHelpBtn');
    const helpModal = document.getElementById('helpModal');

    if (helpBtn && helpModal) {
        helpBtn.addEventListener('click', () => helpModal.classList.remove('hidden'));
    }
    if (closeHelpBtn && helpModal) {
        closeHelpBtn.addEventListener('click', () => helpModal.classList.add('hidden'));
    }

    function showBanner(type, message) {
        if (!statusBanner) return;
        statusBanner.className = 'mb-6 p-4 rounded-xl border text-sm font-medium flex items-start gap-3 ';
        if (type === 'error') {
            statusBanner.className += 'bg-red-950/70 border-red-500/50 text-red-200';
        } else if (type === 'warning') {
            statusBanner.className += 'bg-amber-950/70 border-amber-500/50 text-amber-200';
        } else {
            statusBanner.className += 'bg-emerald-950/70 border-emerald-500/50 text-emerald-200';
        }
        statusBanner.innerHTML = `<span>${message}</span>`;
        statusBanner.classList.remove('hidden');
    }

    // Fetch live mechanic availability from api.php whenever date changes
    async function loadMechanicsForDate(dateStr) {
        if (!mechanicsGrid || !mechanicSelect) return;
        try {
            const res = await fetch(`api.php?action=availability&date=${encodeURIComponent(dateStr)}`);
            const data = await res.json();
            if (data.status !== 'success') return;

            const currentVal = mechanicSelect.value;
            mechanicSelect.innerHTML = '<option value="">-- Select Desired Senior Mechanic --</option>';
            mechanicsGrid.innerHTML = '';

            data.mechanics.forEach(m => {
                const free = parseInt(m.free_slots, 10);
                const max = parseInt(m.max_daily_slots, 10);
                const booked = parseInt(m.booked_slots, 10);
                const isFull = free <= 0;

                // Populate <select>
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.disabled = isFull;
                opt.textContent = `${m.name} (${m.specialty}) — ${isFull ? 'FULL (0 Free Places)' : free + ' of ' + max + ' Free Places'}`;
                if (String(m.id) === String(currentVal) && !isFull) {
                    opt.selected = true;
                }
                mechanicSelect.appendChild(opt);

                // Populate visual card
                const card = document.createElement('div');
                card.className = `mechanic-card rounded-xl border border-slate-700/80 bg-slate-800/70 p-4 cursor-pointer ${isFull ? 'mechanic-full' : ''} ${String(m.id) === String(mechanicSelect.value) ? 'selected' : ''}`;
                card.innerHTML = `
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div>
                            <h4 class="font-bold text-white text-base">${m.name}</h4>
                            <p class="text-xs text-slate-400">${m.specialty}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold ${isFull ? 'bg-red-500/20 text-red-300 border border-red-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'}">
                            ${isFull ? 'Occupied (0 Free)' : `${free} Free Place${free === 1 ? '' : 's'}`}
                        </span>
                    </div>
                    <div class="w-full bg-slate-700/60 h-2 rounded-full overflow-hidden my-2">
                        <div class="${isFull ? 'bg-red-500' : 'bg-amber-400'} h-full transition-all" style="width: ${(booked / max) * 100}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-400 mt-1">
                        <span>${m.experience_years} yrs exp.</span>
                        <span>Assigned: ${booked} / ${max} cars</span>
                    </div>
                `;
                if (!isFull) {
                    card.addEventListener('click', () => {
                        mechanicSelect.value = m.id;
                        document.querySelectorAll('.mechanic-card').forEach(c => c.classList.remove('selected'));
                        card.classList.add('selected');
                    });
                }
                mechanicsGrid.appendChild(card);
            });
        } catch (err) {
            console.error('Failed to load mechanic availability:', err);
        }
    }

    // Live check if client already has an appointment on the selected date
    async function checkDuplicateClientBooking() {
        if (!dateInput) return;
        const phone = document.getElementById('phone')?.value.trim() || '';
        const license = document.getElementById('car_license_no')?.value.trim() || '';
        const dateStr = dateInput.value;
        if (!dateStr || (phone.length < 7 && license.length < 3)) return;

        try {
            const url = `api.php?action=check_client&date=${encodeURIComponent(dateStr)}&phone=${encodeURIComponent(phone)}&car_license_no=${encodeURIComponent(license)}`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.has_duplicate && data.existing) {
                showBanner('warning', `Notice: Our records show you already have an appointment scheduled on ${dateStr} with Senior Mechanic ${data.existing.mechanic_name}. Submitting another booking on the same date will be declined.`);
            }
        } catch (e) {
            // Ignore transient network check errors
        }
    }

    if (dateInput) {
        dateInput.addEventListener('change', () => {
            loadMechanicsForDate(dateInput.value);
            checkDuplicateClientBooking();
        });
        loadMechanicsForDate(dateInput.value);
    }

    ['phone', 'car_license_no'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('blur', checkDuplicateClientBooking);
    });

    // Client-side validation helper
    function validateFormClientSide() {
        let valid = true;
        const setFieldError = (id, msg) => {
            const input = document.getElementById(id);
            const errEl = document.getElementById(id + '_error');
            if (msg) {
                valid = false;
                if (input) input.classList.add('input-error');
                if (errEl) {
                    errEl.textContent = msg;
                    errEl.classList.remove('hidden');
                }
            } else {
                if (input) input.classList.remove('input-error');
                if (errEl) {
                    errEl.textContent = '';
                    errEl.classList.add('hidden');
                }
            }
        };

        const nameVal = document.getElementById('client_name').value.trim();
        const addrVal = document.getElementById('address').value.trim();
        const phoneVal = document.getElementById('phone').value.trim();
        const licVal = document.getElementById('car_license_no').value.trim();
        const engVal = document.getElementById('car_engine_no').value.trim();
        const dateVal = document.getElementById('appointment_date').value.trim();
        const mechVal = document.getElementById('mechanic_id').value.trim();

        setFieldError('client_name', nameVal.length < 2 ? 'Client Name cannot be empty.' : '');
        setFieldError('address', addrVal.length < 4 ? 'Address cannot be empty.' : '');
        setFieldError('phone', !/^[0-9]{7,15}$/.test(phoneVal) ? 'Phone must not be empty and must contain only numbers (7-15 digits).' : '');
        setFieldError('car_license_no', licVal === '' ? 'Car License Number cannot be empty.' : '');
        setFieldError('car_engine_no', !/^[0-9]+$/.test(engVal) ? 'Car Engine Number must not be empty and must contain only numbers.' : '');
        setFieldError('appointment_date', !/^\d{4}-\d{2}-\d{2}$/.test(dateVal) ? 'Please select a valid Appointment Date.' : '');
        setFieldError('mechanic_id', mechVal === '' ? 'You must select an available mechanic from the list.' : '');

        return valid;
    }

    if (bookingForm) {
        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!validateFormClientSide()) {
                showBanner('error', 'Please fix the highlighted validation errors before submitting your appointment.');
                return;
            }

            const payload = {
                client_name: document.getElementById('client_name').value.trim(),
                address: document.getElementById('address').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                car_license_no: document.getElementById('car_license_no').value.trim(),
                car_engine_no: document.getElementById('car_engine_no').value.trim(),
                appointment_date: document.getElementById('appointment_date').value.trim(),
                mechanic_id: document.getElementById('mechanic_id').value.trim()
            };

            try {
                const res = await fetch('api.php?action=book', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                if (res.ok && result.status === 'success') {
                    showBanner('success', result.message);
                    bookingForm.reset();
                    document.getElementById('appointment_date').value = payload.appointment_date;
                    loadMechanicsForDate(payload.appointment_date);
                } else {
                    showBanner('error', result.message || 'Unable to book appointment.');
                    loadMechanicsForDate(payload.appointment_date);
                }
            } catch (err) {
                showBanner('error', 'Server communication error. Please verify your PHP/MySQL server is running.');
            }
        });
    }
});
