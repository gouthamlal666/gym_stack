@props(['value' => 0, 'color' => 'bg-brand', 'height' => 'h-2'])
<div {{ $attributes->merge(['class' => "w-full overflow-hidden rounded-full bg-slate-100 $height"]) }}>
    <div class="{{ $height }} {{ $color }} rounded-full transition-all" style="width: {{ max(0, min(100, $value)) }}%"></div>
</div>
