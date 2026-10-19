<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('export.pdf_title') }}</title>
    <style>
        /* DomPDF reads TTF only; the files carry Latin and Cyrillic. Colours mirror the "Paper and ink" tokens. */
        @font-face { font-family: 'Golos Text'; font-weight: 400; src: url("{{ resource_path('fonts/GolosText-Regular.ttf') }}") format('truetype'); }
        @font-face { font-family: 'Golos Text'; font-weight: 600; src: url("{{ resource_path('fonts/GolosText-SemiBold.ttf') }}") format('truetype'); }
        @font-face { font-family: 'Literata'; font-weight: 400; src: url("{{ resource_path('fonts/Literata-Regular.ttf') }}") format('truetype'); }
        @font-face { font-family: 'Literata'; font-weight: 600; src: url("{{ resource_path('fonts/Literata-SemiBold.ttf') }}") format('truetype'); }

        @page { margin: 28px 32px; }
        body { font-family: 'Golos Text', 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e1a15; }
        .wordmark { font-family: 'Literata', 'DejaVu Serif', serif; font-size: 14px; font-weight: 600; margin: 0 0 14px; }
        .wordmark span { color: #a3431f; }
        h1 { font-family: 'Literata', 'DejaVu Serif', serif; font-size: 20px; font-weight: 600; margin: 0 0 4px; }
        .muted { color: #5c544a; }
        .summary { margin: 14px 0 10px; width: 100%; border-collapse: collapse; }
        .summary td { padding: 8px 10px 8px 0; border-top: 1px solid #1e1a15; vertical-align: top; }
        .summary .label { display: block; color: #5c544a; font-size: 9px; }
        .summary .value { font-family: 'Literata', 'DejaVu Serif', serif; font-size: 14px; font-weight: 600; }
        table.rows { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.rows th { text-align: left; font-weight: 600; padding: 5px 6px 5px 0; border-bottom: 1px solid #1e1a15; }
        table.rows td { padding: 5px 6px 5px 0; border-bottom: 1px solid #d9cfbe; }
        table.rows .num { text-align: right; white-space: nowrap; padding-right: 16px; }
        .income { color: #2f6b3f; }
    </style>
</head>
<body>
    <p class="wordmark">entropiq<span>.</span></p>
    <h1>{{ __('export.pdf_title') }}</h1>
    <div class="muted">
        @if($from || $to)
            {{ __('export.period') }}:
            {{ $from?->format('d.m.Y') ?? '…' }} — {{ $to?->format('d.m.Y') ?? '…' }}.
        @else
            {{ __('export.period_all') }}.
        @endif
        {{ __('export.generated') }}: {{ $generatedAt->format('d.m.Y H:i') }}
    </div>

    <table class="summary">
        <tr>
            <td><span class="label">{{ __('export.income') }}</span><span class="value income">{{ number_format($income / 100, 2, ',', ' ') }} {{ $currency }}</span></td>
            <td><span class="label">{{ __('export.expense') }}</span><span class="value">{{ number_format($expense / 100, 2, ',', ' ') }} {{ $currency }}</span></td>
            <td><span class="label">{{ __('export.balance') }}</span><span class="value">{{ $balance < 0 ? '−' : '' }}{{ number_format(abs($balance) / 100, 2, ',', ' ') }} {{ $currency }}</span></td>
        </tr>
    </table>

    <table class="rows">
        <thead>
            <tr>
                <th>{{ __('export.date') }}</th>
                <th>{{ __('export.type') }}</th>
                <th>{{ __('export.category') }}</th>
                <th class="num">{{ __('export.amount') }}</th>
                <th>{{ __('export.comment') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
                @php($isIncome = $transaction->type === \App\Enums\TransactionType::Income)
                <tr>
                    <td>{{ $transaction->date->format('d.m.Y') }}</td>
                    <td>{{ $isIncome ? __('export.income') : __('export.expense') }}</td>
                    <td>{{ $transaction->category->localizedName() }}</td>
                    <td class="num {{ $isIncome ? 'income' : '' }}">
                        {{ $isIncome ? '+' : '−' }}{{ number_format($transaction->amount / 100, 2, ',', ' ') }} {{ $transaction->currency_code }}
                    </td>
                    <td>{{ $transaction->comment }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
