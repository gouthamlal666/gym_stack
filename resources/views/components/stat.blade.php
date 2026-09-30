@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => 'brand'])
@php $tones = ['brand' => 'bg-brand-50 text-brand', 'green' => 'bg-emerald-50 text-emerald-600', 'amber' => 'bg-amber-50 text-amber-600', 'red' => 'bg-rose-50 text-rose-600', 'blue' => 'bg-sky-50 text-sky-600']; @endphp
<div {{ $attributes->merge(['class' => 'card card-body flex items-start gap-4']) }}>
    @if($icon)
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['brand'] }}"><x-icon :name="$icon" class="size-5"/></div>
    @endif
    <div class="min-w-0">
        <div class="text-sm text-slate-500">{{ $label }}</div>
        <div class="mt-0.5 truncate text-2xl font-semibold text-slate-900">{{ $value }}</div>
        @if($hint)<div class="mt-0.5 text-xs text-slate-500">{{ $hint }}</div>@endif
    </div>
</div>
