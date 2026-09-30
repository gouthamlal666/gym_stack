@props(['title', 'subtitle' => null, 'back' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if($back)
            <a href="{{ $back }}" wire:navigate class="mb-1 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700"><x-icon name="arrow-left" class="size-4"/> Back</a>
        @endif
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h2>
        @if($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif
    </div>
    @if(! $slot->isEmpty())<div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>@endif
</div>
