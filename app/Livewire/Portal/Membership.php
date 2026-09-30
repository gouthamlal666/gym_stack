<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\Title;

#[Title('Membership & bills')]
class Membership extends PortalPage
{
    public function render()
    {
        $member = $this->member();

        return view('livewire.portal.membership', [
            'memberships' => $member->memberships()->with('plan')->get(),
            'invoices' => $member->invoices()->get(),
            'payments' => $member->payments()->get(),
        ]);
    }
}
