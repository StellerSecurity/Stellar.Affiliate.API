(() => {
    const sidebar = document.querySelector('[data-portal-sidebar]');
    const scrim = document.querySelector('[data-portal-scrim]');
    const menuButton = document.querySelector('[data-portal-menu]');

    const setMenu = (open) => {
        if (!sidebar || !scrim || !menuButton) {
            return;
        }

        sidebar.classList.toggle('is-open', open);
        scrim.classList.toggle('is-open', open);
        menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    };

    menuButton?.addEventListener('click', () => {
        setMenu(!sidebar?.classList.contains('is-open'));
    });

    scrim?.addEventListener('click', () => setMenu(false));

    sidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenu(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setMenu(false);
        }
    });

    const loadingSelector = 'button, a.stellar-btn';
    const exportDialog = document.querySelector('[data-export-dialog]');
    const exportForm = exportDialog?.querySelector('[data-export-form]');
    const exportTitle = exportDialog?.querySelector('[data-export-title]');
    const exportCopy = exportDialog?.querySelector('[data-export-copy]');
    const exportNote = exportDialog?.querySelector('[data-export-note]');
    const exportFrom = exportDialog?.querySelector('[data-export-from]');
    const exportTo = exportDialog?.querySelector('[data-export-to]');
    let exportUrl = null;

    const localDateTimeValue = (date) => {
        const pad = (value) => String(value).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    };

    const openExportDialog = (control) => {
        if (!(exportDialog instanceof HTMLDialogElement) || !control) {
            return false;
        }

        const href = control.getAttribute('href');
        if (!href) {
            return false;
        }

        exportUrl = new URL(href, window.location.origin);
        const label = control.getAttribute('data-export-label') || 'Report';
        if (exportTitle) {
            exportTitle.textContent = `Export ${label}`;
        }
        if (exportCopy) {
            exportCopy.textContent = 'Choose the exact period you want in the CSV. Leave both fields empty for all time.';
        }
        if (exportNote) {
            exportNote.textContent = label === 'Campaign performance'
                ? 'For campaign reports, the period applies to clicks, sessions, conversions, order value and commission. Campaigns themselves are not deleted from the report just because they were created earlier.'
                : '';
        }
        const existingFrom = exportUrl.searchParams.get('from') || '';
        const existingTo = exportUrl.searchParams.get('to') || '';
        const existingRange = exportUrl.searchParams.get('range') || '';

        if (exportFrom instanceof HTMLInputElement) {
            exportFrom.value = existingFrom;
        }
        if (exportTo instanceof HTMLInputElement) {
            exportTo.value = existingTo;
        }

        if (!existingFrom && !existingTo && ['7', '30', '90'].includes(existingRange)
            && exportFrom instanceof HTMLInputElement && exportTo instanceof HTMLInputElement) {
            const days = Number(existingRange);
            const to = new Date();
            const from = new Date(to);
            from.setDate(from.getDate() - (days - 1));
            from.setHours(0, 0, 0, 0);
            exportFrom.value = localDateTimeValue(from);
            exportTo.value = localDateTimeValue(to);
        }

        exportDialog.showModal();
        window.setTimeout(() => exportFrom?.focus(), 40);
        return true;
    };

    exportDialog?.querySelectorAll('[data-export-close]').forEach((button) => {
        button.addEventListener('click', () => exportDialog.close());
    });

    exportDialog?.querySelectorAll('[data-export-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!(exportFrom instanceof HTMLInputElement) || !(exportTo instanceof HTMLInputElement)) {
                return;
            }

            const preset = button.getAttribute('data-export-preset');
            if (preset === 'all') {
                exportFrom.value = '';
                exportTo.value = '';
                return;
            }

            const days = Number(preset);
            if (!Number.isFinite(days) || days <= 0) {
                return;
            }

            const to = new Date();
            const from = new Date(to);
            from.setDate(from.getDate() - (days - 1));
            from.setHours(0, 0, 0, 0);
            exportFrom.value = localDateTimeValue(from);
            exportTo.value = localDateTimeValue(to);
        });
    });

    exportForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!exportUrl) {
            return;
        }

        const submitter = event.submitter;
        if (submitter instanceof HTMLElement) {
            setLoading(submitter);
        }

        const fromValue = exportFrom instanceof HTMLInputElement ? exportFrom.value.trim() : '';
        const toValue = exportTo instanceof HTMLInputElement ? exportTo.value.trim() : '';

        if (fromValue && toValue && new Date(toValue).getTime() < new Date(fromValue).getTime()) {
            if (exportNote) {
                exportNote.textContent = 'The To date must be after the From date.';
            }
            if (submitter instanceof HTMLElement) {
                restoreControl(submitter);
            }
            return;
        }

        exportUrl.searchParams.delete('range');
        if (fromValue) { exportUrl.searchParams.set('from', fromValue); } else { exportUrl.searchParams.delete('from'); }
        if (toValue) { exportUrl.searchParams.set('to', toValue); } else { exportUrl.searchParams.delete('to'); }

        const downloadUrl = exportUrl.toString();
        exportDialog.close();
        window.location.assign(downloadUrl);
        window.setTimeout(() => {
            if (submitter instanceof HTMLElement) {
                restoreControl(submitter);
            }
        }, 1400);
    });

    const setLoading = (control) => {
        if (!control || control.dataset.stellarLoading === 'true') {
            return;
        }

        const rect = control.getBoundingClientRect();
        control.dataset.stellarLoading = 'true';
        control.dataset.stellarOriginalHtml = control.innerHTML;
        control.dataset.stellarOriginalAriaLabel = control.getAttribute('aria-label') || '';
        control.style.minWidth = `${Math.ceil(rect.width)}px`;
        control.classList.add('is-loading');
        control.setAttribute('aria-busy', 'true');
        control.setAttribute('aria-label', 'Loading');
        control.innerHTML = '<span class="stellar-button-spinner" aria-hidden="true"></span>';

        if (control instanceof HTMLButtonElement) {
            control.disabled = true;
        } else {
            control.setAttribute('aria-disabled', 'true');
        }
    };

    const restoreControl = (control) => {
        if (!control || control.dataset.stellarLoading !== 'true') {
            return;
        }

        control.innerHTML = control.dataset.stellarOriginalHtml || '';
        const originalAriaLabel = control.dataset.stellarOriginalAriaLabel || '';
        if (originalAriaLabel) {
            control.setAttribute('aria-label', originalAriaLabel);
        } else {
            control.removeAttribute('aria-label');
        }

        control.classList.remove('is-loading');
        control.removeAttribute('aria-busy');
        control.removeAttribute('aria-disabled');
        control.style.minWidth = '';

        if (control instanceof HTMLButtonElement) {
            control.disabled = false;
        }

        delete control.dataset.stellarLoading;
        delete control.dataset.stellarOriginalHtml;
        delete control.dataset.stellarOriginalAriaLabel;
    };

    const wait = (milliseconds) => new Promise((resolve) => window.setTimeout(resolve, milliseconds));

    const fallbackCopyText = (value) => {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        const copied = document.execCommand('copy');
        textarea.remove();

        if (!copied) {
            throw new Error('Copy failed');
        }
    };

    const copyText = async (value) => {
        if (navigator.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(value);
                return;
            } catch (error) {
                // Fall back when the Clipboard API is present but unavailable in this context.
            }
        }

        fallbackCopyText(value);
    };

    document.addEventListener('click', async (event) => {
        const control = event.target.closest(loadingSelector);
        if (!control) {
            return;
        }

        if (control.matches('[data-no-loading]')) {
            return;
        }

        if (control.matches('[data-export-range]')) {
            event.preventDefault();
            openExportDialog(control);
            return;
        }

        if (control.matches('[data-portal-menu]')) {
            setLoading(control);
            window.setTimeout(() => restoreControl(control), 350);
            return;
        }

        if (control.matches('[data-download]')) {
            setLoading(control);
            window.setTimeout(() => restoreControl(control), 1400);
            return;
        }

        if (control.matches('[data-copy]')) {
            event.preventDefault();
            const value = control.getAttribute('data-copy') || '';
            const originalHtml = control.innerHTML;
            setLoading(control);

            try {
                const startedAt = performance.now();
                await copyText(value);
                const elapsed = performance.now() - startedAt;
                if (elapsed < 220) {
                    await wait(220 - elapsed);
                }
                restoreControl(control);
                control.textContent = 'Copied';
                control.dataset.copied = 'true';
                window.setTimeout(() => {
                    control.innerHTML = originalHtml;
                    delete control.dataset.copied;
                }, 1200);
            } catch (error) {
                restoreControl(control);
                control.textContent = 'Copy failed';
                window.setTimeout(() => {
                    control.innerHTML = originalHtml;
                }, 1200);
            }
            return;
        }

        if (control instanceof HTMLButtonElement) {
            const type = (control.getAttribute('type') || 'submit').toLowerCase();
            if (type === 'submit') {
                return;
            }

            setLoading(control);
            window.setTimeout(() => restoreControl(control), 500);
            return;
        }

        if (control instanceof HTMLAnchorElement) {
            const href = control.getAttribute('href');
            if (!href || href === '#' || event.defaultPrevented) {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            setLoading(control);
            if (control.target === '_blank' || href.startsWith('mailto:') || href.startsWith('tel:')) {
                window.setTimeout(() => restoreControl(control), 700);
            }
        }
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
        if (submitter instanceof HTMLElement) {
            setLoading(submitter);
        }
    });

    document.querySelectorAll('[data-campaign-builder]').forEach((form) => {
        const productSelect = form.querySelector('select[name="product"]');
        const destinationInput = form.querySelector('[data-campaign-destination]');
        const resetDestination = form.querySelector('[data-use-product-destination]');
        const destinationSummary = document.querySelector('[data-campaign-destination-summary]');

        if (!(productSelect instanceof HTMLSelectElement) || !(destinationInput instanceof HTMLInputElement)) {
            return;
        }

        const productDefault = (product) => {
            const key = ['esim', 'vpn', 'antivirus'].includes(product) ? product : 'esim';
            return destinationInput.dataset[`default${key.charAt(0).toUpperCase()}${key.slice(1)}`] || '';
        };

        let previousProduct = productSelect.value || 'esim';
        let previousDefault = productDefault(previousProduct);
        let customDestination = destinationInput.value.trim() !== '' && destinationInput.value.trim() !== previousDefault;

        const syncSummary = () => {
            if (destinationSummary) {
                destinationSummary.textContent = destinationInput.value.trim() || productDefault(productSelect.value || 'esim');
            }
        };

        productSelect.addEventListener('change', () => {
            const nextProduct = productSelect.value || 'esim';
            const nextDefault = productDefault(nextProduct);
            const currentValue = destinationInput.value.trim();

            if (!customDestination || currentValue === '' || currentValue === previousDefault) {
                destinationInput.value = nextDefault;
                customDestination = false;
            }

            previousProduct = nextProduct;
            previousDefault = nextDefault;
            syncSummary();
        });

        destinationInput.addEventListener('input', () => {
            const currentDefault = productDefault(productSelect.value || 'esim');
            const value = destinationInput.value.trim();
            customDestination = value !== '' && value !== currentDefault;
            syncSummary();
        });

        resetDestination?.addEventListener('click', () => {
            destinationInput.value = productDefault(productSelect.value || 'esim');
            customDestination = false;
            previousDefault = destinationInput.value;
            destinationInput.dispatchEvent(new Event('input', { bubbles: true }));
        });

        syncSummary();
    });

    document.querySelectorAll('[data-commission-rate-preview]').forEach((preview) => {
        const form = preview.closest('form');
        const productSelect = form?.querySelector('select[name="product"]');

        if (!(productSelect instanceof HTMLSelectElement)) {
            return;
        }

        const formatPercent = (rate) => {
            const value = Number(rate) * 100;
            return `${Number.isInteger(value) ? value.toFixed(0) : value.toFixed(2).replace(/0+$/, '').replace(/\.$/, '')}%`;
        };

        const products = {
            esim: {
                label: 'Stellar eSIM',
                primaryLabel: 'Per sale',
                description: 'Your eSIM rate applies to every eSIM sale.',
            },
            vpn: {
                label: 'Stellar VPN',
                primaryLabel: 'First payment',
                description: 'Earn on the first payment and every recurring renewal.',
            },
            antivirus: {
                label: 'Stellar Antivirus',
                primaryLabel: 'First payment',
                description: 'Earn on the first payment and every recurring renewal.',
            },
        };

        const renderRate = () => {
            const product = products[productSelect.value] ? productSelect.value : 'esim';
            const definition = products[product];
            const initial = preview.dataset[`${product}Initial`] || '0';
            const recurring = preview.dataset[`${product}Recurring`] || initial;
            const showRecurring = product !== 'esim';

            preview.querySelector('[data-rate-product]')?.replaceChildren(document.createTextNode(definition.label));
            preview.querySelector('[data-rate-primary-label]')?.replaceChildren(document.createTextNode(definition.primaryLabel));
            preview.querySelector('[data-rate-primary-value]')?.replaceChildren(document.createTextNode(formatPercent(initial)));
            preview.querySelector('[data-rate-secondary-value]')?.replaceChildren(document.createTextNode(formatPercent(recurring)));

            const secondary = preview.querySelector('[data-rate-secondary]');
            if (secondary instanceof HTMLElement) {
                secondary.hidden = !showRecurring;
            }

            preview.querySelector('[data-rate-description]')?.replaceChildren(document.createTextNode(definition.description));
        };

        productSelect.addEventListener('change', renderRate);
        renderRate();
    });

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-stellar-loading="true"]').forEach((control) => restoreControl(control));
    });
})();
