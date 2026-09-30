<?php

namespace App\Livewire\Pt\Client;

use App\Models\FitnessAssessment;

class Fitness extends ClientTab
{
    public ?int $editingId = null;
    public string $assessed_on = '';
    public array $ratings = [];
    public array $tests = [];
    public ?string $notes = '';

    public function openForm(?int $id = null): void
    {
        $this->authorizeCoach();
        $this->resetErrorBag();
        $fa = $id ? FitnessAssessment::with('results')->where('member_id', $this->member->id)->findOrFail($id) : null;
        $this->editingId = $id;
        $this->assessed_on = ($fa?->assessed_on ?? today())->toDateString();
        $this->notes = $fa?->notes ?? '';
        $this->ratings = collect(config('gym.fitness_ratings'))->mapWithKeys(fn ($l, $k) => [$k => $fa?->$k ?? 5])->all();
        $this->tests = collect(config('gym.fitness_tests'))->mapWithKeys(fn ($t, $k) => [$k => $fa?->result($k) ?? ''])->all();
        $this->modal = 'fitness';
    }

    public function save(): void
    {
        $this->authorizeCoach();
        $this->validate([
            'assessed_on' => 'required|date',
            'ratings.*' => 'nullable|integer|between:1,10',
            'tests.*' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        $fa = FitnessAssessment::updateOrCreate(['id' => $this->editingId, 'member_id' => $this->member->id], $this->ratings + [
            'assessed_on' => $this->assessed_on, 'notes' => $this->notes, 'trainer_id' => $this->trainerId(),
        ]);
        $fa->results()->delete();
        foreach ($this->tests as $key => $value) {
            if ($value !== '' && $value !== null) {
                $fa->results()->create(['test_key' => $key, 'value' => $value]);
            }
        }
        $this->modal = null;
        $this->toast('Fitness assessment saved.');
    }

    public function render()
    {
        $all = FitnessAssessment::with('results', 'trainer.user')->where('member_id', $this->member->id)->orderBy('assessed_on')->get();
        $first = $all->first();
        $latest = $all->last();
        $labels = array_values(config('gym.fitness_ratings'));

        return view('livewire.pt.client.fitness', [
            'assessments' => $all,
            'latest' => $latest,
            'radar' => $latest ? ['type' => 'radar', 'labels' => $labels, 'datasets' => array_values(array_filter([
                $all->count() > 1 ? ['label' => $first->assessed_on->format('d M Y'), 'data' => collect(array_keys(config('gym.fitness_ratings')))->map(fn ($k) => $first->$k)->all(), 'color' => '#94a3b8'] : null,
                ['label' => $latest->assessed_on->format('d M Y'), 'data' => collect(array_keys(config('gym.fitness_ratings')))->map(fn ($k) => $latest->$k)->all(), 'color' => '#4f46e5'],
            ]))] : null,
            'canCoach' => $this->canCoach(),
        ]);
    }
}
