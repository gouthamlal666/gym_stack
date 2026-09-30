<?php

namespace App\Livewire\Pt;

use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Pt\Concerns\BooksSessions;
use App\Models\PtSession;
use App\Models\PtSubscription;
use App\Models\Trainer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app'), Title('PT schedule')]
class Schedule extends Component
{
    use BooksSessions, InteractsWithUi;

    #[Url] public string $week = '';
    #[Url] public string $trainer = '';
    public ?string $modal = null;

    public function mount(): void
    {
        $this->requirePermission('pt.schedule');
        $this->week = $this->week ?: now()->startOfWeek()->toDateString();
        if (Auth::user()->isTrainer()) {
            $this->trainer = (string) Auth::user()->trainer?->id;
        }
    }

    public function shiftWeek(int $weeks): void
    {
        $this->week = Carbon::parse($this->week)->addWeeks($weeks)->toDateString();
    }

    public function thisWeek(): void
    {
        $this->week = now()->startOfWeek()->toDateString();
    }

    public function openBooking(?string $date = null, ?string $time = null): void
    {
        $this->resetErrorBag();
        $this->book_date = $date ?? today()->toDateString();
        $this->book_time = $time ?? '';
        $this->modal = 'book';
    }

    protected function bookableSubscription($id): PtSubscription
    {
        return $this->subscriptionsQuery()->findOrFail($id);
    }

    private function subscriptionsQuery()
    {
        return PtSubscription::with('member', 'package', 'trainer.user')->where('status', 'active')
            ->when(Auth::user()->isTrainer(), fn ($q) => $q->where('trainer_id', Auth::user()->trainer?->id))
            ->when(! Auth::user()->isTrainer() && $this->trainer, fn ($q) => $q->where('trainer_id', $this->trainer));
    }

    public function render()
    {
        $start = Carbon::parse($this->week)->startOfWeek();
        $end = $start->copy()->endOfWeek();
        $sessions = PtSession::with('member', 'trainer.user')
            ->whereBetween('scheduled_at', [$start, $end])
            ->when($this->trainer, fn ($q) => $q->where('trainer_id', $this->trainer))
            ->orderBy('scheduled_at')->get();

        $minHour = min(6, (int) ($sessions->min(fn ($s) => $s->scheduled_at->hour) ?? 6));
        $maxHour = max(21, (int) ($sessions->max(fn ($s) => $s->scheduled_at->hour) ?? 21));

        return view('livewire.pt.schedule', [
            'days' => collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i)),
            'hours' => range($minHour, $maxHour),
            'grid' => $sessions->groupBy(fn ($s) => $s->scheduled_at->format('Y-m-d H')),
            'sessions' => $sessions,
            'trainers' => Auth::user()->isTrainer() ? collect() : Trainer::with('user')->where('is_pt_trainer', true)->where('status', 'active')->get(),
            'subscriptions' => $this->modal === 'book' ? $this->subscriptionsQuery()->get()->filter->isUsable() : collect(),
            'slots' => $this->modal === 'book' ? $this->bookingSlots() : [],
            'start' => $start, 'end' => $end,
        ]);
    }
}
