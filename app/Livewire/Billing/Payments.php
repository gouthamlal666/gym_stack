<?php

namespace App\Livewire\Billing;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Payments')]
class Payments extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url] public string $from = '';
    #[Url] public string $to = '';
    #[Url] public string $method = '';

    public function mount(): void
    {
        $this->requirePermission('billing.view');
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    public function updating(): void { $this->resetPage(); }

    public function render()
    {
        $base = Payment::whereBetween('paid_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])
            ->when($this->method, fn ($q) => $q->where('method', $this->method));

        return view('livewire.billing.payments', [
            'payments' => (clone $base)->with('member', 'invoice', 'receiver')->latest('paid_at')->paginate(25),
            'collected' => (clone $base)->where('type', 'payment')->sum('amount'),
            'refunded' => (clone $base)->where('type', 'refund')->sum('amount'),
            'byMethod' => (clone $base)->where('type', 'payment')->selectRaw('method, sum(amount) as total')->groupBy('method')->pluck('total', 'method'),
        ]);
    }
}
