<?php

namespace App\Livewire\Finance;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use App\Models\Expense;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Expenses')]
class Expenses extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url] public string $month = '';
    #[Url] public string $category = '';

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $expense_category = 'other';
    public $amount = '';
    public string $expense_date = '';
    public $branch_id = '';
    public ?string $vendor = '';
    public ?string $description = '';

    public function mount(): void
    {
        $this->requirePermission('expenses.manage');
        $this->month = $this->month ?: now()->format('Y-m');
    }

    public function create(): void
    {
        $this->reset('editingId', 'amount', 'vendor', 'description');
        $this->expense_category = 'other';
        $this->expense_date = today()->toDateString();
        $this->branch_id = auth()->user()->branch_id ?? Branch::value('id');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $e = Expense::findOrFail($id);
        $this->editingId = $id;
        $this->expense_category = $e->category;
        $this->amount = $e->amount;
        $this->expense_date = $e->expense_date->toDateString();
        $this->branch_id = $e->branch_id;
        $this->vendor = $e->vendor;
        $this->description = $e->description;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'expense_category' => 'required|in:'.implode(',', array_keys(config('gym.expense_categories'))),
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'vendor' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
        ]);
        Expense::updateOrCreate(['id' => $this->editingId], [
            'category' => $this->expense_category, 'amount' => $this->amount, 'expense_date' => $this->expense_date,
            'branch_id' => $this->branch_id ?: null, 'vendor' => $this->vendor, 'description' => $this->description,
        ] + ($this->editingId ? [] : ['recorded_by' => auth()->id()]));
        $this->showForm = false;
        $this->toast('Expense saved.');
    }

    public function delete(int $id): void
    {
        Expense::findOrFail($id)->delete();
        $this->toast('Expense deleted.');
    }

    public function render()
    {
        [$y, $m] = explode('-', $this->month);
        $base = Expense::whereYear('expense_date', $y)->whereMonth('expense_date', $m);

        return view('livewire.finance.expenses', [
            'expenses' => (clone $base)->with('branch', 'recorder')->when($this->category, fn ($q) => $q->where('category', $this->category))->latest('expense_date')->paginate(25),
            'total' => (clone $base)->sum('amount'),
            'byCategory' => (clone $base)->selectRaw('category, sum(amount) as total')->groupBy('category')->orderByDesc('total')->pluck('total', 'category'),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }
}
