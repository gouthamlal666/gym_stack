<?php

namespace App\Livewire\Pt;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Member;
use App\Support\PtProgress;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class ClientShow extends Component
{
    use InteractsWithUi;

    public Member $member;
    #[Url] public string $tab = 'overview';

    public function mount(Member $member): void
    {
        Gate::authorize('view-pt-member', $member);
        $this->member = $member;
    }

    public function render()
    {
        $tabs = [
            'overview' => 'Overview', 'sessions' => 'Sessions', 'body' => 'Body tracking',
            'photos' => 'Progress photos', 'fitness' => 'Fitness assessment', 'workout' => 'Workout plan',
            'nutrition' => 'Nutrition', 'goals' => 'Goals',
        ];
        if (Gate::denies('view-progress-photos', $this->member)) {
            unset($tabs['photos']); // strict photo privacy: member, assigned trainer, gym admin only
        }
        if (! isset($tabs[$this->tab])) {
            $this->tab = 'overview';
        }

        return view('livewire.pt.client-show', [
            'tabs' => $tabs,
            'summary' => $this->tab === 'overview' ? PtProgress::summary($this->member) : null,
            'canCoach' => Gate::allows('coach-pt-member', $this->member),
        ])->title($this->member->name.' · PT');
    }
}
