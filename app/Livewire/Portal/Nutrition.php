<?php

namespace App\Livewire\Portal;

use App\Models\NutritionPlan;
use Livewire\Attributes\Title;

#[Title('My nutrition')]
class Nutrition extends PortalPage
{
    public function render()
    {
        $plan = NutritionPlan::with('meals', 'trainer.user')->where('member_id', $this->member()->id)->where('is_active', true)->latest()->first();

        return view('livewire.portal.nutrition', ['plan' => $plan]);
    }
}
