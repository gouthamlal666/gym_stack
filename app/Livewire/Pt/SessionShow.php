<?php

namespace App\Livewire\Pt;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Exercise;
use App\Models\PtSession;
use App\Models\PtWorkoutPlan;
use App\Services\PtService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Run a PT session: start/end, log exercises (sets, reps, weight, duration), calories, notes and feedback. */
#[Layout('layouts.app')]
class SessionShow extends Component
{
    use InteractsWithUi;

    public PtSession $session;
    public ?string $modal = null;

    public array $exercises = [];
    public $calories = '';
    public ?string $trainer_notes = '';
    public ?string $trainer_feedback = '';
    public $rating = '';
    public ?string $focus = '';

    public string $new_datetime = '';
    public string $cancel_reason = '';

    public function mount(PtSession $session): void
    {
        $this->requirePermission('pt.schedule');
        Gate::authorize('view-pt-member', $session->member);
        $this->session = $session;
        $this->fill($session->only('calories', 'trainer_notes', 'trainer_feedback', 'rating', 'focus'));
        $this->exercises = $session->exercises->map(fn ($e) => $e->only('name', 'sets', 'reps', 'weight', 'duration_minutes', 'notes'))->all();
        $this->new_datetime = $session->scheduled_at->format('Y-m-d\TH:i');
    }

    /** Pre-fill from the client's active PT workout plan for this weekday. */
    public function loadFromPlan(): void
    {
        $plan = PtWorkoutPlan::with('days.exercises')->where('member_id', $this->session->member_id)->where('is_active', true)->first();
        $day = $plan?->days->firstWhere('day_of_week', $this->session->scheduled_at->dayOfWeekIso);
        if (! $day || $day->exercises->isEmpty()) {
            $this->toast('No planned exercises for '.$this->session->scheduled_at->format('l').'.', 'error');

            return;
        }
        $this->focus = $this->focus ?: $day->focus;
        $this->exercises = $day->exercises->map(fn ($e) => ['name' => $e->name, 'sets' => $e->sets, 'reps' => $e->reps,
            'weight' => is_numeric($e->weight) ? $e->weight : '', 'duration_minutes' => '', 'notes' => $e->notes])->all();
    }

    public function addExercise(): void
    {
        $this->exercises[] = ['name' => '', 'sets' => 3, 'reps' => '10', 'weight' => '', 'duration_minutes' => '', 'notes' => ''];
    }

    public function removeExercise(int $i): void
    {
        unset($this->exercises[$i]);
        $this->exercises = array_values($this->exercises);
    }

    private function coach(): void
    {
        Gate::authorize('coach-pt-member', $this->session->member);
    }

    private function attempt(callable $fn, string $ok): void
    {
        try {
            $fn();
            $this->modal = null;
            $this->session->refresh();
            $this->toast($ok);
        } catch (InvalidArgumentException $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function start(PtService $pt): void
    {
        $this->coach();
        $this->attempt(fn () => $pt->start($this->session), 'Session started — timer running.');
    }

    public function saveLog(): void
    {
        $this->coach();
        $this->validateLog();
        $this->persistLog();
        $this->toast('Session log saved.');
    }

    public function complete(PtService $pt): void
    {
        $this->coach();
        $this->validateLog();
        DB::transaction(function () use ($pt) {
            $this->persistLog();
            $pt->complete($this->session, []);
        });
        $this->session->refresh();
        $this->toast('Session completed. '.$this->session->subscription->remainingSessions().' session(s) remaining.');
    }

    public function noShow(PtService $pt): void
    {
        $this->attempt(fn () => $pt->markNoShow($this->session), 'Marked as no-show.');
    }

    public function reschedule(PtService $pt): void
    {
        $this->validate(['new_datetime' => 'required|date|after:now']);
        $this->attempt(fn () => $pt->reschedule($this->session, $this->new_datetime), 'Session rescheduled.');
    }

    public function cancel(PtService $pt): void
    {
        $this->validate(['cancel_reason' => 'required|string|max:200']);
        $this->attempt(fn () => $pt->cancel($this->session, $this->cancel_reason), 'Session cancelled — it does not count against the package.');
    }

    private function validateLog(): void
    {
        $this->validate([
            'focus' => 'nullable|string|max:80',
            'exercises.*.name' => 'required|string|max:120',
            'exercises.*.sets' => 'nullable|integer|between:1,50',
            'exercises.*.weight' => 'nullable|numeric|min:0',
            'exercises.*.duration_minutes' => 'nullable|integer|min:0',
            'calories' => 'nullable|integer|min:0|max:5000',
            'rating' => 'nullable|integer|between:1,5',
            'trainer_notes' => 'nullable|string|max:2000',
            'trainer_feedback' => 'nullable|string|max:2000',
        ], [], ['exercises.*.name' => 'exercise name']);
    }

    private function persistLog(): void
    {
        $library = Exercise::pluck('id', 'name');
        $this->session->update([
            'focus' => $this->focus, 'calories' => $this->calories ?: null, 'rating' => $this->rating ?: null,
            'trainer_notes' => $this->trainer_notes, 'trainer_feedback' => $this->trainer_feedback,
        ]);
        $this->session->exercises()->delete();
        foreach (array_values($this->exercises) as $i => $e) {
            $this->session->exercises()->create(collect($e)->map(fn ($v) => $v === '' ? null : $v)->all() + ['sort' => $i, 'exercise_id' => $library[$e['name']] ?? null]);
        }
    }

    public function render()
    {
        $this->session->load('member', 'trainer.user', 'subscription.package');
        $previous = PtSession::with('exercises')->where('member_id', $this->session->member_id)->where('status', 'completed')
            ->where('scheduled_at', '<', $this->session->scheduled_at)->latest('scheduled_at')->first();

        return view('livewire.pt.session-show', [
            'previous' => $previous,
            'library' => Exercise::orderBy('name')->pluck('name'),
            'canCoach' => Gate::allows('coach-pt-member', $this->session->member),
            'open' => in_array($this->session->status, ['scheduled', 'in_progress']),
        ])->title('PT session · '.$this->session->member->name);
    }
}
