<div>
    <x-page-header title="Exercise library" subtitle="Shared library plus your gym's custom exercises — used in PT workouts and session logs.">
        <button wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4"/> Add exercise</button>
    </x-page-header>
    <div class="mb-4 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" class="input max-w-xs" placeholder="Search exercises">
        <select wire:model.live="muscle" class="input w-auto"><option value="">All muscle groups</option>@foreach($muscles as $m)<option>{{ $m }}</option>@endforeach</select>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach($exercises as $e)
            <div class="card p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="font-medium">{{ $e->name }}</div>
                    @if($e->gym_id)<button wire:click="edit({{ $e->id }})" class="btn-ghost p-1"><x-icon name="pencil" class="size-4"/></button>@else<x-badge>Library</x-badge>@endif
                </div>
                <div class="mt-1 flex flex-wrap gap-1">@if($e->muscle_group)<x-badge color="brand">{{ $e->muscle_group }}</x-badge>@endif @if($e->equipment)<x-badge>{{ $e->equipment }}</x-badge>@endif</div>
                @if($e->instructions)<p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $e->instructions }}</p>@endif
                @if($e->video_url)<a href="{{ $e->video_url }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-1 text-xs text-brand"><x-icon name="play" class="size-3"/> Video</a>@endif
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $exercises->links() }}</div>

    <x-modal wire:model="showForm" :title="$editingId ? 'Edit exercise' : 'Add exercise'">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field label="Name" name="name" class="sm:col-span-2"><input wire:model="name" class="input"></x-field>
            <x-field label="Muscle group" name="muscle_group"><input wire:model="muscle_group" class="input" list="muscles"></x-field>
            <datalist id="muscles">@foreach($muscles as $m)<option value="{{ $m }}">@endforeach</datalist>
            <x-field label="Equipment" name="equipment"><input wire:model="equipment" class="input"></x-field>
            <x-field label="Video URL" name="video_url" class="sm:col-span-2"><input wire:model="video_url" class="input" placeholder="https://youtube.com/…"></x-field>
            <x-field label="Instructions" name="instructions" class="sm:col-span-2"><textarea wire:model="instructions" rows="3" class="input"></textarea></x-field>
        </div>
        <x-slot:footer><button wire:click="save" class="btn-primary">Save</button></x-slot:footer>
    </x-modal>
</div>
