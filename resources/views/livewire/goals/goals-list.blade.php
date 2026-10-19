<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-h1">{{ __('goals.title') }}</h1>
        <a
            href="{{ route('goals.create') }}"
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            {{ __('goals.create') }}
        </a>
    </div>

    {{-- Summary card --}}
    @if($hasGoals)
        <section class="rounded-md border border-gray-200 bg-white p-6">
            <p class="text-sm text-gray-500">{{ __('goals.total_saved') }}</p>
            <p class="mt-1 font-serif text-3xl font-semibold tabular-nums">
                {{ number_format($totalCurrent / 100, 0, '.', ' ') }} ₽
                <span class="font-sans text-base font-normal text-gray-500">
                    / {{ number_format($totalTarget / 100, 0, '.', ' ') }} ₽
                </span>
            </p>
            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>{{ __('goals.progress') }}</span>
                    <span>{{ $overallProgress }}%</span>
                </div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-sm bg-gray-100">
                    <div
                        class="ink-grow h-full rounded-sm bg-primary-600"
                        style="width: {{ min(100, $overallProgress) }}%"
                    ></div>
                </div>
            </div>
        </section>
    @endif

    {{-- Upsell: goal limit --}}
    @if(!$canCreateGoal)
        <x-upsell-banner
            :message="__('goals.limit_upsell')"
        />
    @endif

    {{-- Filter tabs --}}
    <div class="flex gap-2">
        @foreach(['active' => __('goals.filter_active'), 'achieved' => __('goals.filter_achieved'), 'all' => __('goals.filter_all')] as $key => $label)
            <button
                wire:click="setFilter('{{ $key }}')"
                class="min-h-10 rounded-md border px-4 text-sm transition-colors
                    {{ $filter === $key
                        ? 'border-gray-900 bg-gray-900 text-white'
                        : 'border-gray-200 text-gray-900 hover:border-gray-900' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Goals grid --}}
    @if($goalData->isEmpty())
        <x-empty-state
            :title="__('goals.no_goals')"
            :description="__('goals.no_goals_description')"
            :actionUrl="route('goals.create')"
            :actionLabel="__('goals.create')"
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($goalData as $item)
                <x-goal-card
                    :goal="$item['goal']"
                    :monthlyPayment="$item['monthly_payment']"
                    :completionDate="$item['completion_date']"
                />
            @endforeach
        </div>
    @endif

</div>
