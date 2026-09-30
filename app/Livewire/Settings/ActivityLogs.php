<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Activity log')]
class ActivityLogs extends Component
{
    use InteractsWithUi, WithPagination;

    public string $user = '';
    public string $action = '';
    public string $search = '';

    public function mount(): void { $this->requirePermission('activity.view'); }

    public function updating(): void { $this->resetPage(); }

    public function render()
    {
        return view('livewire.settings.activity-logs', [
            'logs' => ActivityLog::with('user')
                ->when($this->user, fn ($q) => $q->where('user_id', $this->user))
                ->when($this->action, fn ($q) => $q->where('action', $this->action))
                ->when($this->search, fn ($q) => $q->where('description', 'like', "%{$this->search}%"))
                ->latest('created_at')->latest('id')->paginate(30),
            'users' => User::where('gym_id', auth()->user()->gym_id)->where('role', '!=', 'member')->orderBy('name')->get(),
            'actions' => ActivityLog::distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
