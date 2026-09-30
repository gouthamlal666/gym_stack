<?php

namespace App\Livewire\Pt\Client;

use App\Livewire\Pt\Concerns\BooksSessions;
use App\Models\PtSession;
use App\Models\PtSubscription;
use Livewire\WithPagination;

class Sessions extends ClientTab
{
    use BooksSessions, WithPagination;

    public function openBooking(): void
    {
        abort_unless(auth()->user()->hasPermission('pt.schedule'), 403);
        $this->book_subscription_id = $this->member->activePtSubscription?->id ?? '';
        $this->book_date = today()->addDay()->toDateString();
        $this->modal = 'book';
    }

    protected function bookableSubscription($id): PtSubscription
    {
        return PtSubscription::where('member_id', $this->member->id)->findOrFail($id);
    }

    public function render()
    {
        $subs = PtSubscription::with('package', 'trainer.user')->where('member_id', $this->member->id)->latest('start_date')->get();

        return view('livewire.pt.client.sessions', [
            'subscriptions' => $subs,
            'sessions' => PtSession::with('trainer.user')->where('member_id', $this->member->id)->latest('scheduled_at')->paginate(15),
            'slots' => $this->modal === 'book' ? $this->bookingSlots() : [],
            'canBook' => auth()->user()->hasPermission('pt.schedule'),
            'stats' => PtSession::where('member_id', $this->member->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }
}
