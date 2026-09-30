<?php

namespace App\Livewire\Portal;

use App\Models\BodyAssessment;
use App\Models\FitnessAssessment;
use App\Models\ProgressPhoto;
use App\Support\PtProgress;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

/** PT customer's own progress: body measurements, before/after photos, fitness tests and goals (read-only). */
#[Title('My progress')]
class Progress extends PortalPage
{
    #[Url] public string $tab = 'body';
    public $compareA = '';
    public $compareB = '';
    public $beforeDate = '';
    public $afterDate = '';

    public function render()
    {
        $member = $this->member();
        Gate::authorize('view-pt-member', $member);
        $data = ['member' => $member];

        if ($this->tab === 'body') {
            $all = PtProgress::assessments($member);
            $a = $all->firstWhere('id', (int) $this->compareA) ?? $all->first();
            $b = $all->firstWhere('id', (int) $this->compareB) ?? $all->last();
            $data += ['assessments' => $all, 'rows' => PtProgress::compare($a, $b), 'a' => $a, 'b' => $b,
                'weightChart' => PtProgress::lineChart($all, ['weight']), 'fatChart' => PtProgress::lineChart($all, ['body_fat', 'muscle_mass']),
                'measureChart' => PtProgress::lineChart($all, ['chest', 'waist', 'hip', 'biceps', 'thigh'])];
        }

        if ($this->tab === 'photos') {
            Gate::authorize('view-progress-photos', $member);
            $photos = ProgressPhoto::where('member_id', $member->id)->orderByDesc('taken_on')->get();
            $dates = $photos->pluck('taken_on')->map->toDateString()->unique()->values();
            $before = $this->beforeDate ?: $dates->last();
            $after = $this->afterDate ?: $dates->first();
            $assessments = BodyAssessment::where('member_id', $member->id)->get();
            $nearest = fn ($d) => $d ? $assessments->sortBy(fn ($x) => abs($x->assessed_on->diffInDays($d)))->first() : null;
            $data += ['dates' => $dates, 'before' => $before, 'after' => $after,
                'beforePhotos' => $photos->filter(fn ($p) => $p->taken_on->toDateString() === $before)->keyBy('angle'),
                'afterPhotos' => $photos->filter(fn ($p) => $p->taken_on->toDateString() === $after)->keyBy('angle'),
                'beforeStats' => $nearest($before), 'afterStats' => $nearest($after)];
        }

        if ($this->tab === 'fitness') {
            $data['fitness'] = FitnessAssessment::with('results')->where('member_id', $member->id)->orderBy('assessed_on')->get();
        }

        if ($this->tab === 'goals') {
            $data['goals'] = $member->goals()->latest()->get();
        }

        return view('livewire.portal.progress', $data);
    }
}
