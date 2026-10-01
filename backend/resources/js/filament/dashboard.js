import Chart from 'chart.js/auto';

/**
 * Gráficas del tablero del Super Admin (mismo estilo del sistema anterior).
 * Se usan desde Alpine: x-data="asistChart(@js($config))".
 */
const money = (value) => '$' + Number(value).toLocaleString('es-MX', { minimumFractionDigits: 2 });

const baseScales = (yTicks = {}) => ({
    y: { beginAtZero: true, ticks: yTicks, grid: { color: '#f3f4f6' } },
    x: { grid: { display: false } },
});

function build(config) {
    if (config.type === 'pie') {
        const total = config.data.reduce((sum, value) => sum + Number(value), 0);

        return {
            type: 'pie',
            data: {
                labels: config.labels,
                datasets: [{
                    data: config.data,
                    backgroundColor: config.colors,
                    // 2px de separación entre rebanadas
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { usePointStyle: true, boxWidth: 8, padding: 12, color: '#475569' },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const percent = total ? Math.round((Number(ctx.raw) / total) * 100) : 0;
                                const value = config.money ? money(ctx.raw) : `${ctx.raw}${config.suffix ?? ''}`;

                                return ` ${ctx.label}: ${value} (${percent}%)`;
                            },
                        },
                    },
                },
            },
        };
    }

    if (config.type === 'bar') {
        return {
            type: 'bar',
            data: {
                labels: config.labels,
                datasets: [{
                    label: config.label,
                    data: config.data,
                    backgroundColor: config.colors,
                    borderColor: config.colors,
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 48,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => (config.money ? money(ctx.raw) : ctx.raw) } },
                },
                scales: baseScales(config.money ? { callback: (v) => '$' + v.toLocaleString('es-MX') } : { precision: 0 }),
            },
        };
    }

    return {
        type: 'line',
        data: {
            labels: config.labels,
            datasets: config.series.map((serie) => ({
                label: serie.label,
                data: serie.data,
                borderColor: serie.color,
                backgroundColor: serie.color + '1a',
                borderWidth: 2,
                pointBackgroundColor: serie.color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${ctx.raw}${config.suffix ?? ''}` } },
            },
            scales: baseScales({ precision: 0 }),
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('asistChart', (config) => ({
        chart: null,
        init() {
            this.chart = new Chart(this.$refs.canvas, build(config));
        },
        destroy() {
            this.chart?.destroy();
        },
    }));
});
