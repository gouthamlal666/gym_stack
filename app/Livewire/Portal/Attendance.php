<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\Title;

#[Title('Attendance')]
class Attendance extends PortalPage
{
    public function render()
    {
        $member = $this->member();
        $visits = $member->attendances()->where('check_in_at', '>=', now()->subDays(83)->startOfWeek())->get();
        $byDay = $visits->groupBy(fn ($a) => $a->check_in_at->toDateString())->map->count();

        return view('livewire.portal.attendance', [
            'recent' => $member->attendances()->limit(30)->get(),
            'byDay' => $byDay,
            'start' => now()->subDays(83)->startOfWeek(),
            'streak' => $this->streak($byDay->keys()->all()),
            'total' => $member->attendances()->count(),
        ]);
    }

    private function streak(array $days): int
    {
        $set = array_flip($days);
        $d = today();
        if (! isset($set[$d->toDateString()])) {
            $d = $d->subDay();
        }
        $n = 0;
        while (isset($set[$d->toDateString()])) {
            $n++;
            $d = $d->subDay();
        }

        return $n;
    }
}
