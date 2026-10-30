document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-signing-upload]').forEach(zone => {
        const input = zone.querySelector('input[type="file"]');
        const selection = zone.querySelector('[data-upload-selection]');
        const error = zone.querySelector('[data-upload-error]');
        const remove = zone.querySelector('[data-upload-remove]');
        const showError = message => {
            error.textContent = message;
            error.hidden = !message;
        };
        const refresh = () => {
            const file = input.files[0];
            selection.textContent = file ? file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB) — ready to upload' : '';
            remove.hidden = !file;
        };
        const validate = file => {
            if (!/\.(pdf|docx|jpe?g|png)$/i.test(file.name)) return 'Choose a PDF, DOCX, JPG or PNG file.';
            if (file.size > 10 * 1024 * 1024) return 'The signing packet must be 10 MB or smaller.';
            return '';
        };
        const selectFiles = files => {
            showError('');
            if (files.length !== 1) {
                showError('Select one complete signing packet. Multiple files cannot be uploaded together.');
                return;
            }
            const message = validate(files[0]);
            if (message) { showError(message); return; }
            try {
                const transfer = new DataTransfer();
                transfer.items.add(files[0]);
                input.files = transfer.files;
                refresh();
            } catch {
                showError('This browser could not select the file. Please use Choose signing packet.');
            }
        };
        input.addEventListener('change', () => {
            const message = input.files[0] ? validate(input.files[0]) : '';
            if (message) input.value = '';
            showError(message);
            refresh();
        });
        remove.addEventListener('click', () => {
            input.value = '';
            showError('');
            refresh();
            zone.focus();
        });
        zone.addEventListener('click', event => {
            if (!event.target.closest('input, button, label')) zone.focus();
        });
        zone.addEventListener('dragover', event => {
            event.preventDefault();
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy';
            zone.classList.add('is-dragging');
        });
        zone.addEventListener('dragleave', event => {
            if (!zone.contains(event.relatedTarget)) zone.classList.remove('is-dragging');
        });
        zone.addEventListener('drop', event => {
            event.preventDefault();
            zone.classList.remove('is-dragging');
            selectFiles(Array.from(event.dataTransfer?.files ?? []));
        });
        zone.addEventListener('paste', event => {
            const files = Array.from(event.clipboardData?.files ?? []);
            event.preventDefault();
            if (!files.length) {
                showError('No file was available from the clipboard. Try dragging the file here or use Choose signing packet.');
                return;
            }
            selectFiles(files);
        });
        input.form.addEventListener('reset', () => {
            input.value = '';
            showError('');
            refresh();
        });
    });
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
        const comments = form.querySelector('[name="extras_comments"]');
        const updateComments = () => {
            comments.required = form.querySelector('[name="extras_choice"]:checked')?.value === 'request';
        };
        form.querySelectorAll('[name="extras_choice"]').forEach(input => input.addEventListener('change', updateComments));
        updateComments();
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
