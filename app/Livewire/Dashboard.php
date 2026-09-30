<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PtSession;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app'), Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        if ($user->isTrainer()) {
            return view('livewire.dashboard-trainer', $this->trainerData($user));
        }

        $monthStart = now()->startOfMonth();
        $net = fn ($from, $to) => (float) Payment::whereBetween('paid_at', [$from, $to])->where('type', 'payment')->sum('amount')
            - (float) Payment::whereBetween('paid_at', [$from, $to])->where('type', 'refund')->sum('amount');

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        $revenueChart = [
            'type' => 'bar',
            'labels' => $months->map->format('M')->all(),
            'beginAtZero' => true,
            'datasets' => [
                ['label' => 'Revenue', 'data' => $months->map(fn ($m) => $net($m, $m->copy()->endOfMonth()))->all(), 'color' => '#4f46e5', 'borderRadius' => 6],
                ['label' => 'Expenses', 'data' => $months->map(fn ($m) => (float) Expense::whereBetween('expense_date', [$m, $m->copy()->endOfMonth()])->sum('amount'))->all(), 'color' => '#f59e0b', 'borderRadius' => 6],
            ],
        ];

        $outstanding = Invoice::whereIn('status', ['unpaid', 'partial'])->get();

        return view('livewire.dashboard', [
            'activeMembers' => Member::where('status', 'active')->whereHas('currentMembership')->count(),
            'totalMembers' => Member::count(),
            'ptClients' => Member::pt()->where('status', 'active')->count(),
            'checkinsToday' => Attendance::whereDate('check_in_at', today())->count(),
            'revenueMonth' => $net($monthStart, now()),
            'outstanding' => $outstanding->sum(fn ($i) => $i->balance()),
            'overdueCount' => $outstanding->filter->isOverdue()->count(),
            'expiring' => Membership::with('member', 'plan')->where('status', 'active')
                ->whereDate('end_date', '<=', today()->addDays(7))->orderBy('end_date')->limit(6)->get(),
            'todaySessions' => PtSession::with('member', 'trainer.user')->whereDate('scheduled_at', today())
                ->whereNotIn('status', ['cancelled'])->orderBy('scheduled_at')->get(),
            'recentPayments' => Payment::with('member')->latest('paid_at')->limit(6)->get(),
            'revenueChart' => $revenueChart,
        ]);
    }

    private function trainerData($user): array
    {
        $trainer = $user->trainer;
        $clientIds = $trainer?->ptClientIds() ?? [];

        return [
            'trainer' => $trainer,
            'todaySessions' => PtSession::with('member')->where('trainer_id', $trainer?->id)
                ->whereDate('scheduled_at', today())->orderBy('scheduled_at')->get(),
            'upcoming' => PtSession::with('member')->where('trainer_id', $trainer?->id)->where('status', 'scheduled')
                ->where('scheduled_at', '>', now()->endOfDay())->orderBy('scheduled_at')->limit(8)->get(),
            'clients' => Member::with('activePtSubscription.package')->whereIn('id', $clientIds)->get(),
            'completedMonth' => PtSession::where('trainer_id', $trainer?->id)->where('status', 'completed')
                ->where('scheduled_at', '>=', now()->startOfMonth())->count(),
        ];
    }
}
