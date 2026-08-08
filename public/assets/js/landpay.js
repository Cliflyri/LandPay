(() => {
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