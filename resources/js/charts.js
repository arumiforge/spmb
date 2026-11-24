/*
 * Charts bundle (ApexCharts), loaded only on pages that show charts.
 * Usage: <div data-chart='{"chart":{"type":"bar"},"series":[...]}' class="min-h-72"></div>
 *
 * Every chart gets shared defaults so text never overlaps on small screens (D-070,
 * spmb-ui → Charts): under 640 px data labels are off, the legend sits below, long
 * category labels are shortened (full text stays in the tooltip) and rotated labels
 * hide when they would overlap. Colours follow the brand colour and dark mode, and the
 * chart redraws when the theme changes. Animation is off under prefers-reduced-motion.
 */
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

const SMALL = 640;
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isDark = () => document.documentElement.classList.contains('dark');

/** Shortens a label to max characters with an ellipsis. */
export function shorten(label, max) {
    const text = String(label ?? '');

    return text.length > max ? `${text.slice(0, Math.max(1, max - 1)).trimEnd()}…` : text;
}

function isObject(value) {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

/** Deep merge: values in override win; arrays are replaced, not merged. */
function merge(base, override) {
    const out = { ...base };

    Object.entries(override ?? {}).forEach(([key, value]) => {
        out[key] = isObject(value) && isObject(out[key]) ? merge(out[key], value) : value;
    });

    return out;
}

/**
 * Brand-based palette. The primary scale is built with color-mix() (app.css), which
 * ApexCharts cannot read, so each step is painted on a 1 px canvas and read back as hex.
 */
function palette() {
    const probe = document.createElement('span');
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    document.body.appendChild(probe);

    const colors = ['--color-primary-600', '--color-primary-300', '--color-primary-800', '--color-primary-400', '--color-primary-900'].map((name) => {
        probe.style.color = `var(${name}, #2563eb)`;
        ctx.clearRect(0, 0, 1, 1);
        ctx.fillStyle = '#2563eb';
        ctx.fillStyle = getComputedStyle(probe).color;
        ctx.fillRect(0, 0, 1, 1);
        const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

        return `#${[r, g, b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
    });
    probe.remove();

    return colors;
}

function defaults(type) {
    const dark = isDark();
    const text = dark ? '#d1d5db' : '#4b5563';
    const grid = dark ? '#374151' : '#e5e7eb';
    const round = type === 'donut' || type === 'pie' || type === 'radialBar';

    return {
        chart: {
            fontFamily: 'inherit',
            foreColor: text,
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false },
            parentHeightOffset: 0,
            animations: { enabled: !reducedMotion(), speed: 500 },
            height: round ? 300 : 320,
        },
        colors: palette(),
        theme: { mode: dark ? 'dark' : 'light' },
        grid: { borderColor: grid, strokeDashArray: 4, padding: { left: 8, right: 8 } },
        dataLabels: { enabled: false },
        legend: {
            position: round ? 'bottom' : 'top',
            horizontalAlign: 'center',
            fontSize: '14px',
            itemMargin: { horizontal: 12, vertical: 4 },
            markers: { size: 6, strokeWidth: 0, offsetX: -4 },
            labels: { colors: text },
        },
        tooltip: { theme: dark ? 'dark' : 'light' },
        stroke: round ? { width: 2, colors: [dark ? '#1f2937' : '#ffffff'] } : { width: type === 'line' || type === 'area' ? 3 : 0 },
        plotOptions: {
            bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '55%' },
            pie: {
                // No labels on slices under 8%: they would overlap.
                dataLabels: { minAngleToShowLabel: 29 },
                donut: { size: '62%', labels: { show: true, name: { fontSize: '14px' }, value: { fontSize: '24px', fontWeight: 700, color: dark ? '#ffffff' : '#111827' }, total: { show: true, fontSize: '14px', color: text } } },
            },
        },
        xaxis: {
            labels: { trim: true, hideOverlappingLabels: true, style: { fontSize: '13px' } },
            axisBorder: { color: grid },
            axisTicks: { color: grid },
        },
        yaxis: { labels: { style: { fontSize: '13px' } } },
        noData: { text: 'Belum ada data' },
    };
}

/** Options for screens under 640 px, merged as an ApexCharts responsive breakpoint. */
function smallScreen(options) {
    const type = options.chart?.type ?? 'line';
    const round = type === 'donut' || type === 'pie' || type === 'radialBar';
    const horizontal = options.plotOptions?.bar?.horizontal === true;
    const small = {
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '13px' },
        chart: { height: Math.max(260, Number(options.chart?.height) || 0) },
    };

    if (round) {
        small.plotOptions = { pie: { donut: { labels: { value: { fontSize: '20px' }, total: { fontSize: '13px' } } } } };

        return small;
    }

    const shortLabel = (value) => shorten(value, horizontal ? 14 : 10);
    small.xaxis = {
        labels: {
            rotate: -45,
            rotateAlways: false,
            hideOverlappingLabels: true,
            style: { fontSize: '12px' },
            // Date axes format their own labels; shortening would print the raw timestamp.
            ...(horizontal || options.xaxis?.type === 'datetime' ? {} : { formatter: shortLabel }),
        },
        tickAmount: options.xaxis?.type === 'datetime' ? 4 : undefined,
    };
    small.yaxis = { labels: { style: { fontSize: '12px' }, ...(horizontal ? { maxWidth: 110, formatter: shortLabel } : {}) } };
    small.plotOptions = { bar: { columnWidth: '70%' } };

    return small;
}

function build(el) {
    const own = JSON.parse(el.dataset.chart);
    const type = own.chart?.type ?? 'line';
    const options = merge(defaults(type), own);

    // Keep the full category text in the tooltip even when the axis label is shortened.
    if (options.xaxis?.categories && !options.tooltip?.x?.formatter) {
        const categories = options.xaxis.categories;
        options.tooltip = merge(options.tooltip, { x: { formatter: (value, { dataPointIndex } = {}) => categories[dataPointIndex] ?? value } });
    }

    options.responsive = [{ breakpoint: SMALL, options: smallScreen(own) }, ...(own.responsive ?? [])];

    return options;
}

const charts = new Map();

function render(el) {
    try {
        charts.get(el)?.destroy();
        const chart = new ApexCharts(el, build(el));
        charts.set(el, chart);
        chart.render();
    } catch (error) {
        console.error('Chart could not be drawn', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-chart]').forEach(render);

    // Redraw with the right colours when the light/dark toggle changes <html class>.
    let dark = isDark();
    new MutationObserver(() => {
        if (dark !== isDark()) {
            dark = isDark();
            charts.forEach((_, el) => render(el));
        }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
