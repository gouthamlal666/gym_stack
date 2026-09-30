<?php

namespace App\Livewire\Pt\Concerns;

use App\Models\PtSubscription;
use App\Services\PtService;
use InvalidArgumentException;

/** Shared booking form: pick a PT subscription, date and free slot from the trainer's availability. */
trait BooksSessions
{
    public $book_subscription_id = '';
    public string $book_date = '';
    public string $book_time = '';
    public ?string $book_focus = '';

    public function bookingSlots(): array
    {
        $sub = $this->book_subscription_id ? PtSubscription::with('trainer', 'package')->find($this->book_subscription_id) : null;
        if (! $sub || ! $this->book_date) {
            return [];
        }

        return app(PtService::class)->availableSlots($sub->trainer, $this->book_date, $sub->package->session_minutes);
    }

    public function updatedBookDate(): void { $this->book_time = ''; }
    public function updatedBookSubscriptionId(): void { $this->book_time = ''; }

    public function book(): void
    {
        abort_unless(auth()->user()->hasPermission('pt.schedule') || auth()->user()->isMember(), 403);
        $this->validate([
            'book_subscription_id' => 'required|exists:pt_subscriptions,id',
            'book_date' => 'required|date|after_or_equal:today',
            'book_time' => 'required|date_format:H:i',
            'book_focus' => 'nullable|string|max:80',
        ], [], ['book_time' => 'time slot']);

        $sub = $this->bookableSubscription($this->book_subscription_id);
        try {
            app(PtService::class)->book($sub, "{$this->book_date} {$this->book_time}", $this->book_focus);
            $this->reset('book_time', 'book_focus');
            $this->modal = null;
            $this->toast('Session booked for '.\Carbon\Carbon::parse("{$this->book_date} {$this->book_time}")->format('D d M, h:i A').'.');
        } catch (InvalidArgumentException $e) {
            $this->addError('book_time', $e->getMessage());
        }
    }

    /** Override to scope which subscriptions the current user may book against. */
    abstract protected function bookableSubscription($id): PtSubscription;
}
