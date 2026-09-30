@props(['icon' => 'box', 'title' => 'Nothing here yet', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400"><x-icon :name="$icon" class="size-6"/></div>
    <h3 class="mt-3 text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if($text)<p class="mt-1 max-w-sm text-sm text-slate-500">{{ $text }}</p>@endif
    @if(! $slot->isEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
