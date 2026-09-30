<?php

namespace App\Livewire\Billing;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Invoice;
use App\Models\Member;
use App\Services\BillingService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Invoices')]
class Invoices extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url] public string $status = '';
    #[Url] public string $search = '';

    // Manual invoice (e.g. merchandise, locker, supplements)
    public bool $showForm = false;
    public $member_id = '';
    public string $memberSearch = '';
    public array $items = [['description' => '', 'quantity' => 1, 'unit_price' => '']];
    public $discount = 0;
    public bool $apply_tax = true;
    public string $due_date = '';

    public function mount(): void { $this->requirePermission('billing.view'); }

    public function updating($name): void
    {
        if (in_array($name, ['status', 'search'])) {
            $this->resetPage();
        }
    }

    public function newInvoice(): void
    {
        $this->requirePermission('billing.collect');
        $this->reset('member_id', 'memberSearch', 'items', 'discount', 'apply_tax');
        $this->due_date = today()->addDays(7)->toDateString();
        $this->showForm = true;
    }

    public function addItem(): void { $this->items[] = ['description' => '', 'quantity' => 1, 'unit_price' => '']; }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
    }

    public function create(BillingService $billing)
    {
        $this->requirePermission('billing.collect');
        $this->validate([
            'member_id' => 'required|exists:members,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:200',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount' => 'numeric|min:0',
            'due_date' => 'required|date',
        ], [], ['items.*.description' => 'description', 'items.*.unit_price' => 'price']);

        $invoice = $billing->createInvoice(Member::findOrFail($this->member_id), array_map(fn ($i) => [
            'description' => $i['description'], 'quantity' => (int) $i['quantity'], 'unit_price' => (float) $i['unit_price'],
        ], $this->items), (float) $this->discount, null, $this->due_date, $this->apply_tax ? null : 0);

        return $this->redirectRoute('invoices.show', $invoice, navigate: true);
    }

    public function render()
    {
        $invoices = Invoice::with('member')
            ->when($this->status === 'overdue', fn ($q) => $q->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today()))
            ->when($this->status && $this->status !== 'overdue', fn ($q) => $q->where('status', $this->status))
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")
                ->orWhereHas('member', fn ($m) => $m->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%"))))
            ->latest('issue_date')->latest('id')->paginate(20);

        $open = Invoice::whereIn('status', ['unpaid', 'partial'])->get();

        return view('livewire.billing.invoices', [
            'invoices' => $invoices,
            'outstanding' => $open->sum(fn ($i) => $i->balance()),
            'overdue' => $open->filter->isOverdue()->sum(fn ($i) => $i->balance()),
            'memberResults' => strlen($this->memberSearch) >= 2 ? Member::where(fn ($q) => $q->where('first_name', 'like', "%{$this->memberSearch}%")
                ->orWhere('last_name', 'like', "%{$this->memberSearch}%")->orWhere('member_code', 'like', "%{$this->memberSearch}%"))->limit(5)->get() : collect(),
            'selectedMember' => $this->member_id ? Member::find($this->member_id) : null,
        ]);
    }
}
