@php use App\Support\Money; $cm = $member->currentMembership; $pt = $member->activePtSubscription; @endphp
<div>
    <x-page-header :title="$member->name" :back="route('members.index')">
        @can('attendance.manage')<button wire:click="checkIn" class="btn-secondary"><x-icon name="check" class="size-4"/> Check in</button>@endcan
        @can('members.manage')<a href="{{ route('members.edit', $member) }}" wire:navigate class="btn-secondary"><x-icon name="pencil" class="size-4"/> Edit</a>@endcan
        @if($member->isPt())
            @can('view-pt-member', $member)<a href="{{ route('pt.clients.show', $member) }}" wire:navigate class="btn-primary"><x-icon name="star" class="size-4"/> PT profile</a>@endcan
        @endif
        @can('pt.enroll')<button wire:click="openModal('enroll')" class="btn-primary"><x-icon name="plus" class="size-4"/> {{ $member->isPt() ? 'New PT package' : 'Enroll in PT' }}</button>@endcan
    </x-page-header>

    {{-- Summary --}}
    <div class="card mb-6 flex flex-wrap items-center gap-6 p-5">
        <x-avatar :name="$member->name" :src="$member->photoUrl()" size="size-20"/>
        <div class="min-w-0 flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm text-slate-500">{{ $member->member_code }}</span>
                @if($member->isPt())<x-badge color="amber">⭐ Personal training customer</x-badge>@else<x-badge>Regular member</x-badge>@endif
                <x-status :value="$member->status"/>
            </div>
            <div class="text-sm text-slate-600">{{ $member->phone }} @if($member->email)· {{ $member->email }}@endif</div>
            <div class="text-sm text-slate-500">{{ $member->branch?->name }} · Joined {{ $member->joined_on->format('d M Y') }} @if($member->trainer)· Trainer: {{ $member->trainer->name }}@endif</div>
        </div>
        <div class="grid grid-cols-3 gap-6 text-center">
            <div><div class="text-xs text-slate-500">Membership</div><div class="mt-1 font-semibold">{{ $cm ? $cm->daysLeft().' days left' : '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Visits this month</div><div class="mt-1 font-semibold">{{ $visitsThisMonth }}</div></div>
            <div><div class="text-xs text-slate-500">Balance due</div><div @class(['mt-1 font-semibold', 'text-rose-600' => $balance > 0])>{{ Money::format($balance) }}</div></div>
        </div>
    </div>

    <x-tabs :tabs="['overview' => 'Overview', 'memberships' => 'Membership history', 'billing' => 'Payments & invoices', 'attendance' => 'Attendance', 'documents' => 'Documents']" :active="$tab"/>

    @if($tab === 'overview')
        <div class="grid gap-6 lg:grid-cols-3">
            <x-card title="Current membership" class="lg:col-span-2">
                @if($cm)
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="text-lg font-semibold">{{ $cm->plan->name }} <x-status :value="$cm->status"/></div>
                            <div class="text-sm text-slate-500">{{ $cm->start_date->format('d M Y') }} → {{ $cm->end_date->format('d M Y') }}</div>
                            @if($cm->status === 'frozen')<div class="mt-1 text-sm text-sky-700">Frozen {{ $cm->frozen_from->format('d M') }} – {{ $cm->frozen_until->format('d M Y') }}</div>@endif
                        </div>
                        @can('memberships.manage')
                            <div class="flex flex-wrap gap-2">
                                <button wire:click="openModal('renew', {{ $cm->id }})" class="btn-primary btn-sm">Renew</button>
                                <button wire:click="openModal('change', {{ $cm->id }})" class="btn-secondary btn-sm">Upgrade / downgrade</button>
                                @if($cm->status === 'frozen')
                                    <button wire:click="unfreeze({{ $cm->id }})" class="btn-secondary btn-sm">Unfreeze</button>
                                @elseif($cm->plan->max_freeze_days > $cm->frozen_days_used)
                                    <button wire:click="openModal('freeze', {{ $cm->id }})" class="btn-secondary btn-sm">Freeze</button>
                                @endif
                                <button wire:click="openModal('extend', {{ $cm->id }})" class="btn-secondary btn-sm">Extend</button>
                                <button wire:click="openModal('cancel', {{ $cm->id }})" class="btn-ghost btn-sm text-rose-600">Cancel</button>
                            </div>
                        @endcan
                    </div>
                    @php $total = max(1, $cm->start_date->diffInDays($cm->end_date)); $used = $cm->start_date->diffInDays(now()->min($cm->end_date)); @endphp
                    <x-progress :value="$used / $total * 100" class="mt-4"/>
                    <div class="mt-1 flex justify-between text-xs text-slate-500"><span>{{ (int) $used }} days used</span><span>{{ $cm->daysLeft() }} days left · {{ $cm->plan->max_freeze_days - $cm->frozen_days_used }} freeze days available</span></div>
                @else
                    <x-empty icon="card" title="No active membership">
                        @can('memberships.manage')<button wire:click="openModal('assign')" class="btn-primary">Assign a plan</button>@endcan
                    </x-empty>
                @endif
            </x-card>

            <x-card title="Emergency contact">
                @if($member->emergency_contact_name)
                    <div class="font-medium">{{ $member->emergency_contact_name }}</div>
                    <div class="text-sm text-slate-500">{{ $member->emergency_contact_relation }}</div>
                    <div class="mt-1 text-sm">{{ $member->emergency_contact_phone }}</div>
                @else
                    <p class="text-sm text-slate-500">Not provided.</p>
                @endif
                @if($member->medical_notes)
                    <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900"><b>Medical:</b> {{ $member->medical_notes }}</div>
                @endif
            </x-card>

            @if($pt)
                <x-card title="Personal training" class="lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">{{ $pt->package->name }}</div>
                            <div class="text-sm text-slate-500">with {{ $pt->trainer->name }} · expires {{ $pt->expiry_date->format('d M Y') }}</div>
                        </div>
                        <div class="text-right"><div class="text-2xl font-semibold">{{ $pt->remainingSessions() }}</div><div class="text-xs text-slate-500">of {{ $pt->total_sessions }} sessions left</div></div>
                    </div>
                    <x-progress :value="$pt->progressPercent()" class="mt-3" color="bg-amber-500"/>
                </x-card>
            @endif

            <x-card title="Renewal reminders" :padding="false">
                <ul class="divide-y divide-slate-100">
                    @forelse($reminders as $r)
                        <li class="flex items-center justify-between gap-2 px-5 py-2.5 text-sm">
                            <span>{{ $r->channel === 'mail' ? '✉️ Email' : '💬 WhatsApp' }} · {{ $r->type === 'membership_expired' ? 'expired' : 'expiring' }}</span>
                            <span class="text-right text-xs text-slate-500"><x-badge :color="$r->succeeded() ? 'green' : ($r->status === 'failed' ? 'red' : 'slate')" class="mr-1">{{ $r->status === 'logged' ? 'test' : $r->status }}</x-badge>{{ $r->created_at->diffForHumans() }} · {{ $r->sender?->name ?? 'auto' }}
                                @if($r->error)<span class="block text-rose-600">{{ $r->error }}</span>@endif</span>
                        </li>
                    @empty
                        <li class="px-5 py-4 text-sm text-slate-500">No reminders sent yet.</li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Member portal">
                @if($member->user)
                    <div class="flex items-center gap-2 text-sm text-emerald-700"><x-icon name="check" class="size-4"/> Login active ({{ $member->user->email }})</div>
                    <div class="mt-1 text-xs text-slate-500">Last sign-in: {{ $member->user->last_login_at?->diffForHumans() ?? 'never' }}</div>
                @else
                    <p class="text-sm text-slate-500">This member cannot sign in yet.</p>
                    @can('members.manage')<button wire:click="createLogin" class="btn-secondary btn-sm mt-3">Create portal login</button>@endcan
                @endif
                @can('members.delete')
                    <button wire:click="delete" wire:confirm="Archive this member? Their history is kept." class="btn-ghost btn-sm mt-4 text-rose-600"><x-icon name="trash" class="size-4"/> Archive member</button>
                @endcan
            </x-card>
        </div>
    @endif

    @if($tab === 'memberships')
        <x-card :padding="false">
            <x-slot:actions>@can('memberships.manage')<button wire:click="openModal('assign')" class="btn-primary btn-sm"><x-icon name="plus" class="size-4"/> New membership</button>@endcan</x-slot:actions>
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Plan</th><th>Type</th><th>Period</th><th>Price</th><th>Status</th><th>Invoice</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($memberships as $m)
                    <tr>
                        <td class="font-medium">{{ $m->plan->name }}</td>
                        <td><x-badge>{{ ucfirst($m->change_type) }}</x-badge></td>
                        <td class="text-sm">{{ $m->start_date->format('d M Y') }} – {{ $m->end_date->format('d M Y') }}
                            @if($m->extended_days)<span class="text-xs text-slate-500">(+{{ $m->extended_days }}d ext.)</span>@endif
                            @if($m->frozen_days_used)<span class="text-xs text-sky-600">({{ $m->frozen_days_used }}d frozen)</span>@endif
                        </td>
                        <td>{{ Money::format($m->price - $m->discount) }}</td>
                        <td><x-status :value="$m->status"/>@if($m->cancel_reason)<div class="text-xs text-slate-500">{{ $m->cancel_reason }}</div>@endif</td>
                        <td>@if($m->invoice)<a href="{{ route('invoices.show', $m->invoice) }}" wire:navigate class="text-brand hover:underline">{{ $m->invoice->number }}</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty icon="card" title="No memberships yet"/></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-card>
    @endif

    @if($tab === 'billing')
        <div class="grid gap-6 lg:grid-cols-2">
            <x-card title="Invoices" :padding="false">
                <ul class="divide-y divide-slate-100">
                    @forelse($invoices as $i)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div><a href="{{ route('invoices.show', $i) }}" wire:navigate class="font-medium hover:text-brand">{{ $i->number }}</a><div class="text-xs text-slate-500">{{ $i->issue_date->format('d M Y') }}</div></div>
                            <div class="text-right"><div class="font-medium">{{ Money::format($i->total) }}</div><x-status :value="$i->status"/></div>
                        </li>
                    @empty <x-empty icon="document" title="No invoices"/> @endforelse
                </ul>
            </x-card>
            <x-card title="Payment history" :padding="false">
                <ul class="divide-y divide-slate-100">
                    @forelse($payments as $p)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div><a href="{{ route('payments.receipt', $p) }}" target="_blank" class="font-medium hover:text-brand">{{ $p->receipt_number }}</a><div class="text-xs text-slate-500">{{ $p->paid_at->format('d M Y') }} · {{ $p->methodLabel() }}</div></div>
                            <div @class(['font-medium', 'text-rose-600' => $p->type === 'refund'])>{{ $p->type === 'refund' ? '−' : '' }}{{ Money::format($p->amount) }}</div>
                        </li>
                    @empty <x-empty icon="cash" title="No payments"/> @endforelse
                </ul>
            </x-card>
        </div>
    @endif

    @if($tab === 'attendance')
        <x-card :padding="false">
            <div class="overflow-x-auto"><table class="table">
                <thead><tr><th>Date</th><th>Check in</th><th>Check out</th><th>Duration</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($attendance as $a)
                    <tr><td>{{ $a->check_in_at->format('D, d M Y') }}</td><td>{{ $a->check_in_at->format('H:i') }}</td><td>{{ $a->check_out_at?->format('H:i') ?? '—' }}</td>
                        <td>{{ $a->check_out_at ? $a->check_in_at->diffInMinutes($a->check_out_at).' min' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="4"><x-empty icon="check" title="No visits recorded"/></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-card>
    @endif

    @if($tab === 'documents')
        <div class="grid gap-6 lg:grid-cols-3">
            <x-card :padding="false" class="lg:col-span-2">
                <ul class="divide-y divide-slate-100">
                    @forelse($documents as $d)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <x-icon name="document" class="size-5 text-slate-400"/>
                            <div class="min-w-0 flex-1"><div class="truncate font-medium">{{ $d->title }}</div><div class="text-xs text-slate-500">{{ $d->created_at->format('d M Y') }} · {{ $d->uploader?->name }}</div></div>
                            <a href="{{ route('documents.download', $d) }}" class="btn-secondary btn-sm">Download</a>
                            @can('members.manage')<button wire:click="deleteDocument({{ $d->id }})" wire:confirm="Delete this document?" class="btn-ghost btn-sm text-rose-600"><x-icon name="trash" class="size-4"/></button>@endcan
                        </li>
                    @empty <x-empty icon="document" title="No documents" text="ID proofs, waivers, medical certificates…"/> @endforelse
                </ul>
            </x-card>
            @can('members.manage')
                <x-card title="Upload document">
                    <form wire:submit="uploadDocument" class="space-y-3">
                        <x-field label="Title" name="doc_title"><input wire:model="doc_title" class="input" placeholder="e.g. ID proof"></x-field>
                        <x-field label="File (PDF, image, Word · max 5 MB)" name="doc_file"><input type="file" wire:model="doc_file" class="block w-full text-sm"></x-field>
                        <button class="btn-primary w-full" wire:loading.attr="disabled">Upload</button>
                    </form>
                </x-card>
            @endcan
        </div>
    @endif

    {{-- ── Modals ─────────────────────────────────────────────────────────── --}}
    @php $planOptions = $plans->map(fn ($p) => ['id' => $p->id, 'label' => $p->name.' · '.$p->cycleLabel().' · '.Money::format($p->price)]); @endphp

    <x-modal name="assign" title="Assign membership">
        <div class="space-y-4">
            <x-field label="Plan" name="plan_id"><select wire:model="plan_id" class="input"><option value="">Choose…</option>@foreach($planOptions as $o)<option value="{{ $o['id'] }}">{{ $o['label'] }}</option>@endforeach</select></x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Start date" name="start_date"><input type="date" wire:model="start_date" class="input"></x-field>
                <x-field label="Discount" name="discount"><input type="number" step="0.01" wire:model="discount" class="input"></x-field>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="admission" class="rounded text-brand"> Charge admission fee (if the plan has one)</label>
            @error('modal')<p class="error">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer><button wire:click="assignPlan" class="btn-primary">Assign & create invoice</button></x-slot:footer>
    </x-modal>

    <x-modal name="renew" title="Renew membership">
        <div class="space-y-4">
            <p class="text-sm text-slate-500">The new period starts the day after the current one ends (or today if it has already lapsed).</p>
            <x-field label="Plan" name="plan_id"><select wire:model="plan_id" class="input">@foreach($planOptions as $o)<option value="{{ $o['id'] }}">{{ $o['label'] }}</option>@endforeach</select></x-field>
            <x-field label="Discount" name="discount"><input type="number" step="0.01" wire:model="discount" class="input"></x-field>
            @error('modal')<p class="error">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer><button wire:click="renew" class="btn-primary">Renew</button></x-slot:footer>
    </x-modal>

    <x-modal name="change" title="Upgrade / downgrade">
        <div class="space-y-4">
            <p class="text-sm text-slate-500">The current plan ends today. Unused days are credited against the new plan's invoice.</p>
            <x-field label="New plan" name="plan_id"><select wire:model="plan_id" class="input"><option value="">Choose…</option>@foreach($planOptions as $o)<option value="{{ $o['id'] }}">{{ $o['label'] }}</option>@endforeach</select></x-field>
            <x-field label="Extra discount" name="discount"><input type="number" step="0.01" wire:model="discount" class="input"></x-field>
            @error('modal')<p class="error">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer><button wire:click="changePlan" class="btn-primary">Change plan</button></x-slot:footer>
    </x-modal>

    <x-modal name="freeze" title="Freeze membership">
        <div class="grid grid-cols-2 gap-4">
            <x-field label="Freeze from" name="start_date"><input type="date" wire:model="start_date" class="input"></x-field>
            <x-field label="Days" name="days"><input type="number" wire:model="days" class="input" min="1"></x-field>
            <p class="col-span-2 text-xs text-slate-500">The end date is pushed back by the same number of days.</p>
            @error('modal')<p class="error col-span-2">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer><button wire:click="freeze" class="btn-primary">Freeze</button></x-slot:footer>
    </x-modal>

    <x-modal name="extend" title="Extend membership">
        <div class="space-y-4">
            <x-field label="Extra days" name="days"><input type="number" wire:model="days" class="input" min="1"></x-field>
            <x-field label="Reason" name="reason"><input wire:model="reason" class="input" placeholder="e.g. Gym closed for renovation"></x-field>
            @error('modal')<p class="error">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer><button wire:click="extend" class="btn-primary">Extend</button></x-slot:footer>
    </x-modal>

    <x-modal name="cancel" title="Cancel membership">
        <x-field label="Reason" name="reason"><input wire:model="reason" class="input"></x-field>
        @error('modal')<p class="error">{{ $message }}</p>@enderror
        <x-slot:footer><button wire:click="cancel" class="btn-danger">Cancel membership</button></x-slot:footer>
    </x-modal>

    <x-modal name="enroll" title="Enroll in personal training" max-width="max-w-xl">
        <div class="space-y-4">
            @unless($member->isPt())
                <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">This converts {{ $member->first_name }} to a <b>Personal Training Customer</b> and unlocks the PT modules.</div>
            @endunless
            <x-field label="PT package" name="pt_package_id">
                <select wire:model="pt_package_id" class="input"><option value="">Choose…</option>
                    @foreach($packages as $p)<option value="{{ $p->id }}">{{ $p->name }} · {{ $p->sessions_count }} sessions · {{ $p->validity_days }} days · {{ Money::format($p->price) }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Trainer" name="pt_trainer_id">
                <select wire:model="pt_trainer_id" class="input"><option value="">Choose…</option>@foreach($trainers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
            </x-field>
            <div class="grid grid-cols-2 gap-4">
                <x-field label="Start date" name="start_date"><input type="date" wire:model="start_date" class="input"></x-field>
                <x-field label="Discount" name="discount"><input type="number" step="0.01" wire:model="discount" class="input"></x-field>
            </div>
            @error('modal')<p class="error">{{ $message }}</p>@enderror
        </div>
        <x-slot:footer><button wire:click="enrollPt" class="btn-primary">Enroll & create invoice</button></x-slot:footer>
    </x-modal>
</div>
