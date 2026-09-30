<?php

namespace App\Livewire\Trainers;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Payment;
use App\Models\PtSession;
use App\Models\Trainer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    use InteractsWithUi;

    public Trainer $trainer;
    #[Url] public string $month = '';

    public function mount(Trainer $trainer): void
    {
        $this->requirePermission('trainers.manage');
        $this->trainer = $trainer;
        $this->month = $this->month ?: now()->format('Y-m');
    }

    public function render()
    {
        $start = now()->parse($this->month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $sessions = PtSession::where('trainer_id', $this->trainer->id)->whereBetween('scheduled_at', [$start, $end])->get();

        // PT revenue collected this month on this trainer's PT packages → commission
        $ptRevenue = (float) Payment::whereBetween('paid_at', [$start, $end])->where('type', 'payment')
            ->whereHas('invoice', fn ($q) => $q->where('billable_type', 'pt_subscription')
                ->whereIn('billable_id', $this->trainer->ptSubscriptions()->pluck('id')))->sum('amount');

        $trend = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        return view('livewire.trainers.show', [
            'completed' => $sessions->where('status', 'completed')->count(),
            'noShows' => $sessions->where('status', 'no_show')->count(),
            'cancelled' => $sessions->where('status', 'cancelled')->count(),
            'scheduled' => $sessions->whereIn('status', ['scheduled', 'in_progress'])->count(),
            'avgRating' => round((float) $sessions->whereNotNull('rating')->avg('rating'), 1),
            'ptRevenue' => $ptRevenue,
            'commission' => round($ptRevenue * (float) $this->trainer->commission_rate / 100, 2),
            'clients' => $this->trainer->ptSubscriptions()->with('member', 'package')->where('status', 'active')->get(),
            'chart' => ['type' => 'bar', 'labels' => $trend->map->format('M')->all(), 'beginAtZero' => true, 'datasets' => [[
                'label' => 'Completed sessions', 'borderRadius' => 6,
                'data' => $trend->map(fn ($m) => PtSession::where('trainer_id', $this->trainer->id)->where('status', 'completed')
                    ->whereBetween('scheduled_at', [$m, $m->copy()->endOfMonth()])->count())->all(),
            ]]],
        ])->title($this->trainer->name);
    }
}
