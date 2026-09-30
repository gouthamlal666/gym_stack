<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

trait InteractsWithUi
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }

    protected function requirePermission(string $permission): void
    {
        abort_unless(Auth::user()->hasPermission($permission), 403);
    }

    protected function gym()
    {
        return Auth::user()->gym;
    }
}
