(() => {
    document.querySelectorAll('[data-dashboard-reminder]').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const cell = form.closest('td');
            if (cell.dataset.reminderBusy === '1') return;
            if (!window.confirm(form.dataset.reminderConfirm)) return;
            const data = new FormData(form);
            const buttons = cell.querySelectorAll('[data-dashboard-reminder] button');
            const output = form.closest('tr').querySelector('[data-reminder-result]');
            const toggle = cell.querySelector('[data-bs-toggle="dropdown"]');
            const show = (message, level) => {
                output.hidden = false;
                output.className = 'small mt-2 text-wrap text-' + level;
                output.textContent = message;
            };
            cell.dataset.reminderBusy = '1';
            buttons.forEach(button => { button.disabled = true; });
            if (toggle && window.bootstrap) bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            show('Sending...', 'muted');
            try {
                const response = await fetch(form.action, {
                    method: 'POST', body: data, credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                if (result.message && ['success', 'warning', 'danger'].includes(result.level)) {
                    show(result.message, result.level);
                } else if ([401, 419].includes(response.status)) {
                    show('Session expired. Reload and sign in before sending.', 'danger');
                } else if (response.status === 429) {
                    show('Too many requests. Wait before trying again.', 'warning');
                } else {
                    show('Send outcome could not be confirmed. Check reminder history before retrying.', 'warning');
                }
            } catch (error) {
                show('Connection interrupted. Check reminder history before retrying; a message may have been sent.', 'warning');
            } finally {
                cell.dataset.reminderBusy = '0';
                buttons.forEach(button => { button.disabled = false; });
                toggle?.focus({ preventScroll: true });
            }
        });
    });
})();
