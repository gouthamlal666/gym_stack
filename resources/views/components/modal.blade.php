{{-- Usage: <x-modal name="freeze" title="…"> opens when the component's $modal === 'freeze',
     or <x-modal wire:model="showForm" title="…"> bound to a boolean property. --}}
@props(['title' => '', 'maxWidth' => 'max-w-lg', 'name' => null])
@php $model = $attributes->wire('model')->value(); @endphp
<div @if($name)
        x-data="{ get open() { return $wire.modal === @js($name) }, set open(v) { if (! v) $wire.modal = null } }"
     @else
        x-data="{ open: $wire.entangle('{{ $model }}') }"
     @endif
     x-show="open" x-cloak x-on:keydown.escape.window="open = false" class="fixed inset-0 z-50 overflow-y-auto">
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/50" @click="open = false"></div>
    <div class="relative flex min-h-full items-end justify-center p-4 sm:items-center">
        <div x-show="open" x-transition class="relative w-full {{ $maxWidth }} rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                <button type="button" class="btn-ghost -mr-2 p-1.5" @click="open = false"><x-icon name="x" class="size-5"/></button>
            </div>
            <div class="px-5 py-4">{{ $slot }}</div>
            @isset($footer)
                <div class="flex justify-end gap-2 rounded-b-2xl border-t border-slate-100 bg-slate-50 px-5 py-3">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
