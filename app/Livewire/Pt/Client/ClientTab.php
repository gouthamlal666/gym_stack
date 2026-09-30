<?php

namespace App\Livewire\Pt\Client;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Base for PT client tabs: every tab is visible to whoever can view the PT client; edits need coach rights. */
abstract class ClientTab extends Component
{
    use InteractsWithUi;

    #[Locked]
    public Member $member;

    public ?string $modal = null;

    public function mount(Member $member): void
    {
        Gate::authorize('view-pt-member', $member);
        $this->member = $member;
    }

    protected function authorizeCoach(): void
    {
        Gate::authorize('coach-pt-member', $this->member);
    }

    protected function canCoach(): bool
    {
        return Gate::allows('coach-pt-member', $this->member);
    }

    protected function trainerId(): ?int
    {
        return Auth::user()->trainer?->id ?? $this->member->coachId();
    }
}
