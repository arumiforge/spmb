/*
 * Charts bundle (ApexCharts), loaded only on pages that show charts.
 * Usage: <div data-chart='{"chart":{"type":"bar"},"series":[...]}'></div>
 */
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-chart]').forEach((el) => {
        try {
            const options = JSON.parse(el.dataset.chart);
            new ApexCharts(el, options).render();
        } catch (error) {
            console.error('Chart could not be drawn', error);
        }
    });
});
