import DataTable from 'datatables.net';

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
        infoEmpty: 'Showing 0 to 0 of 0',
        infoFiltered: '(filtered from _MAX_)',
        emptyTable: 'No records found.',
        zeroRecords: 'No matching records.',
        paginate: {
            previous: 'Prev',
            next: 'Next',
        },
    },
};

function initAdminDataTables() {
    document.querySelectorAll('table.js-datatable').forEach((table) => {
        if (table.dataset.dtInit === '1') {
            return;
        }

        table.dataset.dtInit = '1';
        new DataTable(table, defaultOptions);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminDataTables);
} else {
    initAdminDataTables();
}
