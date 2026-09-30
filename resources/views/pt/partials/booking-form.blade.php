{{-- Expects $subscriptions (usable), $slots --}}
<div class="space-y-4">
    <x-field label="PT package" name="book_subscription_id">
        <select wire:model.live="book_subscription_id" class="input"><option value="">Choose…</option>
            @foreach($subscriptions as $s)
                @if($s->status === 'active')<option value="{{ $s->id }}">{{ $s->member->name ?? '' }} {{ $s->package->name }} · {{ $s->bookableSessions() }} bookable · {{ $s->trainer->name }}</option>@endif
            @endforeach
        </select>
    </x-field>
    <div class="grid grid-cols-2 gap-4">
        <x-field label="Date" name="book_date"><input type="date" wire:model.live="book_date" min="{{ today()->toDateString() }}" class="input"></x-field>
        <x-field label="Focus" name="book_focus"><input wire:model="book_focus" class="input" placeholder="e.g. Legs"></x-field>
    </div>
    <div>
        <label class="label">Available slots</label>
        @if($book_subscription_id && $book_date)
            <div class="flex flex-wrap gap-2">
                @forelse($slots as $slot)
                    <button type="button" wire:click="$set('book_time', '{{ $slot }}')" @class(['rounded-lg border px-3 py-1.5 text-sm font-medium', 'border-brand bg-brand text-white' => $book_time === $slot, 'border-slate-200 hover:border-brand' => $book_time !== $slot])>{{ \Carbon\Carbon::parse($slot)->format('h:i A') }}</button>
                @empty
                    <p class="text-sm text-slate-500">No free slots — the trainer is unavailable or fully booked that day.</p>
                @endforelse
            </div>
        @else
            <p class="text-sm text-slate-400">Choose a package and date.</p>
        @endif
        @error('book_time')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
