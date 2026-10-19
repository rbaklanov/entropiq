@props([
    'series' => [],
    'categories' => [],
    'height' => 320,
    'colors' => null,
])

@php
    $defaultColors = null;
    $chartColors = $colors ?? $defaultColors;
@endphp

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
                    type: 'area',
                    height: {{ $height }},
                    fontFamily: 'Golos Text, ui-sans-serif, system-ui, sans-serif',
                    animations: { enabled: ! window.prefersReducedMotion(), speed: 900 },
                    toolbar: { show: false },
                    zoom: { enabled: false },
                },
                series: {{ Js::from($series) }},
                colors: {{ Js::from($chartColors) }} ?? [window.themeColor('gray-900'), window.themeColor('primary-600')],
                xaxis: {
                    categories: {{ Js::from($categories) }},
                    labels: {
                        style: { colors: window.themeColor('gray-500'), fontSize: '12px' },
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: window.themeColor('gray-500'), fontSize: '12px' },
                        formatter: (val) => new Intl.NumberFormat(locale, { notation: 'compact' }).format(Math.round(val / 100)) + ' ₽',
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 2.5,
                },
                fill: {
                    type: 'solid',
                    opacity: 0.06,
                },
                dataLabels: {
                    enabled: false,
                },
                grid: {
                    borderColor: window.themeColor('gray-200'),
                    strokeDashArray: 4,
                    padding: { left: 8, right: 8 },
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    fontWeight: 500,
                    fontSize: '13px',
                    labels: { colors: window.themeColor('gray-700') },
                    markers: { radius: 3 },
                    itemMargin: { horizontal: 12 },
                },
                tooltip: {
                    y: {
                        formatter: (val) => new Intl.NumberFormat(locale).format(Math.round(val / 100)) + ' ₽',
                    },
                },
            });
            this.chart.render();
        },
    }"
>
    <div x-ref="chart"></div>
</div>
