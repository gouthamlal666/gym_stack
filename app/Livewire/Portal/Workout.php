<?php

namespace App\Livewire\Portal;

use App\Models\PtWorkoutPlan;
use Livewire\Attributes\Title;

#[Title('My workout plan')]
class Workout extends PortalPage
{
    public function render()
    {
        $plan = PtWorkoutPlan::with('days.exercises', 'trainer.user')->where('member_id', $this->member()->id)->where('is_active', true)->latest()->first();

        return view('livewire.portal.workout', ['plan' => $plan]);
    }
}
