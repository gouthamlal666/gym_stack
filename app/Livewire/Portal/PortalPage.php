<?php

namespace App\Livewire\Portal;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
abstract class PortalPage extends Component
{
    use InteractsWithUi;

    public ?string $modal = null;

    protected function member(): Member
    {
        $member = Auth::user()->member;
        abort_if(! $member, 403, 'Your login is not linked to a member profile. Please contact the front desk.');

        return $member;
    }
}
