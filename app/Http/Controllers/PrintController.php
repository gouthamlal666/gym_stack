<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function invoice(Request $request, Invoice $invoice)
    {
        $this->authorizeBilling($request, $invoice->member_id);

        return view('print.invoice', ['invoice' => $invoice->load('items', 'member', 'gym', 'payments', 'branch')]);
    }

    public function receipt(Request $request, Payment $payment)
    {
        $this->authorizeBilling($request, $payment->member_id);

        return view('print.receipt', ['payment' => $payment->load('invoice', 'member', 'gym', 'receiver')]);
    }

    private function authorizeBilling(Request $request, int $memberId): void
    {
        $user = $request->user();
        abort_unless($user->hasPermission('billing.view') || ($user->isMember() && $user->member?->id === $memberId), 403);
    }
}
