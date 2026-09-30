{{-- Expects $dates, $before, $after, $beforePhotos, $afterPhotos, $beforeStats, $afterStats --}}
<x-card title="Before / after">
    <x-slot:actions>
        <select wire:model.live="beforeDate" class="input w-auto py-1 text-xs">@foreach($dates->reverse() as $d)<option value="{{ $d }}" @selected($d === $before)>{{ \Carbon\Carbon::parse($d)->format('d M Y') }}</option>@endforeach</select>
        <span class="text-slate-400">vs</span>
        <select wire:model.live="afterDate" class="input w-auto py-1 text-xs">@foreach($dates as $d)<option value="{{ $d }}" @selected($d === $after)>{{ \Carbon\Carbon::parse($d)->format('d M Y') }}</option>@endforeach</select>
    </x-slot:actions>
    <div class="grid grid-cols-2 gap-4">
        @foreach([['BEFORE', $before, $beforeStats, $beforePhotos], ['AFTER', $after, $afterStats, $afterPhotos]] as [$label, $date, $stats, $set])
            <div>
                <div class="mb-2 text-center">
                    <div class="text-xs font-bold tracking-widest text-slate-400">{{ $label }} · {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
                    @if($stats)<div class="text-lg font-semibold">{{ $stats->weight }} kg @if($stats->body_fat)<span class="text-slate-400">·</span> {{ $stats->body_fat }}% BF @endif</div>@endif
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(config('gym.photo_angles') as $k => $l)
                        <div class="relative aspect-[3/4] overflow-hidden rounded-lg bg-slate-100">
                            @if(isset($set[$k]))<img src="{{ $set[$k]->url() }}" class="size-full object-cover" alt="{{ $l }}" loading="lazy">
                            @else<div class="flex size-full items-center justify-center text-xs text-slate-400">No {{ strtolower($l) }}</div>@endif
                            <span class="absolute bottom-1 left-1 rounded bg-black/50 px-1.5 text-[10px] text-white">{{ $l }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-card>
