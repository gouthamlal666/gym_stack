<?php

namespace App\Livewire\Pt;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Member;
use App\Models\Trainer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app'), Title('PT clients')]
class Clients extends Component
{
    use InteractsWithUi;

    #[Url] public string $search = '';
    #[Url] public string $trainer = '';

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('pt.all_clients') || $user->isTrainer(), 403);
    }

    public function render()
    {
        $user = Auth::user();
        $query = Member::pt()->with(['activePtSubscription.package', 'activePtSubscription.trainer.user', 'goals' => fn ($q) => $q->where('status', 'active')]);

        if ($user->isTrainer()) {
            $query->whereIn('id', $user->trainer?->ptClientIds() ?? []);
        } elseif ($this->trainer) {
            $query->whereIn('id', Trainer::find($this->trainer)?->ptClientIds() ?? []);
        }

        $clients = $query->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")))
            ->orderBy('first_name')->get();

        return view('livewire.pt.clients', [
            'clients' => $clients,
            'trainers' => $user->isTrainer() ? collect() : Trainer::with('user')->where('is_pt_trainer', true)->get(),
        ]);
    }
}
