<?php

namespace App\Livewire\Trainers;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app'), Title('Trainers')]
class Index extends Component
{
    use InteractsWithUi;

    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public ?string $phone = '';
    public $branch_id = '';
    public string $specializations = '';
    public string $certifications = '';
    public ?string $bio = '';
    public int $experience_years = 0;
    public $commission_rate = 0;
    public bool $is_pt_trainer = true;
    public string $status = 'active';
    public array $availability = [];

    public function mount(): void { $this->requirePermission('trainers.manage'); }

    public function create(): void
    {
        $this->reset();
        $this->resetErrorBag();
        $this->availability = collect(range(1, 7))->mapWithKeys(fn ($d) => [$d => ['on' => $d <= 6, 'start' => '06:00', 'end' => '13:00']])->all();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $t = Trainer::with('user')->findOrFail($id);
        $this->resetErrorBag();
        $this->editingId = $id;
        $this->name = $t->user->name;
        $this->email = $t->user->email;
        $this->phone = $t->user->phone;
        $this->branch_id = $t->branch_id;
        $this->specializations = implode(', ', $t->specializations ?? []);
        $this->certifications = implode(', ', $t->certifications ?? []);
        $this->fill($t->only('bio', 'experience_years', 'commission_rate', 'is_pt_trainer', 'status'));
        $this->availability = collect(range(1, 7))->mapWithKeys(fn ($d) => [$d => isset($t->availability[$d])
            ? ['on' => true] + $t->availability[$d] : ['on' => false, 'start' => '06:00', 'end' => '13:00']])->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $trainer = $this->editingId ? Trainer::findOrFail($this->editingId) : null;
        $this->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($trainer?->user_id)],
            'phone' => 'nullable|string|max:30',
            'branch_id' => 'nullable|exists:branches,id',
            'experience_years' => 'integer|min:0|max:60',
            'commission_rate' => 'numeric|min:0|max:100',
            'availability.*.start' => 'required_if:availability.*.on,true|date_format:H:i',
            'availability.*.end' => 'required_if:availability.*.on,true|date_format:H:i',
        ]);

        $split = fn ($s) => array_values(array_filter(array_map('trim', explode(',', $s))));
        $availability = collect($this->availability)->filter(fn ($d) => $d['on'])->map(fn ($d) => ['start' => $d['start'], 'end' => $d['end']])->all();

        DB::transaction(function () use ($trainer, $split, $availability) {
            $userData = ['name' => $this->name, 'email' => $this->email, 'phone' => $this->phone, 'branch_id' => $this->branch_id ?: null];
            if ($trainer) {
                $trainer->user->update($userData);
            } else {
                $user = User::create($userData + ['gym_id' => auth()->user()->gym_id, 'role' => 'trainer', 'password' => Str::random(32)]);
                Password::sendResetLink(['email' => $user->email]);
            }
            Trainer::updateOrCreate(['id' => $this->editingId], [
                'user_id' => $trainer?->user_id ?? $user->id,
                'branch_id' => $this->branch_id ?: null,
                'specializations' => $split($this->specializations),
                'certifications' => $split($this->certifications),
                'bio' => $this->bio,
                'experience_years' => $this->experience_years,
                'commission_rate' => $this->commission_rate,
                'is_pt_trainer' => $this->is_pt_trainer,
                'status' => $this->status,
                'availability' => $availability,
            ]);
        });

        $this->showForm = false;
        $this->toast($trainer ? 'Trainer updated.' : 'Trainer added — they will get an email to set a password.');
    }

    public function render()
    {
        return view('livewire.trainers.index', [
            'trainers' => Trainer::with('user', 'branch')
                ->withCount(['ptSubscriptions as active_clients' => fn ($q) => $q->where('status', 'active'),
                    'ptSessions as sessions_month' => fn ($q) => $q->where('status', 'completed')->where('scheduled_at', '>=', now()->startOfMonth())])
                ->orderBy('status')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }
}
