<?php

namespace App\Livewire\Members;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\PtPackage;
use App\Models\Trainer;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PtService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use InteractsWithUi, WithFileUploads;

    public Member $member;

    #[Url] public string $tab = 'overview';
    public ?string $modal = null;
    public ?int $membershipId = null;

    // Membership form
    public $plan_id = '';
    public string $start_date = '';
    public float $discount = 0;
    public bool $admission = true;
    public int $days = 7;
    public string $reason = '';

    // PT enrollment
    public $pt_package_id = '';
    public $pt_trainer_id = '';

    // Documents
    public string $doc_title = '';
    public $doc_file;

    public function mount(Member $member): void
    {
        $this->requirePermission('members.view');
        $this->member = $member;
        $this->start_date = today()->toDateString();
    }

    public function openModal(string $name, ?int $membershipId = null): void
    {
        $this->resetErrorBag();
        $this->reset('plan_id', 'discount', 'reason', 'pt_package_id');
        $this->days = 7;
        $this->start_date = today()->toDateString();
        $this->membershipId = $membershipId;
        $this->pt_trainer_id = $this->member->trainer_id ?? '';
        if ($membershipId && $name === 'renew') {
            $this->plan_id = Membership::find($membershipId)?->membership_plan_id ?? '';
        }
        $this->modal = $name;
    }

    private function membership(): Membership
    {
        return $this->member->memberships()->findOrFail($this->membershipId);
    }

    private function run(callable $action, string $success): void
    {
        try {
            $result = $action();
            $this->modal = null;
            $this->member->refresh();
            if ($result instanceof \App\Models\Invoice) {
                session()->flash('toast', "$success Invoice {$result->number} raised.");
                $this->redirectRoute('invoices.show', $result, navigate: true);

                return;
            }
            $this->toast($success);
        } catch (InvalidArgumentException $e) {
            $this->addError('modal', $e->getMessage());
        }
    }

    public function assignPlan(MembershipService $service): void
    {
        $this->requirePermission('memberships.manage');
        $this->validate(['plan_id' => 'required|exists:membership_plans,id', 'start_date' => 'required|date', 'discount' => 'numeric|min:0']);
        $this->run(fn () => $service->subscribe($this->member, MembershipPlan::findOrFail($this->plan_id), $this->start_date, $this->discount, $this->admission), 'Membership assigned.');
    }

    public function renew(MembershipService $service): void
    {
        $this->requirePermission('memberships.manage');
        $this->validate(['plan_id' => 'required|exists:membership_plans,id', 'discount' => 'numeric|min:0']);
        $this->run(fn () => $service->renew($this->membership(), MembershipPlan::findOrFail($this->plan_id), $this->discount), 'Membership renewed.');
    }

    public function changePlan(MembershipService $service): void
    {
        $this->requirePermission('memberships.manage');
        $this->validate(['plan_id' => 'required|exists:membership_plans,id', 'discount' => 'numeric|min:0']);
        $this->run(fn () => $service->changePlan($this->membership(), MembershipPlan::findOrFail($this->plan_id), $this->discount), 'Plan changed.');
    }

    public function freeze(MembershipService $service): void
    {
        $this->requirePermission('memberships.manage');
        $this->validate(['start_date' => 'required|date', 'days' => 'required|integer|min:1']);
        $this->run(fn () => $service->freeze($this->membership(), $this->start_date, $this->days), 'Membership frozen.');
    }

    public function unfreeze(int $id): void
    {
        $this->requirePermission('memberships.manage');
        $this->membershipId = $id;
        $this->run(fn () => app(MembershipService::class)->unfreeze($this->membership()), 'Membership unfrozen.');
    }

    public function extend(MembershipService $service): void
    {
        $this->requirePermission('memberships.manage');
        $this->validate(['days' => 'required|integer|min:1|max:365', 'reason' => 'required|string|max:200']);
        $this->run(fn () => $service->extend($this->membership(), $this->days, $this->reason), 'Membership extended.');
    }

    public function cancel(MembershipService $service): void
    {
        $this->requirePermission('memberships.manage');
        $this->validate(['reason' => 'required|string|max:200']);
        $this->run(fn () => $service->cancel($this->membership(), $this->reason), 'Membership cancelled.');
    }

    public function enrollPt(PtService $service): void
    {
        $this->requirePermission('pt.enroll');
        $this->validate([
            'pt_package_id' => 'required|exists:pt_packages,id',
            'pt_trainer_id' => 'required|exists:trainers,id',
            'start_date' => 'required|date',
            'discount' => 'numeric|min:0',
        ]);
        $this->run(fn () => $service->enroll($this->member, PtPackage::findOrFail($this->pt_package_id),
            Trainer::findOrFail($this->pt_trainer_id), $this->start_date, $this->discount), 'Enrolled in personal training.');
    }

    public function checkIn(): void
    {
        $this->requirePermission('attendance.manage');
        if (Attendance::where('member_id', $this->member->id)->whereDate('check_in_at', today())->whereNull('check_out_at')->exists()) {
            $this->toast('Already checked in.', 'error');

            return;
        }
        Attendance::create(['member_id' => $this->member->id, 'branch_id' => $this->member->branch_id, 'check_in_at' => now()]);
        $this->toast("{$this->member->first_name} checked in.");
    }

    public function uploadDocument(): void
    {
        $this->requirePermission('members.manage');
        $this->validate(['doc_title' => 'required|string|max:120', 'doc_file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx']);
        $this->member->documents()->create([
            'title' => $this->doc_title,
            'file_path' => $this->doc_file->store("documents/{$this->member->gym_id}", 'local'),
            'mime_type' => $this->doc_file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);
        $this->reset('doc_title', 'doc_file');
        $this->toast('Document uploaded.');
    }

    public function deleteDocument(int $id): void
    {
        $this->requirePermission('members.manage');
        $doc = MemberDocument::where('member_id', $this->member->id)->findOrFail($id);
        Storage::disk('local')->delete($doc->file_path);
        $doc->delete();
        $this->toast('Document removed.');
    }

    public function createLogin(): void
    {
        $this->requirePermission('members.manage');
        if (! $this->member->email) {
            $this->toast('Add an email address to the member first.', 'error');

            return;
        }
        if (User::where('email', $this->member->email)->exists()) {
            $this->toast('That email is already used by another account.', 'error');

            return;
        }
        $user = User::create([
            'gym_id' => $this->member->gym_id, 'branch_id' => $this->member->branch_id, 'name' => $this->member->name,
            'email' => $this->member->email, 'phone' => $this->member->phone, 'role' => 'member', 'password' => Str::random(32),
        ]);
        $this->member->update(['user_id' => $user->id]);
        Password::sendResetLink(['email' => $user->email]);
        $this->toast('Portal login created — a set-password email was sent.');
    }

    public function delete()
    {
        $this->requirePermission('members.delete');
        $this->member->delete();
        session()->flash('toast', 'Member archived.');

        return $this->redirectRoute('members.index', navigate: true);
    }

    public function render()
    {
        $this->member->load(['branch', 'trainer.user', 'user', 'currentMembership.plan', 'activePtSubscription.package', 'activePtSubscription.trainer.user']);

        return view('livewire.members.show', [
            'memberships' => $this->member->memberships()->with('plan', 'invoice')->get(),
            'invoices' => $this->tab === 'billing' ? $this->member->invoices()->get() : collect(),
            'payments' => $this->tab === 'billing' ? $this->member->payments()->get() : collect(),
            'attendance' => $this->tab === 'attendance' ? $this->member->attendances()->limit(60)->get() : collect(),
            'documents' => $this->tab === 'documents' ? $this->member->documents()->with('uploader')->latest()->get() : collect(),
            'plans' => MembershipPlan::where('is_active', true)->orderBy('price')->get(),
            'packages' => PtPackage::where('is_active', true)->orderBy('sessions_count')->get(),
            'trainers' => Trainer::with('user')->where('status', 'active')->where('is_pt_trainer', true)->get(),
            'visitsThisMonth' => $this->member->attendances()->where('check_in_at', '>=', now()->startOfMonth())->count(),
            'balance' => $this->member->outstandingBalance(),
            'reminders' => $this->member->reminderLogs()->with('sender')->limit(6)->get(),
        ])->title($this->member->name);
    }
}
