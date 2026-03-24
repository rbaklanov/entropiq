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
        <div class="rounded-2xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, #6366F1, #9333EA)">
            <p class="text-sm font-medium" style="color: rgba(255,255,255,0.8)">{{ __('goals.total_saved') }}</p>
            <p class="mt-1 text-2xl font-bold tracking-tight">
                {{ number_format($totalCurrent / 100, 0, '.', ' ') }} ₽
                <span class="text-base font-normal" style="color: rgba(255,255,255,0.7)">
                    / {{ number_format($totalTarget / 100, 0, '.', ' ') }} ₽
                </span>
            </p>
            <div class="mt-3">
                <div class="flex items-center justify-between text-xs" style="color: rgba(255,255,255,0.7)">
                    <span>{{ __('goals.progress') }}</span>
                    <span>{{ $overallProgress }}%</span>
                </div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-white/20">
                    <div
                        class="h-full rounded-full bg-white transition-all duration-700"
                        style="width: {{ min(100, $overallProgress) }}%"
                    ></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Filter tabs --}}
    <div class="flex gap-2">
        @foreach(['active' => __('goals.filter_active'), 'achieved' => __('goals.filter_achieved'), 'all' => __('goals.filter_all')] as $key => $label)
            <button
                wire:click="setFilter('{{ $key }}')"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition
                    {{ $filter === $key
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Goals grid --}}
    @if($goalData->isEmpty())
        <x-empty-state
            icon="🎯"
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
