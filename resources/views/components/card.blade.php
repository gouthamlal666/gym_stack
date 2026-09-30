@props(['title' => null, 'subtitle' => null, 'padding' => true])
<section {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || isset($actions))
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
            <div>
                <h2 class="font-semibold text-slate-900">{{ $title }}</h2>
                @if($subtitle)<p class="text-xs text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['p-5' => $padding])>{{ $slot }}</div>
</section>
