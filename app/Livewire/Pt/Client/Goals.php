<?php

namespace App\Livewire\Pt\Client;

use App\Models\PtGoal;

class Goals extends ClientTab
{
    public ?int $editingId = null;
    public string $goal_type = 'weight_loss';
    public string $title = '';
    public string $metric = 'weight';
    public $start_value = '';
    public $target_value = '';
    public string $start_date = '';
    public ?string $target_date = '';

    public function openForm(?int $id = null): void
    {
        $this->authorizeCoach();
        $this->resetErrorBag();
        $g = $id ? PtGoal::where('member_id', $this->member->id)->findOrFail($id) : null;
        $this->editingId = $id;
        $this->goal_type = $g?->goal_type ?? 'weight_loss';
        $this->title = $g?->title ?? '';
        $this->metric = $g?->metric ?? 'weight';
        $this->start_value = $g?->start_value ?? $this->latest('weight');
        $this->target_value = $g?->target_value ?? '';
        $this->start_date = ($g?->start_date ?? today())->toDateString();
        $this->target_date = $g?->target_date?->toDateString() ?? today()->addWeeks(12)->toDateString();
        $this->modal = 'goal';
    }

    public function updatedMetric(): void
    {
        $this->start_value = $this->latest($this->metric) ?? $this->start_value;
    }

    private function latest(string $metric)
    {
        return $this->member->bodyAssessments()->whereNotNull($metric)->latest('assessed_on')->value($metric) ?? '';
    }

    public function save(): void
    {
        $this->authorizeCoach();
        $this->validate([
            'goal_type' => 'required|in:'.implode(',', array_keys(config('gym.goal_types'))),
            'title' => 'required|string|max:120',
            'metric' => 'required|in:'.implode(',', array_keys(config('gym.body_metrics'))),
            'start_value' => 'required|numeric', 'target_value' => 'required|numeric|different:start_value',
            'start_date' => 'required|date', 'target_date' => 'nullable|date|after:start_date',
        ]);
        PtGoal::updateOrCreate(['id' => $this->editingId, 'member_id' => $this->member->id], [
            'trainer_id' => $this->trainerId(), 'goal_type' => $this->goal_type, 'title' => $this->title, 'metric' => $this->metric,
            'start_value' => $this->start_value, 'target_value' => $this->target_value, 'unit' => config("gym.body_metrics.{$this->metric}.unit") ?: '-',
            'start_date' => $this->start_date, 'target_date' => $this->target_date ?: null,
        ]);
        $this->modal = null;
        $this->toast('Goal saved.');
    }

    public function setStatus(int $id, string $status): void
    {
        $this->authorizeCoach();
        abort_unless(in_array($status, ['active', 'achieved', 'abandoned']), 422);
        PtGoal::where('member_id', $this->member->id)->findOrFail($id)->update(['status' => $status]);
        $this->toast('Goal updated.');
    }

    public function render()
    {
        return view('livewire.pt.client.goals', [
            'goals' => PtGoal::where('member_id', $this->member->id)->orderByRaw("case status when 'active' then 0 else 1 end")->latest()->get(),
            'canCoach' => $this->canCoach(),
        ]);
    }
}
