<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app'), Title('Financial reports')]
class Reports extends Component
{
    use InteractsWithUi;

    #[Url] public string $from = '';
    #[Url] public string $to = '';
    #[Url] public string $branch = '';
    #[Url] public string $groupBy = 'day';

    public function mount(): void
    {
        $this->requirePermission('finance.view');
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    public function preset(string $p): void
    {
        [$this->from, $this->to, $this->groupBy] = match ($p) {
            'today' => [today()->toDateString(), today()->toDateString(), 'day'],
            'month' => [now()->startOfMonth()->toDateString(), today()->toDateString(), 'day'],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString(), 'day'],
            'year' => [now()->startOfYear()->toDateString(), today()->toDateString(), 'month'],
        };
    }

    private function rows(): array
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();
        $payments = Payment::whereBetween('paid_at', [$from, $to])->when($this->branch, fn ($q) => $q->where('branch_id', $this->branch))->get();
        $expenses = Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->when($this->branch, fn ($q) => $q->where('branch_id', $this->branch))->get();

        $fmt = $this->groupBy === 'month' ? 'Y-m' : 'Y-m-d';
        $period = $this->groupBy === 'month' ? CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to) : CarbonPeriod::create($from, '1 day', $to);

        $rows = [];
        foreach ($period as $d) {
            $k = $d->format($fmt);
            $p = $payments->filter(fn ($x) => $x->paid_at->format($fmt) === $k);
            $revenue = (float) $p->where('type', 'payment')->sum('amount') - (float) $p->where('type', 'refund')->sum('amount');
            $cost = (float) $expenses->filter(fn ($x) => $x->expense_date->format($fmt) === $k)->sum('amount');
            $rows[] = ['label' => $this->groupBy === 'month' ? $d->format('M Y') : $d->format('d M'), 'revenue' => $revenue, 'expenses' => $cost, 'profit' => $revenue - $cost];
        }

        return $rows;
    }

    public function export()
    {
        $rows = $this->rows();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Period', 'Revenue', 'Expenses', 'Profit']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['label'], $r['revenue'], $r['expenses'], $r['profit']]);
            }
            fclose($out);
        }, "financial-report-{$this->from}-to-{$this->to}.csv");
    }

    public function render()
    {
        $rows = $this->rows();
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();

        $branchReport = Branch::orderBy('name')->get()->map(function ($b) use ($from, $to) {
            $rev = (float) Payment::where('branch_id', $b->id)->whereBetween('paid_at', [$from, $to])->where('type', 'payment')->sum('amount')
                - (float) Payment::where('branch_id', $b->id)->whereBetween('paid_at', [$from, $to])->where('type', 'refund')->sum('amount');
            $exp = (float) Expense::where('branch_id', $b->id)->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');

            return ['name' => $b->name, 'revenue' => $rev, 'expenses' => $exp, 'profit' => $rev - $exp];
        });

        $byType = Payment::whereBetween('paid_at', [$from, $to])->where('type', 'payment')->with('invoice')->get()
            ->groupBy(fn ($p) => match ($p->invoice?->billable_type) { 'membership' => 'Memberships', 'pt_subscription' => 'Personal training', default => 'Other' })
            ->map->sum('amount');

        $open = Invoice::with('member')->whereIn('status', ['unpaid', 'partial'])->orderBy('due_date')->get();

        return view('livewire.finance.reports', [
            'rows' => $rows,
            'totals' => ['revenue' => array_sum(array_column($rows, 'revenue')), 'expenses' => array_sum(array_column($rows, 'expenses'))],
            'chart' => [
                'type' => 'bar', 'labels' => array_column($rows, 'label'), 'beginAtZero' => true,
                'datasets' => [
                    ['label' => 'Revenue', 'data' => array_column($rows, 'revenue'), 'color' => '#4f46e5', 'borderRadius' => 4],
                    ['label' => 'Expenses', 'data' => array_column($rows, 'expenses'), 'color' => '#f59e0b', 'borderRadius' => 4],
                ],
            ],
            'branchReport' => $branchReport,
            'byType' => $byType,
            'outstanding' => $open,
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }
}
