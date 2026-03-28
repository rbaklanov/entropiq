@props([
    'data' => [],
    'labels' => [],
    'colors' => [],
    'height' => 320,
])

<div
    {{ $attributes->merge(['class' => 'w-full']) }}
    x-data="{
        chart: null,
        get locale() {
            return document.documentElement.lang || 'ru';
        },
        init() {
            const locale = this.locale;

            this.chart = new ApexCharts(this.$refs.chart, {
                chart: {
                    type: 'donut',
                    height: {{ $height }},
                    fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif',
                },
                series: {{ Js::from($data) }},
                labels: {{ Js::from($labels) }},
                colors: {{ Js::from($colors) }},
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: '{{ __('analytics.total') }}',
                                    fontWeight: 600,
                                    fontSize: '14px',
                                    color: '#6B7280',
                                    formatter: (w) => {
                                        const total = w.globals.series.reduce((a, b) => a + b, 0);
                                        return new Intl.NumberFormat(locale).format(Math.round(total / 100)) + ' ₽';
                                    },
                                },
                                value: {
                                    fontWeight: 700,
                                    fontSize: '18px',
                                    color: '#111827',
                                    formatter: (val) => new Intl.NumberFormat(locale).format(Math.round(val / 100)) + ' ₽',
                                },
                            },
                        },
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                legend: {
                    show: false,
                },
                stroke: {
                    width: 2,
                    colors: ['#ffffff'],
                },
                tooltip: {
                    y: {
                        formatter: (val) => new Intl.NumberFormat(locale).format(Math.round(val / 100)) + ' ₽',
                    },
                },
                states: {
                    hover: { filter: { type: 'darken', value: 0.9 } },
                    active: { filter: { type: 'none' } },
                },
            });
            this.chart.render();
        },
    }"
>
    <div x-ref="chart"></div>
</div>
