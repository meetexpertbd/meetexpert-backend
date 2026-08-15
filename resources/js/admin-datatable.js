import DataTable from 'datatables.net';

function emptyStateHtml(title, hint) {
    return `
        <div class="dt-empty-state">
            <span class="dt-empty-icon" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <rect x="4.25" y="3.25" width="15.5" height="17.5" rx="3" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M8 8h8M8 12.25h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </span>
            <p class="dt-empty-title">${title}</p>
            <p class="dt-empty-hint">${hint}</p>
        </div>
    `;
}

const defaultOptions = {
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    order: [],
    autoWidth: false,
    columnDefs: [
        { orderable: false, searchable: false, targets: 'no-sort' },
    ],
    language: {
        search: '',
        searchPlaceholder: 'Search...',
        lengthMenu: 'Show _MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_',
        infoEmpty: 'No records to show',
        infoFiltered: '(filtered from _MAX_)',
        emptyTable: emptyStateHtml(
            'No records found',
            'There is nothing to show here yet.'
        ),
        zeroRecords: emptyStateHtml(
            'No matching records',
            'Try a different search keyword.'
        ),
        paginate: {
            previous: 'Prev',
            next: 'Next',
        },
    },
};

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function bindBulkDelete(table, dt) {
    const url = table.dataset.bulkUrl;
    if (!url) {
        return;
    }

    const wrap = table.closest('.admin-datatable');
    if (!wrap) {
        return;
    }

    const singular = table.dataset.bulkNoun || 'item';
    const plural = table.dataset.bulkNounPlural || `${singular}s`;
    const selected = new Set();

    const bar = document.createElement('div');
    bar.className = 'dt-bulk-bar';
    bar.innerHTML = `
        <p class="dt-bulk-count"></p>
        <button type="button" class="js-dt-bulk-delete">Delete selected</button>
    `;
    wrap.prepend(bar);

    const countEl = bar.querySelector('.dt-bulk-count');
    const deleteBtn = bar.querySelector('.js-dt-bulk-delete');

    const pageChecks = () => [...table.querySelectorAll('.js-dt-row-check:not(:disabled)')];
    const allCheck = () => table.querySelector('.js-dt-check-all');

    const noun = (n) => (n === 1 ? singular : plural);

    const sync = () => {
        const checks = pageChecks();
        checks.forEach((cb) => {
            cb.checked = selected.has(cb.value);
        });

        const header = allCheck();
        if (header) {
            const selectedOnPage = checks.filter((cb) => selected.has(cb.value)).length;
            header.checked = checks.length > 0 && selectedOnPage === checks.length;
            header.indeterminate = selectedOnPage > 0 && selectedOnPage < checks.length;
        }

        const n = selected.size;
        bar.classList.toggle('is-visible', n > 0);
        if (countEl) {
            countEl.textContent = `${n} ${noun(n)} selected`;
        }
    };

    table.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.classList.contains('js-dt-row-check')) {
            if (target.disabled) {
                return;
            }
            if (target.checked) {
                selected.add(target.value);
            } else {
                selected.delete(target.value);
            }
            sync();
            return;
        }

        if (target.classList.contains('js-dt-check-all')) {
            pageChecks().forEach((cb) => {
                if (target.checked) {
                    selected.add(cb.value);
                } else {
                    selected.delete(cb.value);
                }
            });
            sync();
        }
    });

    dt.on('draw', sync);

    deleteBtn.addEventListener('click', async () => {
        const n = selected.size;
        if (n === 0) {
            return;
        }

        const confirm = window.Swal
            ? await window.Swal.fire({
                title: `Delete ${n} ${noun(n)}?`,
                text: 'Selected records will be removed permanently.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            })
            : { isConfirmed: window.confirm(`Delete ${n} ${noun(n)}?`) };

        if (!confirm.isConfirmed) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken();
        form.appendChild(token);

        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        form.appendChild(method);

        selected.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });

    sync();
}

function initAdminDataTables() {
    document.querySelectorAll('table.js-datatable').forEach((table) => {
        if (table.dataset.dtInit === '1') {
            return;
        }

        table.dataset.dtInit = '1';
        table.style.width = '100%';
        const dt = new DataTable(table, defaultOptions);
        bindBulkDelete(table, dt);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminDataTables);
} else {
    initAdminDataTables();
}
