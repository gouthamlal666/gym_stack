<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app'), Title('Branches')]
class Branches extends Component
{
    use InteractsWithUi;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public ?string $code = '';
    public ?string $phone = '';
    public ?string $email = '';
    public ?string $address = '';
    public ?string $opens_at = '05:00';
    public ?string $closes_at = '22:00';
    public bool $is_active = true;

    public function mount(): void { $this->requirePermission('branches.manage'); }

    public function create(): void
    {
        $this->reset();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $b = Branch::findOrFail($id);
        $this->editingId = $id;
        $this->fill($b->only('name', 'code', 'phone', 'email', 'address', 'is_active'));
        $this->opens_at = $b->opens_at ? substr($b->opens_at, 0, 5) : null;
        $this->closes_at = $b->closes_at ? substr($b->closes_at, 0, 5) : null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120', 'code' => 'nullable|string|max:20', 'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email', 'address' => 'nullable|string|max:500', 'opens_at' => 'nullable|date_format:H:i',
            'closes_at' => 'nullable|date_format:H:i', 'is_active' => 'boolean',
        ]);
        Branch::updateOrCreate(['id' => $this->editingId], $data);
        $this->showForm = false;
        $this->toast('Branch saved.');
    }

    public function render()
    {
        return view('livewire.settings.branches', [
            'branches' => Branch::withCount(['members', 'staff'])->orderBy('name')->get(),
        ]);
    }
}
