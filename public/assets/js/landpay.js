(() => {
    document.querySelectorAll('.dashboard-actions-menu .dropdown').forEach((dropdown) => {
        const card = dropdown.closest('.dashboard-table-card');
        dropdown.addEventListener('show.bs.dropdown', () => card?.classList.add('dropdown-open'));
        dropdown.addEventListener('hidden.bs.dropdown', () => card?.classList.remove('dropdown-open'));
    });

    const header = document.querySelector('[data-site-header]');
    if (!header) return;

    const updateHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 12);
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    document.querySelectorAll('#landpayNavigation a[href*="#"]').forEach((link) => {
        link.addEventListener('click', () => {
            const nav = document.getElementById('landpayNavigation');
            if (nav?.classList.contains('show') && window.bootstrap) {
                window.bootstrap.Collapse.getOrCreateInstance(nav).hide();
            }
        });
    });

})();
    document.querySelectorAll('[data-template-preview]').forEach((button) => {
        button.addEventListener('click', () => {
            const preview = document.getElementById(button.dataset.templatePreview);
            preview?.classList.toggle('d-none');
            button.textContent = preview?.classList.contains('d-none') ? 'Preview HTML' : 'Hide preview';
        });
    });

document.querySelectorAll('[data-copy-command]').forEach((button) => {
    button.addEventListener('click', async () => {
        const text = button.closest('.copy-command')?.querySelector('[data-copy-text]')?.textContent?.trim();
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text);
            button.textContent = 'Copied';
            window.setTimeout(() => { button.textContent = 'Copy'; }, 1600);
        } catch (_) {
            window.prompt('Copy this command:', text);
        }
    });
});

if (window.bootstrap) {
    const activateSettingsTab = () => {
        const target = window.location.hash;
        const trigger = target ? document.querySelector(`[data-bs-target="${target}"]`) : null;
        if (trigger?.closest('#settingsTabs')) window.bootstrap.Tab.getOrCreateInstance(trigger).show();
    };
    activateSettingsTab();
    document.querySelectorAll('#settingsTabs [data-bs-toggle="tab"]').forEach((tab) => {
        tab.addEventListener('shown.bs.tab', () => history.replaceState(null, '', tab.dataset.bsTarget));
    });
}

document.querySelectorAll('[data-current-balance-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const group = button.dataset.currentBalanceToggle;
        const hiddenItems = document.querySelectorAll(`[data-current-balance-hidden="${group}"]`);
        const expanding = button.getAttribute('aria-expanded') !== 'true';
        hiddenItems.forEach((item) => item.classList.toggle('d-none', !expanding));
        button.setAttribute('aria-expanded', expanding ? 'true' : 'false');
        button.textContent = expanding ? button.dataset.expandedLabel : button.dataset.collapsedLabel;
    });
});

document.querySelectorAll('.dashboard-table-card .table-responsive').forEach((container) => {
    let startX = 0;
    let startScrollLeft = 0;
    let mouseDown = false;
    let dragged = false;

    container.addEventListener('mousedown', (event) => {
        if (event.button !== 0 || container.scrollWidth <= container.clientWidth) return;
        if (event.target.closest('a, button, input, select, textarea, [role="button"]')) return;
        startX = event.clientX;
        startScrollLeft = container.scrollLeft;
        mouseDown = true;
        dragged = false;
    });

    window.addEventListener('mousemove', (event) => {
        if (!mouseDown) return;
        const distance = event.clientX - startX;
        if (Math.abs(distance) < 5) return;
        dragged = true;
        container.classList.add('is-dragging');
        container.scrollLeft = startScrollLeft - distance;
        event.preventDefault();
    });

    window.addEventListener('mouseup', () => {
        mouseDown = false;
        container.classList.remove('is-dragging');
    });

    container.addEventListener('click', (event) => {
        if (!dragged) return;
        event.preventDefault();
        event.stopPropagation();
        dragged = false;
    }, true);
});
document.querySelectorAll('[data-list-filter-form]').forEach((form) => {
    const search = form.querySelector('input[name="search"]');
    const clearSearch = form.querySelector('[data-list-search-clear]');
    let timer;

    search?.addEventListener('input', () => {
        window.clearTimeout(timer);
        clearSearch?.classList.toggle('d-none', search.value.trim() === '');
        timer = window.setTimeout(() => form.requestSubmit(), 800);
    });

    clearSearch?.addEventListener('click', () => {
        window.clearTimeout(timer);
        search.value = '';
        clearSearch.classList.add('d-none');
        window.location.assign(form.dataset.clearUrl);
    });

    form.querySelectorAll('select').forEach((select) => select.addEventListener('change', () => {
        window.clearTimeout(timer);
        form.requestSubmit();
    }));
});

const adminStatusRoot = document.querySelector('[data-admin-status-url]');
if (adminStatusRoot) {
    let polling = false;
    const refreshAdminStatus = async () => {
        if (polling || document.hidden) return;
        polling = true;
        try {
            const notices = document.querySelector('[data-admin-notices]');
            const statusUrl = new URL(adminStatusRoot.dataset.adminStatusUrl, window.location.origin);
            if (notices) statusUrl.searchParams.set('notices', '1');
            const response = await fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) return;
            const status = await response.json();
            const messageText = [status.unread_messages ? status.unread_messages + ' unread' : '', status.starred_messages ? '★ ' + status.starred_messages : ''].filter(Boolean).join(' · ');
            document.querySelectorAll('[data-admin-message-badge]').forEach(badge => { badge.textContent = messageText; badge.classList.toggle('d-none', !messageText); });
            document.querySelectorAll('[data-admin-notice-link]').forEach(link => {
                link.classList.toggle('d-none', !status.open_notices);
                const badge = link.querySelector('[data-admin-notice-badge]');
                if (badge) badge.textContent = status.open_notices + ' open';
            });
            if (notices && notices.dataset.adminNoticesRevision !== status.notices_revision) notices.outerHTML = status.notices_html;
        } catch (_) {
            // Keep the existing display and try again later.
        } finally {
            polling = false;
        }
    };
    window.setInterval(refreshAdminStatus, 60000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshAdminStatus(); });
}

document.querySelectorAll('.secure-message-history-toggle').forEach((toggle) => {
    const target = document.querySelector(toggle.dataset.bsTarget);
    const label = toggle.querySelector('.history-label');
    if (!target || !label) return;
    target.addEventListener('show.bs.collapse', () => { label.textContent = 'Collapse older messages'; });
    target.addEventListener('hide.bs.collapse', () => { label.textContent = toggle.dataset.showLabel; });
});
