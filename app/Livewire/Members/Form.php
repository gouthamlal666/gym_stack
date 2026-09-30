<?php

namespace App\Livewire\Members;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Trainer;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Form extends Component
{
    use InteractsWithUi, WithFileUploads;

    public ?Member $member = null;

    public string $first_name = '';
    public ?string $last_name = '';
    public ?string $email = '';
    public string $phone = '';
    public ?string $gender = '';
    public ?string $date_of_birth = null;
    public ?string $address = '';
    public string $training_type = 'regular';
    public $branch_id = '';
    public $trainer_id = '';
    public ?string $emergency_contact_name = '';
    public ?string $emergency_contact_phone = '';
    public ?string $emergency_contact_relation = '';
    public ?string $medical_notes = '';
    public string $joined_on = '';
    public string $status = 'active';
    public $photo;

    // Create-only extras
    public $plan_id = '';
    public float $discount = 0;
    public bool $create_login = false;

    public function mount(?Member $member = null): void
    {
        $this->requirePermission('members.manage');
        $this->joined_on = today()->toDateString();
        $this->branch_id = auth()->user()->branch_id ?? Branch::value('id');

        if ($member?->exists) {
            $this->member = $member;
            $this->fill($member->only([
                'first_name', 'last_name', 'email', 'phone', 'gender', 'address', 'training_type', 'branch_id', 'trainer_id',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation', 'medical_notes', 'status',
            ]));
            $this->date_of_birth = $member->date_of_birth?->toDateString();
            $this->joined_on = $member->joined_on->toDateString();
        }
    }

    public function save(MembershipService $memberships)
    {
        $data = $this->validate([
            'first_name' => 'required|string|max:80',
            'last_name' => 'nullable|string|max:80',
            'email' => ['nullable', 'email', 'max:190', Rule::requiredIf($this->create_login),
                Rule::unique('users', 'email')->ignore($this->member?->user_id)->where(fn ($q) => $q->whereNotNull('email'))],
            'phone' => 'required|string|max:30',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
            'training_type' => 'required|in:regular,pt',
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('gym_id', auth()->user()->gym_id)],
            'trainer_id' => ['nullable', Rule::exists('trainers', 'id')->where('gym_id', auth()->user()->gym_id)],
            'emergency_contact_name' => 'nullable|string|max:120',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relation' => 'nullable|string|max:60',
            'medical_notes' => 'nullable|string|max:1000',
            'joined_on' => 'required|date',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:3072',
            'plan_id' => ['nullable', Rule::exists('membership_plans', 'id')->where('gym_id', auth()->user()->gym_id)],
            'discount' => 'numeric|min:0',
        ]);

        $attributes = collect($data)->except(['photo', 'plan_id', 'discount'])
            ->map(fn ($v) => $v === '' ? null : $v)->all();
        if ($this->photo) {
            $attributes['photo_path'] = $this->photo->store('members', 'public');
        }

        $invoice = null;
        $member = DB::transaction(function () use ($attributes, $memberships, &$invoice) {
            if ($this->member) {
                $this->member->update($attributes);
                $this->member->user?->update(['name' => $this->member->name, 'email' => $this->member->email ?? $this->member->user->email]);
                $member = $this->member;
            } else {
                $member = Member::create($attributes + ['member_code' => Member::nextCode($this->gym())]);
                if ($this->plan_id) {
                    $invoice = $memberships->subscribe($member, MembershipPlan::findOrFail($this->plan_id), $this->joined_on, (float) $this->discount, true);
                }
            }

            if ($this->create_login && ! $member->user_id && $member->email) {
                $user = User::create([
                    'gym_id' => $member->gym_id, 'branch_id' => $member->branch_id, 'name' => $member->name,
                    'email' => $member->email, 'phone' => $member->phone, 'role' => 'member', 'password' => Str::random(32),
                ]);
                $member->update(['user_id' => $user->id]);
                Password::sendResetLink(['email' => $user->email]); // lets the member set their own password
            }

            return $member;
        });

        session()->flash('toast', $this->member ? 'Member updated.' : 'Member created'.($invoice ? " · invoice {$invoice->number} raised." : '.'));

        return $this->redirect($invoice ? route('invoices.show', $invoice) : route('members.show', $member), navigate: true);
    }

    public function render()
    {
        return view('livewire.members.form', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'trainers' => Trainer::with('user')->where('status', 'active')->get(),
            'plans' => MembershipPlan::where('is_active', true)->orderBy('price')->get(),
        ])->title($this->member ? 'Edit member' : 'New member');
    }
}
