(() => {
    document.querySelectorAll('[data-improvement-photo-picker]').forEach((picker) => {
        const filesInput = picker.querySelector('[data-photo-files]');
        // Retain the native chooser on browsers without editable FileLists.
        try { filesInput.files = new DataTransfer().files; } catch { return; }
        const camera = picker.querySelector('[data-photo-camera]');
        const controls = picker.querySelector('[data-photo-controls]');
        const previews = picker.querySelector('[data-photo-previews]');
        const error = picker.querySelector('[data-photo-error]');
        let selected = [];
        let previewUrls = [];
        const render = () => {
            const transfer = new DataTransfer();
            selected.forEach(file => transfer.items.add(file));
            filesInput.files = transfer.files;
            previewUrls.forEach(url => URL.revokeObjectURL(url));
            previewUrls = [];
            previews.replaceChildren();
            selected.forEach((file, index) => {
                const item = document.createElement('div');
                item.style.width = '88px';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                previewUrls.push(img.src);
                img.alt = file.name;
                img.className = 'rounded';
                img.style.cssText = 'width:88px;height:68px;object-fit:cover';
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-link btn-sm p-0 d-block';
                remove.textContent = 'Remove';
                remove.setAttribute('aria-label', 'Remove ' + file.name);
                remove.addEventListener('click', () => {
                    selected.splice(index, 1);
                    error.textContent = '';
                    render();
                    (previews.querySelectorAll('button')[index] || controls.querySelector('button')).focus();
                });
                item.append(img, remove);
                previews.append(item);
            });
        };
        const add = (incoming) => {
            const errors = [];
            incoming.forEach(file => {
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    errors.push('Please choose JPG or PNG photos.');
                } else if (file.size > 10 * 1024 * 1024) {
                    errors.push('Each photo must be 10 MB or smaller.');
                } else if (selected.some(saved => saved.name === file.name && saved.size === file.size && saved.lastModified === file.lastModified)) {
                    // Ignore reselecting the same file.
                } else if (selected.length >= 5) {
                    errors.push('You can attach up to 5 photos. Remove one to add another.');
                } else selected.push(file);
            });
            error.textContent = [...new Set(errors)].join(' ');
            render();
        };
        controls.classList.replace('d-none', 'd-flex');
        filesInput.hidden = true;
        controls.querySelector('[data-photo-choose]').addEventListener('click', () => filesInput.click());
        controls.querySelector('[data-photo-take]').addEventListener('click', () => camera.click());
        const pasteButton = controls.querySelector('[data-photo-paste]');
        const pasteHelp = picker.querySelector('[data-photo-paste-help]');
        const showPasteHelp = (text) => {
            pasteHelp.textContent = text;
            pasteHelp.classList.remove('d-none');
            pasteHelp.classList.add('d-block');
        };
        const pasteHint = 'Copy a photo, then press Ctrl+V (Windows) or Command+V (Mac) here.';
        let pasteVersion = 0;
        let clipboardBusy = false;
        pasteButton.addEventListener('focus', () => showPasteHelp(pasteHint));
        // An editable target reliably receives native paste, including on HTTP.
        pasteButton.addEventListener('beforeinput', event => event.preventDefault());
        const pasteIcon = pasteButton.innerHTML;
        pasteButton.addEventListener('input', () => { pasteButton.innerHTML = pasteIcon; });
        filesInput.closest('form').addEventListener('paste', (event) => {
            let images = Array.from(event.clipboardData?.items || [])
                .filter(item => item.kind === 'file' && item.type.startsWith('image/'))
                .map(item => item.getAsFile()).filter(Boolean);
            if (!images.length) images = Array.from(event.clipboardData?.files || []).filter(file => file.type.startsWith('image/'));
            // Preserve ordinary text pasting into the note field.
            if (!images.length && !pasteButton.contains(event.target)) return;
            event.preventDefault();
            pasteVersion++;
            if (!images.length) {
                showPasteHelp('No image found. Copy the image itself, rather than its link.');
                return;
            }
            add(images);
            showPasteHelp(error.textContent ? 'Check the photo limits below.' : 'Photo pasted. Review the preview before sending.');
        });
        pasteButton.addEventListener('click', async () => {
            pasteButton.focus();
            if (clipboardBusy) return;
            if (!window.isSecureContext || !navigator.clipboard?.read) {
                showPasteHelp('Press Ctrl+V (Windows) or Command+V (Mac) now. This browser does not allow one-click clipboard access here.');
                return;
            }
            clipboardBusy = true;
            const version = ++pasteVersion;
            try {
                const items = await navigator.clipboard.read();
                const images = [];
                for (const item of items) {
                    const type = item.types.find(type => ['image/png', 'image/jpeg'].includes(type));
                    if (type) {
                        const blob = await item.getType(type);
                        images.push(new File([blob], 'pasted-photo-' + Date.now() + '-' + images.length + (type === 'image/png' ? '.png' : '.jpg'), {type}));
                    }
                }
                if (version !== pasteVersion) return;
                if (!images.length) {
                    showPasteHelp('No image found. Copy the image itself, rather than its link.');
                    return;
                }
                add(images);
                showPasteHelp(error.textContent ? 'Check the photo limits below.' : 'Photo pasted. Review the preview before sending.');
            } catch {
                if (version === pasteVersion) showPasteHelp(pasteHint);
            } finally {
                clipboardBusy = false;
            }
        });
        filesInput.addEventListener('change', () => add(Array.from(filesInput.files)));
        camera.addEventListener('change', () => {
            add(Array.from(camera.files));
            camera.value = '';
        });
        filesInput.closest('form').addEventListener('reset', () => {
            selected = [];
            pasteVersion++;
            pasteHelp.classList.add('d-none');
            pasteHelp.classList.remove('d-block');
            error.textContent = '';
            setTimeout(render, 0);
        });
    });
})();
