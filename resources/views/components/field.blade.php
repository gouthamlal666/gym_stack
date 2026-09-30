@props(['label' => null, 'name' => null, 'hint' => null])
<div {{ $attributes }}>
    @if($label)<label class="label">{{ $label }}</label>@endif
    {{ $slot }}
    @if($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @if($name)@error($name)<p class="error">{{ $message }}</p>@enderror @endif
</div>
