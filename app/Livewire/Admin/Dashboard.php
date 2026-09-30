<?php

namespace App\Livewire\Admin;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app'), Title('Platform overview')]
class Dashboard extends Component
{
    public function render()
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        return view('livewire.admin.dashboard', [
            'gyms' => Gym::count(),
            'activeGyms' => Gym::where('status', 'active')->count(),
            'members' => Member::withoutGlobalScopes()->count(),
            'staff' => User::whereNotIn('role', ['member', 'super_admin'])->count(),
            'gmv' => Payment::withoutGlobalScopes()->where('type', 'payment')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'recent' => Gym::latest()->limit(8)->get(),
            'chart' => ['type' => 'line', 'labels' => $months->map->format('M')->all(), 'beginAtZero' => true, 'datasets' => [
                ['label' => 'New gyms', 'data' => $months->map(fn ($m) => Gym::whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count())->all()],
                ['label' => 'New members (all gyms)', 'data' => $months->map(fn ($m) => Member::withoutGlobalScopes()->whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count())->all()],
            ]],
        ]);
    }
}
