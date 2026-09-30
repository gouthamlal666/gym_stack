<?php

namespace App\Livewire\Memberships;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\MembershipPlan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app'), Title('Membership plans')]
class Plans extends Component
{
    use InteractsWithUi;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $billing_cycle = 'monthly';
    public int $duration_days = 30;
    public $price = '';
    public $admission_fee = 0;
    public bool $is_trial = false;
    public int $max_freeze_days = 0;
    public ?string $description = '';
    public bool $is_active = true;

    public function mount(): void { $this->requirePermission('plans.manage'); }

    public function updatedBillingCycle($value): void
    {
        if ($days = config("gym.billing_cycles.$value.days")) {
            $this->duration_days = $days;
        }
    }

    public function create(): void
    {
        $this->reset();
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $plan = MembershipPlan::findOrFail($id);
        $this->editingId = $id;
        $this->fill($plan->only('name', 'billing_cycle', 'duration_days', 'price', 'admission_fee', 'is_trial', 'max_freeze_days', 'description', 'is_active'));
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:100',
            'billing_cycle' => 'required|in:'.implode(',', array_keys(config('gym.billing_cycles'))),
            'duration_days' => 'required|integer|min:1|max:1825',
            'price' => 'required|numeric|min:0',
            'admission_fee' => 'numeric|min:0',
            'is_trial' => 'boolean',
            'max_freeze_days' => 'integer|min:0|max:365',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);
        MembershipPlan::updateOrCreate(['id' => $this->editingId], $data);
        $this->showForm = false;
        $this->toast('Plan saved.');
    }

    public function render()
    {
        return view('livewire.memberships.plans', [
            'plans' => MembershipPlan::withCount(['memberships as active_count' => fn ($q) => $q->whereIn('status', ['active', 'frozen'])])
                ->orderByDesc('is_active')->orderBy('price')->get(),
        ]);
    }
}
