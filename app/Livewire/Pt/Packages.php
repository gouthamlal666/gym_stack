<?php

namespace App\Livewire\Pt;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\PtPackage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app'), Title('PT packages')]
class Packages extends Component
{
    use InteractsWithUi;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public int $sessions_count = 12;
    public $price = '';
    public int $validity_days = 60;
    public int $session_minutes = 60;
    public ?string $description = '';
    public bool $is_active = true;

    public function mount(): void { $this->requirePermission('pt.packages'); }

    public function create(): void
    {
        $this->reset();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->editingId = $id;
        $this->fill(PtPackage::findOrFail($id)->only('name', 'sessions_count', 'price', 'validity_days', 'session_minutes', 'description', 'is_active'));
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:100', 'sessions_count' => 'required|integer|min:1|max:500',
            'price' => 'required|numeric|min:0', 'validity_days' => 'required|integer|min:1|max:1095',
            'session_minutes' => 'required|integer|min:15|max:240', 'description' => 'nullable|string|max:500', 'is_active' => 'boolean',
        ]);
        PtPackage::updateOrCreate(['id' => $this->editingId], $data);
        $this->showForm = false;
        $this->toast('PT package saved.');
    }

    public function render()
    {
        return view('livewire.pt.packages', [
            'packages' => PtPackage::withCount(['subscriptions as active_count' => fn ($q) => $q->where('status', 'active')])
                ->orderByDesc('is_active')->orderBy('sessions_count')->get(),
        ]);
    }
}
