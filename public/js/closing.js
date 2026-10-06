document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-closing-owners]').forEach(container => {
        const list = container.querySelector('[data-owner-list]');
        const template = container.querySelector('template');
        let next = Math.max(-1, ...Array.from(list.querySelectorAll('input[name]'), input => Number(input.name.match(/owners\[(\d+)\]/)?.[1] ?? -1))) + 1;
        container.querySelector('[data-add-owner]').addEventListener('click', () => {
            if (list.querySelectorAll('[data-owner]').length >= 20) return;
            const holder = document.createElement('div');
            holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(next++));
            const owner = holder.firstElementChild;
            list.appendChild(owner);
            owner.querySelector('input')?.focus();
        });
        list.addEventListener('click', event => {
            if (event.target.closest('[data-remove-owner]') && list.querySelectorAll('[data-owner]').length > 1) {
                event.target.closest('[data-owner]').remove();
            }
        });
    });
    document.querySelectorAll('[data-combined-closing]').forEach(form => {
        form.addEventListener('invalid', event => {
            const section = event.target.closest('details');
            if (section) section.open = true;
        }, true);
        const beneficiary = form.querySelector('[name="beneficiary"]');
        beneficiary.addEventListener('change', () => {
            if (beneficiary.value === 'yes') {
                form.querySelector('[name="extras_choice"][value="request"]').checked = true;
            }
        });
    });
    document.querySelectorAll('[data-vesting-modal]').forEach(modal => {
        // Keep modal outside collapsible/stacking containers and load the PDF only when requested.
        document.body.appendChild(modal);
        modal.addEventListener('show.bs.modal', () => {
            const frame = modal.querySelector('iframe');
            if (!frame.getAttribute('src')) frame.src = frame.dataset.pdfSrc;
        });
    });
    document.querySelectorAll('[data-closing-reopen]').forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            const target = document.getElementById(button.dataset.closingReopen);
            for (let node = target; node; node = node.parentElement) {
                if (node.tagName === 'DETAILS') node.open = true;
            }
            target.querySelector('textarea')?.focus();
        });
    });
    // Open the enclosing panel when arriving via a dashboard or message deep link.
    function reveal() {
        const target = document.getElementById(location.hash.slice(1));
        if (!target) return;
        for (let node = target; node; node = node.parentElement) {
            if (node.tagName === 'DETAILS') node.open = true;
        }
    }
    reveal();
    window.addEventListener('hashchange', reveal);
});
