<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app'), Title('Staff & roles')]
class Staff extends Component
{
    use InteractsWithUi;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public string $role = 'receptionist';
    public $branch_id = '';
    public bool $is_active = true;

    public function mount(): void { $this->requirePermission('staff.manage'); }

    public function create(): void
    {
        $this->reset();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $u = $this->staffQuery()->findOrFail($id);
        $this->editingId = $id;
        $this->fill($u->only('name', 'email', 'phone', 'role', 'branch_id', 'is_active'));
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->editingId)],
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:'.implode(',', config('gym.staff_roles')),
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        if ($this->editingId === auth()->id() && ($this->role !== 'gym_admin' || ! $this->is_active)) {
            $this->addError('role', 'You cannot demote or disable your own account.');

            return;
        }

        $data = ['name' => $this->name, 'email' => $this->email, 'phone' => $this->phone, 'role' => $this->role,
            'branch_id' => $this->branch_id ?: null, 'is_active' => $this->is_active];

        if ($this->editingId) {
            $this->staffQuery()->findOrFail($this->editingId)->update($data);
        } else {
            $user = User::create($data + ['gym_id' => auth()->user()->gym_id, 'password' => Str::random(32)]);
            if ($user->role === 'trainer') {
                Trainer::create(['user_id' => $user->id, 'branch_id' => $user->branch_id]);
            }
            Password::sendResetLink(['email' => $user->email]);
        }
        $this->showForm = false;
        $this->toast('Staff saved.');
    }

    private function staffQuery()
    {
        return User::where('gym_id', auth()->user()->gym_id)->whereIn('role', config('gym.staff_roles'));
    }

    public function render()
    {
        $permissions = collect(config('gym.permissions'));

        return view('livewire.settings.staff', [
            'staff' => $this->staffQuery()->with('branch')->orderBy('role')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'matrix' => $permissions,
        ]);
    }
}
