<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Gyms')]
class Gyms extends Component
{
    use InteractsWithUi, WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public string $gym_name = '';
    public string $admin_name = '';
    public string $admin_email = '';
    public string $subscription_plan = 'starter';

    public function create(): void
    {
        $this->reset('gym_name', 'admin_name', 'admin_email');
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'gym_name' => 'required|string|max:120',
            'admin_name' => 'required|string|max:120',
            'admin_email' => 'required|email|unique:users,email',
            'subscription_plan' => 'required|in:trial,starter,pro,enterprise',
        ]);
        DB::transaction(function () {
            $gym = Gym::create(['name' => $this->gym_name, 'slug' => Str::slug($this->gym_name).'-'.Str::lower(Str::random(4)),
                'email' => $this->admin_email, 'subscription_plan' => $this->subscription_plan]);
            Branch::create(['gym_id' => $gym->id, 'name' => 'Main branch', 'code' => 'MAIN']);
            User::create(['gym_id' => $gym->id, 'name' => $this->admin_name, 'email' => $this->admin_email, 'role' => 'gym_admin', 'password' => Str::random(32)]);
        });
        Password::sendResetLink(['email' => $this->admin_email]);
        $this->showForm = false;
        $this->toast('Gym created and admin invited.');
    }

    public function toggle(int $id): void
    {
        $gym = Gym::findOrFail($id);
        $gym->update(['status' => $gym->status === 'active' ? 'suspended' : 'active']);
        Activity::log('gym_status', "Gym {$gym->name} is now {$gym->status}", $gym);
        $this->toast("{$gym->name} is now {$gym->status}.");
    }

    public function setPlan(int $id, string $plan): void
    {
        Gym::findOrFail($id)->update(['subscription_plan' => $plan]);
        $this->toast('Subscription updated.');
    }

    public function render()
    {
        return view('livewire.admin.gyms', [
            'gyms' => Gym::withCount('branches')
                ->addSelect(['members_count' => Member::withoutGlobalScopes()->selectRaw('count(*)')->whereColumn('gym_id', 'gyms.id')])
                ->with(['users' => fn ($q) => $q->where('role', 'gym_admin')])
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->latest()->paginate(20),
        ]);
    }
}
