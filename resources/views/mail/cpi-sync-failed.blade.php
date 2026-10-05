<x-mail::message>
# {{ __('cpi_alert.heading') }}

{{ __('cpi_alert.intro') }}

| | |
|---|---|
| **{{ __('cpi_alert.reason') }}** | {{ $reason }} |
| **{{ __('cpi_alert.period') }}** | {{ $periodFrom->format('d.m.Y') }} → {{ $periodTo->format('d.m.Y') }} |
| **{{ __('cpi_alert.time') }}** | {{ $failedAt->format('d.m.Y H:i') }} |

{{ __('cpi_alert.stored_data') }}

{{ __('cpi_alert.next_step') }}

```
php artisan cpi:sync --from={{ $periodFrom->toDateString() }}
```
</x-mail::message>
