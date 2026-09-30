@props(['tabs', 'active', 'model' => 'tab'])
<div class="mb-6 overflow-x-auto border-b border-slate-200">
    <nav class="-mb-px flex gap-6">
        @foreach($tabs as $key => $label)
            <button type="button" wire:click="$set('{{ $model }}', '{{ $key }}')"
                @class(['whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition',
                        'border-brand text-brand' => $active === $key,
                        'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $active !== $key])>
                {{ $label }}
            </button>
        @endforeach
    </nav>
</div>
