/* BaleBolen — interaksi halaman pelanggan */
(function () {
    'use strict';

    const formatRupiah = (value) =>
        'Rp' + Math.round(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });

    /* ---------- Navigasi mobile ---------- */
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            const open = navLinks.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* ---------- Stepper jumlah produk satuan ---------- */
    document.querySelectorAll('[data-qty-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('[data-qty]');
        const form = stepper.closest('form');
        const lineTotal = form ? form.querySelector('[data-line-total]') : null;
        const unitPrice = form ? parseFloat(form.dataset.unitPrice || '0') : 0;

        const update = () => {
            if (lineTotal) {
                lineTotal.textContent = formatRupiah(unitPrice * (parseInt(input.value, 10) || 0));
            }
        };

        stepper.querySelectorAll('[data-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const step = parseInt(button.dataset.step, 10);
                const min = parseInt(input.min || '1', 10);
                const max = parseInt(input.max || '999', 10);
                const next = Math.min(max, Math.max(min, (parseInt(input.value, 10) || min) + step));
                input.value = String(next);
                update();
            });
        });

        input.addEventListener('input', update);
        update();
    });

    /* ---------- Builder komposisi hampers ---------- */
    document.querySelectorAll('[data-hampers-builder]').forEach((form) => {
        const size = parseInt(form.dataset.packageSize || '0', 10);
        const canOrder = form.dataset.canOrder === '1';
        const rows = Array.from(form.querySelectorAll('[data-variant-row]'));
        const totalEl = form.querySelector('[data-builder-total]');
        const countEl = form.querySelector('[data-builder-count]');
        const submit = form.querySelector('[data-builder-submit]');
        const hint = form.querySelector('[data-builder-hint]');

        if (!totalEl || !countEl) {
            return;
        }

        const currentTotal = () =>
            rows.reduce((sum, row) => {
                const input = row.querySelector('[data-qty]');
                return sum + (parseInt(input ? input.value : '0', 10) || 0);
            }, 0);

        const update = () => {
            const total = currentTotal();
            totalEl.textContent = String(total);
            countEl.classList.toggle('is-valid', total === size);
            countEl.classList.toggle('is-over', total > size);

            if (submit) {
                submit.disabled = !(canOrder && total === size);
            }

            if (hint) {
                hint.style.display = total === size ? 'none' : 'block';
            }
        };

        rows.forEach((row) => {
            const input = row.querySelector('[data-qty]');
            const stock = input ? parseInt(input.max || '0', 10) : 0;

            row.querySelectorAll('[data-step]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!input) {
                        return;
                    }

                    const step = parseInt(button.dataset.step, 10);
                    const currentValue = parseInt(input.value, 10) || 0;
                    const otherTotal = currentTotal() - currentValue;
                    const roomByPackage = Math.max(0, size - otherTotal);
                    const max = Math.min(stock, roomByPackage);
                    const next = Math.min(max, Math.max(0, currentValue + step));

                    input.value = String(next);
                    update();
                });
            });
        });

        update();
    });

    /* ---------- Checkout: pickup/delivery & metode pembayaran ---------- */
    const checkoutForm = document.getElementById('checkoutForm');

    if (checkoutForm) {
        const deliveryFields = checkoutForm.querySelector('[data-delivery-fields]');
        const paymentCards = Array.from(checkoutForm.querySelectorAll('[data-payment-card]'));

        const sync = () => {
            const selected = checkoutForm.querySelector('input[name="fulfillment"]:checked');
            const fulfillment = selected ? selected.value : 'pickup';

            if (deliveryFields) {
                deliveryFields.classList.toggle('is-hidden', fulfillment !== 'delivery');
            }

            paymentCards.forEach((card) => {
                const target = card.dataset.for;
                const hide =
                    (target === 'delivery' && fulfillment !== 'delivery') ||
                    (target === 'pickup' && fulfillment !== 'pickup');

                card.classList.toggle('is-hidden', hide);

                if (hide) {
                    const input = card.querySelector('input');
                    if (input && input.checked) {
                        input.checked = false;
                        const fallback = checkoutForm.querySelector('input[name="payment_method"][value="transfer"]');
                        if (fallback) {
                            fallback.checked = true;
                        }
                    }
                }
            });
        };

        checkoutForm.querySelectorAll('input[name="fulfillment"]').forEach((input) => {
            input.addEventListener('change', sync);
        });

        sync();
    }

    /* ---------- Flash otomatis hilang ---------- */
    document.querySelectorAll('[data-flash]').forEach((flash) => {
        window.setTimeout(() => {
            flash.style.transition = 'opacity 0.4s, transform 0.4s';
            flash.style.opacity = '0';
            flash.style.transform = 'translateX(12px)';
            window.setTimeout(() => flash.remove(), 420);
        }, 6000);
    });

    /* ---------- Reveal saat scroll ---------- */
    const revealItems = document.querySelectorAll('[data-reveal]');

    if (revealItems.length > 0 && 'IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
        );

        revealItems.forEach((item) => observer.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }
})();
