@props([
    'step' => 1,
    'totalSteps' => 3,
    'illustration' => '',
    'title' => '',
    'text' => '',
])

<div class="flex min-h-[calc(100dvh-3.5rem)] items-center justify-center px-4 py-8 lg:min-h-screen lg:bg-gray-100">
    <div class="w-full max-w-md lg:rounded-2xl lg:bg-white lg:p-10 lg:shadow-xl">
        <div class="flex flex-col items-center text-center">

            {{-- Illustration --}}
            <div class="mb-8 flex h-48 w-48 items-center justify-center">
                {!! $illustration !!}
            </div>

            {{-- Title --}}
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $title }}</h1>

            {{-- Description --}}
            <p class="mt-3 text-base leading-relaxed text-gray-500">{{ $text }}</p>

            {{-- Action buttons (slot) --}}
            <div class="mt-10 flex w-full flex-col gap-3">
                {{ $slot }}
            </div>

            {{-- Step indicator dots --}}
            <div class="mt-8 flex items-center gap-2">
                @for($i = 1; $i <= $totalSteps; $i++)
                    <span @class([
                        'h-2 rounded-full transition-all duration-300',
                        'w-6 bg-indigo-600' => $i === $step,
                        'w-2 bg-gray-300' => $i !== $step,
                    ])></span>
                @endfor
            </div>
        </div>
    </div>
</div>
