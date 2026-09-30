{{-- Shows a signed change. "goodWhen" = down|up decides the colour. --}}
@props(['value', 'unit' => '', 'goodWhen' => 'down'])
@php
    $v = round((float) $value, 1);
    $good = ($v == 0 || $goodWhen === 'neutral') ? null : (($goodWhen === 'down') ? $v < 0 : $v > 0);
@endphp
<span @class(['font-medium', 'text-emerald-600' => $good === true, 'text-rose-600' => $good === false, 'text-slate-500' => $good === null])>
    {{ $v > 0 ? '+' : '' }}{{ $v }}{{ $unit ? ' '.$unit : '' }}
</span>
