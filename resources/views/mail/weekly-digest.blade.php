<x-mail::message>
# {{ __('digest.greeting', ['name' => $user->name ?? __('digest.default_name')]) }}

{{ __('digest.intro', ['from' => $periodFrom->translatedFormat('j M'), 'to' => $periodTo->translatedFormat('j M')]) }}

| | |
|---|---:|
| **{{ __('digest.income') }}** | **+{{ number_format($digest['total_income'] / 100, 2, ',', ' ') }} ₽** |
| **{{ __('digest.expense') }}** | **-{{ number_format($digest['total_expense'] / 100, 2, ',', ' ') }} ₽** |
| **{{ __('digest.balance') }}** | **{{ number_format($digest['balance'] / 100, 2, ',', ' ') }} ₽** |

{{ __('digest.transactions_count', ['count' => $digest['transactions_count']]) }}

@if(count($digest['top_categories']) > 0)
### {{ __('digest.top_categories') }}

@foreach($digest['top_categories'] as $category)
- {{ $category['name'] }}: {{ number_format($category['total'] / 100, 2, ',', ' ') }} ₽
@endforeach
@endif

<x-mail::button :url="route('dashboard')">
{{ __('digest.cta') }}
</x-mail::button>

{{ __('digest.footer') }}
</x-mail::message>
