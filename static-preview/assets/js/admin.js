/* BaleBolen — interaksi dashboard admin */
(function () {
    'use strict';

    const toggle = document.getElementById('adminToggle');
    const sidebar = document.getElementById('adminSidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', sidebar.classList.contains('is-open') ? 'true' : 'false');
        });

        document.addEventListener('click', (event) => {
            if (
                sidebar.classList.contains('is-open') &&
                !sidebar.contains(event.target) &&
                !toggle.contains(event.target)
            ) {
                sidebar.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ---------- Konfirmasi aksi sensitif ---------- */
    const confirmAction = (message) =>
        new Promise((resolve) => {
            if (typeof HTMLDialogElement === 'undefined') {
                resolve(window.confirm(message));
                return;
            }

            const dialog = document.createElement('dialog');
            dialog.className = 'a-dialog';
            dialog.setAttribute('aria-labelledby', 'aDialogTitle');

            const form = document.createElement('form');
            form.method = 'dialog';

            const title = document.createElement('h2');
            title.id = 'aDialogTitle';
            title.textContent = 'Konfirmasi';

            const text = document.createElement('p');
            text.textContent = message;

            const actions = document.createElement('div');
            actions.className = 'a-dialog__actions';

            const cancel = document.createElement('button');
            cancel.type = 'submit';
            cancel.value = 'cancel';
            cancel.className = 'a-btn a-btn--ghost';
            cancel.textContent = 'Batal';

            const ok = document.createElement('button');
            ok.type = 'submit';
            ok.value = 'ok';
            ok.className = 'a-btn a-btn--danger';
            ok.textContent = 'Lanjutkan';

            actions.append(cancel, ok);
            form.append(title, text, actions);
            dialog.append(form);
            document.body.append(dialog);

            dialog.addEventListener('close', () => {
                const confirmed = dialog.returnValue === 'ok';
                dialog.remove();
                resolve(confirmed);
            });

            dialog.showModal();
            cancel.focus();
        });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.dataset.confirmed === '1') {
            return;
        }

        const message = form.getAttribute('data-confirm');

        if (!message) {
            return;
        }

        event.preventDefault();

        confirmAction(message).then((confirmed) => {
            if (!confirmed) {
                return;
            }

            form.dataset.confirmed = '1';
            form.submit();
        });
    });
})();
