@props(['name' => '', 'src' => null, 'size' => 'size-10'])
@php $initials = collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode(''); @endphp
@if($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->merge(['class' => "$size shrink-0 rounded-full object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$size inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700"]) }}>{{ $initials }}</span>
@endif
