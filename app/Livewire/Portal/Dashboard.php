<?php

namespace App\Livewire\Portal;

use App\Support\PtProgress;
use Livewire\Attributes\Title;

#[Title('My dashboard')]
class Dashboard extends PortalPage
{
    public function render()
    {
        $member = $this->member()->load('currentMembership.plan');

        return view('livewire.portal.dashboard', [
            'member' => $member,
            'pt' => $member->isPt() ? PtProgress::summary($member) : null,
            'visitsMonth' => $member->attendances()->where('check_in_at', '>=', now()->startOfMonth())->count(),
            'balance' => $member->outstandingBalance(),
        ]);
    }
}
