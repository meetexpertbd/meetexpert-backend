import './bootstrap';
import jquery from 'jquery';
import select2 from 'select2';
import 'select2/dist/css/select2.min.css';
import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import ApexCharts from 'apexcharts';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.jQuery = window.$ = jquery;
select2(window, jquery);

window.Alpine = Alpine;
window.Swal = Swal;

window.initSkillsCreateSelect2 = function (modalPanel) {
    const $ = window.jQuery;
    const $el = $('#skill-names-select');
    if (!$el.length) {
        return;
    }
    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    const $parent = modalPanel ? $(modalPanel) : $(document.body);
    $el.select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Type a skill, press Enter',
        width: '100%',
        dropdownParent: $parent,
    });
};

window.destroySkillsCreateSelect2 = function () {
    const $ = window.jQuery;
    const $el = $('#skill-names-select');
    if ($el.length && $el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }
    $el.empty().trigger('change');
};
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;

document.addEventListener('alpine:init', () => {
    Alpine.store('toast', {
        items: [],
        show(message, type = 'success') {
            if (message === null || message === '') {
                return;
            }
            const id = Date.now() + Math.random();
            this.items.push({ id, message: String(message), type });
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });
});

Alpine.start();

window.showToast = (message, type = 'success') => {
    Alpine.store('toast').show(message, type);
};

// Initialize components on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
});
