@php use App\Support\Money; use App\Notifications\MembershipReminder; @endphp
<div>
    <x-page-header title="Memberships" subtitle="Expiry tracking, freezes, renewals and reminders."/>
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach(['active' => 'Active', 'expiring' => 'Expiring ≤ 7 days', 'frozen' => 'Frozen', 'upcoming' => 'Upcoming', 'expired' => 'Expired (not renewed)', 'cancelled' => 'Cancelled'] as $k => $label)
            <button wire:click="$set('filter', '{{ $k }}')" @class(['rounded-full px-3 py-1.5 text-sm font-medium', 'bg-brand text-white' => $filter === $k, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $filter !== $k])>
                {{ $label }} <span class="opacity-70">{{ $counts[$k] ?? 0 }}</span>
            </button>
        @endforeach
        <input wire:model.live.debounce.300ms="search" class="input ml-auto w-64" placeholder="Search member…">
    </div>

    @if($remindable)
        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-brand/20 bg-brand-50 px-4 py-3">
            <x-icon name="bolt" class="size-5 text-brand"/>
            <div class="min-w-0 flex-1 text-sm">
                <b>{{ $filter === 'expiring' ? 'Remind before it lapses' : 'Win back lapsed members' }}</b>
                <span class="text-slate-600">— WhatsApp & email. Automatic reminders also go out {{ $filter === 'expiring' ? '7, 3 and 1 day(s) before expiry' : '1 and 7 days after expiry' }} (change in Gym settings).</span>
                @if($waDriver !== 'meta')<div class="text-xs text-amber-700">WhatsApp is in test mode (messages are written to the log). Connect WhatsApp Business in Gym settings to deliver them.</div>@endif
            </div>
            <span class="text-sm text-slate-600">{{ count($selected) }} selected</span>
            <button wire:click="selectAll" class="btn-secondary btn-sm">Select all {{ $counts[$filter] ?? 0 }}</button>
            @if($selected)<button wire:click="$set('selected', [])" class="btn-ghost btn-sm">Clear</button>@endif
            <button wire:click="openReminder" class="btn-primary btn-sm" @disabled(empty($selected))><x-icon name="bolt" class="size-4"/> Send reminder</button>
        </div>
    @endif

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr>
                @if($remindable)<th class="w-8"></th>@endif
                <th>Member</th><th>Plan</th><th>Start</th><th>End</th><th>{{ $filter === 'expired' ? 'Lapsed' : 'Days left' }}</th><th>Amount</th><th>Status</th>
                @if($remindable)<th>Last reminded</th><th class="text-right">Remind</th>@endif
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($memberships as $m)
                <tr wire:key="ms-{{ $m->id }}" @class(['bg-brand-50/50' => in_array((string) $m->id, $selected)])>
                    @if($remindable)<td><input type="checkbox" wire:model.live="selected" value="{{ $m->id }}" class="rounded border-slate-300 text-brand"></td>@endif
                    <td><a href="{{ route('members.show', $m->member) }}" wire:navigate class="font-medium hover:text-brand">{{ $m->member->name }}</a><div class="text-xs text-slate-500">{{ $m->member->member_code }} · {{ $m->member->phone }}</div></td>
                    <td>{{ $m->plan->name }}</td>
                    <td>{{ $m->start_date->format('d M Y') }}</td>
                    <td>{{ $m->end_date->format('d M Y') }}</td>
                    <td>
                        @if(in_array($m->status, ['active', 'frozen']))<x-badge :color="$m->daysLeft() <= 3 ? 'red' : ($m->daysLeft() <= 7 ? 'amber' : 'green')">{{ $m->daysLeft() }}</x-badge>
                        @elseif($m->status === 'expired')<span class="text-sm text-slate-500">{{ $m->daysSinceExpiry() }}d ago</span>
                        @else — @endif
                    </td>
                    <td>{{ Money::format($m->price - $m->discount) }}</td>
                    <td><x-status :value="$m->status"/></td>
                    @if($remindable)
                        <td class="text-sm text-slate-500">{{ $m->last_reminded_at ? \Carbon\Carbon::parse($m->last_reminded_at)->diffForHumans() : 'Never' }}</td>
                        <td class="text-right whitespace-nowrap">
                            @php $link = $wa->chatLink($m->member->phone, (new MembershipReminder($m, MembershipReminder::typeFor($m)))->text()); @endphp
                            @if($link)<a href="{{ $link }}" target="_blank" rel="noopener" class="btn-ghost btn-sm text-emerald-600" title="Open in WhatsApp (send from your own phone)">
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1-.8 1-.9 1.2-.3.2-.6.1a8.2 8.2 0 0 1-2.4-1.5 9 9 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.4-.5.3-.5v-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6a1.1 1.1 0 0 0-.8.4 3.4 3.4 0 0 0-1 2.5 5.9 5.9 0 0 0 1.2 3.1 13.5 13.5 0 0 0 5.2 4.6c.7.3 1.3.5 1.7.6a4.2 4.2 0 0 0 1.9.1 3.1 3.1 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4c-.1-.2-.3-.2-.6-.4M12 21.8a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1 1 12 21.8m8.4-18.2A11.8 11.8 0 0 0 1.8 17.9L.1 24l6.3-1.6A11.8 11.8 0 0 0 12 23.8 11.8 11.8 0 0 0 20.4 3.6"/></svg>
                            </a>@endif
                            <button wire:click="openReminder({{ $m->id }})" class="btn-secondary btn-sm">Send</button>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="10"><x-empty icon="card" title="Nothing in this list"/></td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 p-4">{{ $memberships->links() }}</div>
    </div>

    <x-modal wire:model="showReminder" title="Send renewal reminder" max-width="max-w-xl">
        <div class="space-y-4">
            <div class="text-sm text-slate-600">
                To <b>{{ $targets->count() }}</b> member{{ $targets->count() === 1 ? '' : 's' }}:
                {{ $targets->take(4)->map(fn ($t) => $t->member->name)->implode(', ') }}{{ $targets->count() > 4 ? ' and '.($targets->count() - 4).' more' : '' }}
            </div>
            <div>
                <label class="label">Channels</label>
                <div class="flex gap-3">
                    @foreach(\App\Services\ReminderService::CHANNELS as $key => $label)
                        <label @class(['flex flex-1 cursor-pointer items-center gap-2 rounded-lg border p-3 text-sm', 'border-brand bg-brand-50' => in_array($key, $channels), 'border-slate-200' => ! in_array($key, $channels)])>
                            <input type="checkbox" wire:model.live="channels" value="{{ $key }}" class="rounded text-brand">
                            <span class="font-medium">{{ $label }}</span>
                            <span class="ml-auto text-xs text-slate-500">
                                @php $missing = $targets->filter(fn ($t) => $key === 'mail' ? ! $t->member->email : ! $wa->normalize($t->member->phone))->count(); @endphp
                                {{ $missing ? $missing.' without '.($key === 'mail' ? 'email' : 'phone') : 'all reachable' }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('channels')<p class="error">{{ $message }}</p>@enderror
            </div>
            @if($preview)
                <div>
                    <label class="label">Message preview</label>
                    <div class="rounded-xl rounded-tl-none bg-emerald-50 p-3 text-sm text-slate-800 ring-1 ring-emerald-100">{{ $preview }}</div>
                    <p class="mt-1 text-xs text-slate-500">Personalised per member (name, plan, date). @if($waDriver === 'meta' && $wa->config()['template_expiring'])WhatsApp uses your approved templates.@endif</p>
                </div>
            @endif
            @if($waDriver !== 'meta' && in_array('whatsapp', $channels))
                <p class="rounded-lg bg-amber-50 p-2.5 text-xs text-amber-800">WhatsApp test mode: messages are logged, not delivered. Use the green WhatsApp icon on a row to send from your own phone, or connect WhatsApp Business in Gym settings.</p>
            @endif
        </div>
        <x-slot:footer><button wire:click="sendReminders" wire:loading.attr="disabled" class="btn-primary"><span wire:loading.remove wire:target="sendReminders">Send now</span><span wire:loading wire:target="sendReminders">Sending…</span></button></x-slot:footer>
    </x-modal>
</div>
