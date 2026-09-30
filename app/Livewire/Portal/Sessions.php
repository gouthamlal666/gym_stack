<?php

namespace App\Livewire\Portal;

use App\Livewire\Pt\Concerns\BooksSessions;
use App\Models\PtSession;
use App\Models\PtSubscription;
use App\Services\PtService;
use InvalidArgumentException;
use Livewire\Attributes\Title;

/** PT customer self-service: book in the trainer's free slots, cancel with notice, see feedback. */
#[Title('My PT sessions')]
class Sessions extends PortalPage
{
    use BooksSessions;

    public const CANCEL_NOTICE_HOURS = 12;

    public function openBooking(): void
    {
        $this->book_subscription_id = $this->member()->activePtSubscription?->id ?? '';
        $this->book_date = today()->addDay()->toDateString();
        $this->modal = 'book';
    }

    protected function bookableSubscription($id): PtSubscription
    {
        return PtSubscription::where('member_id', $this->member()->id)->findOrFail($id);
    }

    public function cancelSession(int $id, PtService $pt): void
    {
        $session = PtSession::where('member_id', $this->member()->id)->findOrFail($id);
        if ($session->scheduled_at->lt(now()->addHours(self::CANCEL_NOTICE_HOURS))) {
            $this->toast('Sessions can only be cancelled '.self::CANCEL_NOTICE_HOURS.'h in advance. Please contact your trainer.', 'error');

            return;
        }
        try {
            $pt->cancel($session, 'Cancelled by member');
            $this->toast('Session cancelled.');
        } catch (InvalidArgumentException $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        $member = $this->member();

        return view('livewire.portal.sessions', [
            'upcoming' => PtSession::with('trainer.user')->where('member_id', $member->id)->whereIn('status', ['scheduled', 'in_progress'])->orderBy('scheduled_at')->get(),
            'history' => PtSession::with('trainer.user', 'exercises')->where('member_id', $member->id)->whereNotIn('status', ['scheduled', 'in_progress'])->latest('scheduled_at')->limit(20)->get(),
            'subscriptions' => PtSubscription::with('package', 'trainer.user')->where('member_id', $member->id)->where('status', 'active')->get(),
            'slots' => $this->modal === 'book' ? $this->bookingSlots() : [],
            'noticeHours' => self::CANCEL_NOTICE_HOURS,
        ]);
    }
}
