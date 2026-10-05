<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('export.pdf_title') }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .summary { margin: 12px 0; width: 100%; }
        .summary td { padding: 6px 10px; background: #f3f4f6; }
        table.rows { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.rows th { text-align: left; background: #4f46e5; color: #fff; padding: 5px 6px; }
        table.rows td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
        table.rows .num { text-align: right; white-space: nowrap; }
        .income { color: #059669; }
        .expense { color: #dc2626; }
    </style>
</head>
<body>
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
            <td>{{ __('export.income') }}: <strong class="income">{{ number_format($income / 100, 2, ',', ' ') }} {{ $currency }}</strong></td>
            <td>{{ __('export.expense') }}: <strong class="expense">{{ number_format($expense / 100, 2, ',', ' ') }} {{ $currency }}</strong></td>
            <td>{{ __('export.balance') }}: <strong>{{ $balance < 0 ? '−' : '' }}{{ number_format(abs($balance) / 100, 2, ',', ' ') }} {{ $currency }}</strong></td>
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
                    <td class="num {{ $isIncome ? 'income' : 'expense' }}">
                        {{ $isIncome ? '+' : '−' }}{{ number_format($transaction->amount / 100, 2, ',', ' ') }} {{ $transaction->currency_code }}
                    </td>
                    <td>{{ $transaction->comment }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
