<?php

namespace App\Livewire\Exercises;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Exercise;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Exercise library')]
class Index extends Component
{
    use InteractsWithUi, WithPagination;

    public string $search = '';
    public string $muscle = '';
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public ?string $muscle_group = '';
    public ?string $equipment = '';
    public ?string $video_url = '';
    public ?string $instructions = '';

    public function mount(): void { $this->requirePermission('exercises.manage'); }

    public function updating(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->reset('editingId', 'name', 'muscle_group', 'equipment', 'video_url', 'instructions');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $e = Exercise::whereNotNull('gym_id')->findOrFail($id); // global library entries are read-only
        $this->editingId = $id;
        $this->fill($e->only('name', 'muscle_group', 'equipment', 'video_url', 'instructions'));
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120', 'muscle_group' => 'nullable|string|max:60', 'equipment' => 'nullable|string|max:60',
            'video_url' => 'nullable|url|max:255', 'instructions' => 'nullable|string|max:2000',
        ]);
        if ($this->editingId) {
            Exercise::whereNotNull('gym_id')->findOrFail($this->editingId)->update($data);
        } else {
            Exercise::create($data);
        }
        $this->showForm = false;
        $this->toast('Exercise saved.');
    }

    public function render()
    {
        return view('livewire.exercises.index', [
            'exercises' => Exercise::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->when($this->muscle, fn ($q) => $q->where('muscle_group', $this->muscle))->orderBy('name')->paginate(24),
            'muscles' => Exercise::whereNotNull('muscle_group')->distinct()->orderBy('muscle_group')->pluck('muscle_group'),
        ]);
    }
}
