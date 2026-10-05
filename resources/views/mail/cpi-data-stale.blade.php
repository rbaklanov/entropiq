<x-mail::message>
# {{ __('cpi_alert.stale_heading') }}

{{ __('cpi_alert.stale_intro') }}

| | |
|---|---|
| **{{ __('cpi_alert.stale_latest') }}** | {{ $latestPublished?->format('m.Y') ?? __('cpi_alert.stale_none') }} |
| **{{ __('cpi_alert.stale_expected') }}** | {{ $expectedAtLeast->format('m.Y') }} |
| **{{ __('cpi_alert.time') }}** | {{ $checkedAt->format('d.m.Y H:i') }} |

{{ __('cpi_alert.stale_causes') }}

{{ __('cpi_alert.next_step') }}

```
php artisan cpi:sync --from=2023-01-01
```
</x-mail::message>
