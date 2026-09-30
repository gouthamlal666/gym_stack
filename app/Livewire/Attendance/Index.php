<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Attendance;
use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app'), Title('Attendance')]
class Index extends Component
{
    use InteractsWithUi;

    public string $search = '';
    #[Url] public string $date = '';

    public function mount(): void
    {
        $this->requirePermission('attendance.manage');
        $this->date = $this->date ?: today()->toDateString();
    }

    public function checkIn(int $memberId): void
    {
        $member = Member::with('currentMembership')->findOrFail($memberId);
        if (Attendance::where('member_id', $memberId)->whereDate('check_in_at', today())->whereNull('check_out_at')->exists()) {
            $this->toast("{$member->name} is already checked in.", 'error');

            return;
        }
        Attendance::create(['member_id' => $memberId, 'branch_id' => auth()->user()->branch_id ?? $member->branch_id, 'check_in_at' => now()]);
        $this->search = '';
        $this->toast($member->currentMembership ? "{$member->name} checked in." : "{$member->name} checked in — ⚠ no active membership!", $member->currentMembership ? 'success' : 'error');
    }

    public function checkOut(int $attendanceId): void
    {
        Attendance::findOrFail($attendanceId)->update(['check_out_at' => now()]);
        $this->toast('Checked out.');
    }

    public function render()
    {
        return view('livewire.attendance.index', [
            'results' => strlen($this->search) >= 2 ? Member::with('currentMembership.plan')
                ->where(fn ($q) => $q->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")->orWhere('member_code', 'like', "%{$this->search}%"))
                ->limit(6)->get() : collect(),
            'records' => Attendance::with('member')->whereDate('check_in_at', $this->date)->latest('check_in_at')->get(),
            'hourly' => Attendance::whereDate('check_in_at', $this->date)->get()->groupBy(fn ($a) => (int) $a->check_in_at->format('G'))->map->count(),
        ]);
    }
}
