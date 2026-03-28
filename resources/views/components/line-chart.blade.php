@props([
    'series' => [],
    'categories' => [],
    'height' => 320,
    'colors' => null,
])

@php
    $defaultColors = ['#6366F1', '#F59E0B'];
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
                    fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                },
                series: {{ Js::from($series) }},
                colors: {{ Js::from($chartColors) }},
                xaxis: {
                    categories: {{ Js::from($categories) }},
                    labels: {
                        style: { colors: '#9CA3AF', fontSize: '12px' },
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: { colors: '#9CA3AF', fontSize: '12px' },
                        formatter: (val) => new Intl.NumberFormat(locale, { notation: 'compact' }).format(Math.round(val / 100)) + ' ₽',
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 2.5,
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.15,
                        opacityTo: 0.02,
                        stops: [0, 100],
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                grid: {
                    borderColor: '#F3F4F6',
                    strokeDashArray: 4,
                    padding: { left: 8, right: 8 },
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    fontWeight: 500,
                    fontSize: '13px',
                    labels: { colors: '#374151' },
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
