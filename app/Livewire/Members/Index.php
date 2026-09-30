<?php

namespace App\Livewire\Members;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Members')]
class Index extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $type = '';
    #[Url] public string $status = '';
    #[Url] public string $branch = '';

    public function mount(): void
    {
        $this->requirePermission('members.view');
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'type', 'status', 'branch'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $members = Member::query()
            ->with(['currentMembership.plan', 'trainer.user', 'branch'])
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('member_code', 'like', "%{$this->search}%")))
            ->when($this->type, fn ($q) => $q->where('training_type', $this->type))
            ->when($this->branch, fn ($q) => $q->where('branch_id', $this->branch))
            ->when($this->status === 'active', fn ($q) => $q->whereHas('currentMembership'))
            ->when($this->status === 'no_plan', fn ($q) => $q->whereDoesntHave('currentMembership'))
            ->latest()
            ->paginate(15);

        return view('livewire.members.index', ['members' => $members, 'branches' => Branch::orderBy('name')->get()]);
    }
}
