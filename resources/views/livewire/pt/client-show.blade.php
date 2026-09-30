<div>
    <x-page-header :title="$member->name" :back="route('pt.clients')">
        @can('members.view')<a href="{{ route('members.show', $member) }}" wire:navigate class="btn-secondary"><x-icon name="user" class="size-4"/> Member profile</a>@endcan
    </x-page-header>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <x-avatar :name="$member->name" :src="$member->photoUrl()" size="size-12"/>
        <x-badge color="amber">⭐ Personal training customer</x-badge>
        <span class="text-sm text-slate-500">{{ $member->member_code }} · {{ $member->phone }}</span>
        @if($member->date_of_birth)<span class="text-sm text-slate-500">· {{ $member->date_of_birth->age }} yrs</span>@endif
        @if($member->medical_notes)<x-badge color="red">⚕ {{ \Illuminate\Support\Str::limit($member->medical_notes, 50) }}</x-badge>@endif
    </div>

    <x-tabs :tabs="$tabs" :active="$tab"/>

    @switch($tab)
        @case('overview') @include('pt.partials.summary', ['s' => $summary, 'portal' => false]) @break
        @case('sessions') <livewire:pt.client.sessions :member="$member" :key="'sessions-'.$member->id"/> @break
        @case('body') <livewire:pt.client.body-tracking :member="$member" :key="'body-'.$member->id"/> @break
        @case('photos') <livewire:pt.client.photos :member="$member" :key="'photos-'.$member->id"/> @break
        @case('fitness') <livewire:pt.client.fitness :member="$member" :key="'fitness-'.$member->id"/> @break
        @case('workout') <livewire:pt.client.workout :member="$member" :key="'workout-'.$member->id"/> @break
        @case('nutrition') <livewire:pt.client.nutrition :member="$member" :key="'nutrition-'.$member->id"/> @break
        @case('goals') <livewire:pt.client.goals :member="$member" :key="'goals-'.$member->id"/> @break
    @endswitch
</div>
