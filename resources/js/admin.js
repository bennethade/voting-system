import Chart from 'chart.js/auto';

/**
 * Charts are declared in Blade as:
 *   <canvas data-chart='{"type":"line","labels":[...],"datasets":[...]}'></canvas>
 * Data is server-rendered JSON, so there are no extra API calls.
 */
const palette = ['#e8b634', '#4a5285', '#0f9d8a', '#c2417a', '#6b74a6', '#d99a1e', '#272d52', '#9ba2c8'];

Chart.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#4a5285';
Chart.defaults.plugins.legend.labels.usePointStyle = true;

document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
    let config;
    try {
        config = JSON.parse(canvas.dataset.chart);
    } catch (e) {
        return;
    }

    const isCircular = ['doughnut', 'pie'].includes(config.type);

    config.datasets = config.datasets.map((ds, i) => ({
        borderWidth: isCircular ? 0 : 2,
        tension: 0.35,
        pointRadius: 0,
        pointHoverRadius: 4,
        fill: config.type === 'line' ? 'origin' : undefined,
        backgroundColor: isCircular ? palette : config.type === 'line' ? palette[i % palette.length] + '22' : palette[i % palette.length],
        borderColor: isCircular ? undefined : palette[i % palette.length],
        borderRadius: config.type === 'bar' ? 6 : undefined,
        ...ds,
    }));

    new Chart(canvas, {
        type: config.type,
        data: { labels: config.labels, datasets: config.datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: config.horizontal ? 'y' : 'x',
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: isCircular || config.datasets.length > 1, position: 'bottom' } },
            scales: isCircular
                ? {}
                : {
                      x: { grid: { display: false } },
                      y: { beginAtZero: true, grid: { color: '#e7e9f3' }, ticks: { precision: 0 } },
                  },
        },
    });
});
