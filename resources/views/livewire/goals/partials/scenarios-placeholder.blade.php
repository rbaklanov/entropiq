<div class="space-y-3">
    <div class="h-5 w-32 rounded bg-gray-200"></div>
    <div class="grid grid-cols-3 gap-3">
        @foreach(range(1, 3) as $i)
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                <div class="mx-auto h-3 w-16 rounded bg-gray-200"></div>
                <div class="mx-auto mt-3 h-4 w-20 rounded bg-gray-300"></div>
                <div class="mx-auto mt-2 h-3 w-12 rounded bg-gray-200"></div>
                <div class="mx-auto mt-2 h-3 w-14 rounded bg-gray-200"></div>
            </div>
        @endforeach
    </div>
</div>
