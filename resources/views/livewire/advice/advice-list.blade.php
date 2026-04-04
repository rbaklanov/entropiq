<div class="space-y-6">

    <div>
        <h1 class="text-h1">{{ __('advice.title') }}</h1>
    </div>

    @if($advices->isEmpty())
        <x-empty-state
            icon="✨"
            :title="__('advice.no_advice')"
            :description="__('advice.no_advice_description')"
            :actionUrl="route('transactions.create')"
            :actionLabel="__('transactions.add')"
        />
    @else
        <div class="space-y-3">
            @foreach($advices as $advice)
                <x-ai-advice-card :advice="$advice" />
            @endforeach
        </div>
    @endif

</div>
