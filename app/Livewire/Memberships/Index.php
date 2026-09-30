<?php

namespace App\Livewire\Memberships;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\Membership;
use App\Notifications\MembershipReminder;
use App\Services\ReminderService;
use App\Services\WhatsApp\WhatsAppClient;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app'), Title('Memberships')]
class Index extends Component
{
    use InteractsWithUi, WithPagination;

    #[Url] public string $filter = 'active';
    #[Url] public string $search = '';

    // Reminders
    public array $selected = [];
    public bool $showReminder = false;
    public array $targetIds = [];
    public array $channels = ['whatsapp', 'mail'];

    public function mount(): void { $this->requirePermission('members.view'); }

    public function updating($name): void
    {
        if (in_array($name, ['filter', 'search'])) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    private function query()
    {
        $query = Membership::with('member', 'plan')
            ->when($this->search, fn ($q) => $q->whereHas('member', fn ($m) => $m->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('member_code', 'like', "%{$this->search}%")));

        match ($this->filter) {
            'expiring' => $query->where('status', 'active')->whereDate('end_date', '<=', today()->addDays(7))->orderBy('end_date'),
            'expired' => $query->lapsed()->orderByDesc('end_date'),
            'frozen' => $query->where('status', 'frozen')->orderBy('frozen_until'),
            'upcoming' => $query->where('status', 'upcoming')->orderBy('start_date'),
            'cancelled' => $query->where('status', 'cancelled')->orderByDesc('cancelled_at'),
            default => $query->where('status', 'active')->orderBy('end_date'),
        };

        return $query;
    }

    public function canRemind(): bool
    {
        return in_array($this->filter, ['expiring', 'expired']) && auth()->user()->hasPermission('reminders.send');
    }

    public function selectAll(): void
    {
        $this->selected = $this->query()->pluck('memberships.id')->map(fn ($id) => (string) $id)->all();
    }

    public function openReminder(?int $id = null): void
    {
        $this->requirePermission('reminders.send');
        $this->targetIds = $id ? [$id] : array_map('intval', $this->selected);
        if (empty($this->targetIds)) {
            $this->toast('Select at least one member.', 'error');

            return;
        }
        $this->channels = ReminderService::settings($this->gym())['channels'] ?: ['whatsapp', 'mail'];
        $this->showReminder = true;
    }

    public function sendReminders(ReminderService $reminders): void
    {
        $this->requirePermission('reminders.send');
        $this->validate(['channels' => 'required|array|min:1', 'channels.*' => 'in:whatsapp,mail'], ['channels.required' => 'Choose at least one channel.']);

        $tally = ['whatsapp' => 0, 'mail' => 0, 'skipped' => 0, 'failed' => 0];
        $errors = [];
        foreach (Membership::with('member.gym', 'plan')->whereIn('id', $this->targetIds)->get() as $membership) {
            foreach ($reminders->send($membership, $this->channels) as $channel => $log) {
                match (true) {
                    $log->succeeded() => $tally[$channel]++,
                    $log->status === 'failed' => $tally['failed']++,
                    default => $tally['skipped']++,
                };
                if ($log->status === 'failed') {
                    $errors[] = $log->error;
                }
            }
        }

        $this->showReminder = false;
        $this->selected = [];
        $parts = array_filter([
            $tally['whatsapp'] ? "{$tally['whatsapp']} WhatsApp" : null,
            $tally['mail'] ? "{$tally['mail']} email" : null,
            $tally['skipped'] ? "{$tally['skipped']} skipped (missing contact)" : null,
            $tally['failed'] ? "{$tally['failed']} failed: ".collect($errors)->unique()->first() : null,
        ]);
        $this->toast('Reminders: '.implode(' · ', $parts ?: ['nothing sent']), $tally['failed'] ? 'error' : 'success');
    }

    public function render()
    {
        $memberships = $this->query()
            ->withMax(['reminderLogs as last_reminded_at' => fn ($q) => $q->whereIn('status', ['sent', 'logged'])], 'created_at')
            ->paginate(20);

        $counts = Membership::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $counts['expiring'] = Membership::where('status', 'active')->whereDate('end_date', '<=', today()->addDays(7))->count();
        $counts['expired'] = Membership::lapsed()->count();

        $client = WhatsAppClient::forGym($this->gym());
        $targets = $this->targetIds ? Membership::with('member', 'plan')->whereIn('id', $this->targetIds)->get() : collect();

        return view('livewire.memberships.index', [
            'memberships' => $memberships,
            'counts' => $counts,
            'remindable' => $this->canRemind(),
            'wa' => $client,
            'waDriver' => $client->driver(),
            'targets' => $targets,
            'preview' => $targets->first() ? (new MembershipReminder($targets->first(), MembershipReminder::typeFor($targets->first())))->text() : null,
        ]);
    }
}
