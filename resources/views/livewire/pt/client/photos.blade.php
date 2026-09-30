<div class="space-y-6">
    <div class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm text-white"><x-icon name="lock" class="size-4"/> Private — visible only to {{ $member->first_name }}, their assigned trainer and the gym admin.</div>

    @if($canUpload)
        <x-card title="Upload photos">
            <form wire:submit="upload" class="flex flex-wrap items-end gap-4">
                <x-field label="Date" name="taken_on"><input type="date" wire:model="taken_on" class="input"></x-field>
                <x-field label="Angle" name="angle"><select wire:model="angle" class="input">@foreach(config('gym.photo_angles') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
                <x-field label="Image(s)" name="uploads"><input type="file" wire:model="uploads" multiple accept="image/*" class="text-sm"></x-field>
                <button class="btn-primary" wire:loading.attr="disabled" wire:target="uploads,upload"><x-icon name="camera" class="size-4"/> Upload</button>
            </form>
            @error('uploads.*')<p class="error">{{ $message }}</p>@enderror
        </x-card>
    @endif

    @if($dates->isEmpty())
        <div class="card"><x-empty icon="camera" title="No progress photos yet" text="Upload front, back and side photos at each assessment."/></div>
    @else
        @include('pt.partials.before-after')

        <x-card title="All photos">
            <div class="space-y-6">
                @foreach($groups as $date => $set)
                    <div>
                        <div class="mb-2 text-sm font-semibold">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                            @foreach($set as $p)
                                <div class="group relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-100">
                                    <img src="{{ $p->url() }}" class="size-full object-cover" loading="lazy" alt="">
                                    <span class="absolute bottom-1 left-1 rounded bg-black/50 px-1.5 text-[10px] text-white">{{ config('gym.photo_angles.'.$p->angle) }}</span>
                                    @if($canUpload)<button wire:click="delete({{ $p->id }})" wire:confirm="Delete this photo?" class="absolute top-1 right-1 hidden rounded bg-white/90 p-1 text-rose-600 group-hover:block"><x-icon name="trash" class="size-4"/></button>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</div>
