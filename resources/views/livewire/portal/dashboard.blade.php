@php $cm = $member->currentMembership; $hour = now()->hour; @endphp
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-semibold tracking-tight">Good {{ $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') }}, {{ $member->first_name }} 👋</h2>
        <p class="text-sm text-slate-500">{{ now()->format('l, d F Y') }} · Member ID {{ $member->member_code }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Membership" :value="$cm ? $cm->plan->name : 'None'" :hint="$cm ? $cm->daysLeft().' days left · ends '.$cm->end_date->format('d M Y') : 'Visit the front desk to join'" icon="card" :tone="$cm && $cm->daysLeft() > 7 ? 'green' : 'amber'"/>
        <x-stat label="Visits this month" :value="$visitsMonth" icon="check" tone="blue"/>
        <x-stat label="Balance due" :value="\App\Support\Money::format($balance)" icon="cash" :tone="$balance > 0 ? 'red' : 'green'"/>
    </div>

    @if($pt)
        @include('pt.partials.summary', ['s' => $pt, 'portal' => true])
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('portal.progress') }}" wire:navigate class="btn-primary"><x-icon name="chart" class="size-4"/> View full progress</a>
            <a href="{{ route('portal.workout') }}" wire:navigate class="btn-secondary"><x-icon name="dumbbell" class="size-4"/> Workout plan</a>
            <a href="{{ route('portal.sessions') }}" wire:navigate class="btn-secondary"><x-icon name="calendar" class="size-4"/> Book a session</a>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-6 bg-gradient-to-r from-amber-50 to-white p-6">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-amber-100 text-3xl">⭐</div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold">Upgrade to Personal Training</h3>
                    <p class="text-sm text-slate-600">Get a dedicated coach, body-composition tracking, progress photos, a custom workout & nutrition plan and measurable goals.</p>
                </div>
                <span class="text-sm text-slate-500">Ask at the front desk</span>
            </div>
        </div>
    @endif
</div>
