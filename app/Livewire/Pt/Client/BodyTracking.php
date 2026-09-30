<?php

namespace App\Livewire\Pt\Client;

use App\Models\BodyAssessment;
use App\Support\PtProgress;

class BodyTracking extends ClientTab
{
    public $compareA = '';
    public $compareB = '';

    public ?int $editingId = null;
    public string $assessed_on = '';
    public ?string $next_assessment_on = '';
    public array $metrics = [];
    public $height = '';
    public ?string $notes = '';

    public function openForm(?int $id = null): void
    {
        $this->authorizeCoach();
        $this->resetErrorBag();
        $this->editingId = $id;
        $a = $id ? BodyAssessment::where('member_id', $this->member->id)->findOrFail($id) : null;
        $last = PtProgress::assessments($this->member)->last();

        $this->assessed_on = ($a?->assessed_on ?? today())->toDateString();
        $this->next_assessment_on = ($a?->next_assessment_on ?? today()->addDays(14))->toDateString();
        $this->height = $a?->height ?? $last?->height ?? '';
        $this->notes = $a?->notes ?? '';
        $this->metrics = collect(config('gym.body_metrics'))->except('bmi')->mapWithKeys(fn ($m, $k) => [$k => $a?->$k ?? ''])->all();
        $this->modal = 'assessment';
    }

    public function save(): void
    {
        $this->authorizeCoach();
        $rules = ['assessed_on' => 'required|date', 'next_assessment_on' => 'nullable|date|after:assessed_on', 'height' => 'nullable|numeric|between:50,250', 'notes' => 'nullable|string|max:1000',
            'metrics.weight' => 'required|numeric|between:20,400'];
        foreach (array_keys($this->metrics) as $k) {
            $rules["metrics.$k"] ??= 'nullable|numeric|between:0,400';
        }
        $this->validate($rules, [], ['metrics.weight' => 'weight']);

        $data = collect($this->metrics)->map(fn ($v) => $v === '' ? null : $v)->all() + [
            'assessed_on' => $this->assessed_on, 'next_assessment_on' => $this->next_assessment_on ?: null,
            'height' => $this->height ?: null, 'notes' => $this->notes,
        ];

        if ($this->editingId) {
            BodyAssessment::where('member_id', $this->member->id)->findOrFail($this->editingId)->update($data);
        } else {
            BodyAssessment::create($data + [
                'member_id' => $this->member->id,
                'trainer_id' => $this->trainerId(),
                'is_initial' => ! BodyAssessment::where('member_id', $this->member->id)->exists(),
            ]);
        }
        $this->modal = null;
        $this->toast('Assessment saved.');
    }

    public function delete(int $id): void
    {
        $this->authorizeCoach();
        BodyAssessment::where('member_id', $this->member->id)->findOrFail($id)->delete();
        $this->toast('Assessment deleted.');
    }

    public function render()
    {
        $all = PtProgress::assessments($this->member);
        $a = $all->firstWhere('id', (int) $this->compareA) ?? $all->first();
        $b = $all->firstWhere('id', (int) $this->compareB) ?? $all->last();

        return view('livewire.pt.client.body-tracking', [
            'assessments' => $all,
            'rows' => PtProgress::compare($a, $b),
            'a' => $a, 'b' => $b,
            'weightChart' => PtProgress::lineChart($all, ['weight']),
            'fatChart' => PtProgress::lineChart($all, ['body_fat', 'muscle_mass']),
            'measureChart' => PtProgress::lineChart($all, ['chest', 'waist', 'hip', 'biceps', 'thigh']),
            'canCoach' => $this->canCoach(),
        ]);
    }
}
