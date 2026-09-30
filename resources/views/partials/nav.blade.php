@php
    $link = fn ($route, $pattern = null) => request()->routeIs($pattern ?? $route) ? 'nav-link active' : 'nav-link';
@endphp

@if($user->isSuperAdmin())
    <a href="{{ route('admin.dashboard') }}" wire:navigate class="{{ $link('admin.dashboard') }}"><x-icon name="home" class="size-5"/> Platform overview</a>
    <a href="{{ route('admin.gyms') }}" wire:navigate class="{{ $link('admin.gyms') }}"><x-icon name="building" class="size-5"/> Gyms</a>

@elseif($user->isMember())
    <a href="{{ route('portal.dashboard') }}" wire:navigate class="{{ $link('portal.dashboard') }}"><x-icon name="home" class="size-5"/> My dashboard</a>
    <a href="{{ route('portal.membership') }}" wire:navigate class="{{ $link('portal.membership') }}"><x-icon name="card" class="size-5"/> Membership & bills</a>
    <a href="{{ route('portal.attendance') }}" wire:navigate class="{{ $link('portal.attendance') }}"><x-icon name="check" class="size-5"/> Attendance</a>
    @if($member?->isPt())
        <div class="nav-heading">Personal training ⭐</div>
        <a href="{{ route('portal.sessions') }}" wire:navigate class="{{ $link('portal.sessions') }}"><x-icon name="calendar" class="size-5"/> My PT sessions</a>
        <a href="{{ route('portal.progress') }}" wire:navigate class="{{ $link('portal.progress') }}"><x-icon name="chart" class="size-5"/> Body progress</a>
        <a href="{{ route('portal.workout') }}" wire:navigate class="{{ $link('portal.workout') }}"><x-icon name="dumbbell" class="size-5"/> Workout plan</a>
        <a href="{{ route('portal.nutrition') }}" wire:navigate class="{{ $link('portal.nutrition') }}"><x-icon name="leaf" class="size-5"/> Nutrition</a>
    @endif

@else
    <a href="{{ route('dashboard') }}" wire:navigate class="{{ $link('dashboard') }}"><x-icon name="home" class="size-5"/> Dashboard</a>

    @can('members.view')
        <div class="nav-heading">Members</div>
        <a href="{{ route('members.index') }}" wire:navigate class="{{ $link('members.index', 'members.*') }}"><x-icon name="users" class="size-5"/> Members</a>
        <a href="{{ route('memberships.index') }}" wire:navigate class="{{ $link('memberships.index') }}"><x-icon name="card" class="size-5"/> Memberships</a>
    @endcan
    @can('plans.manage')
        <a href="{{ route('plans.index') }}" wire:navigate class="{{ $link('plans.index') }}"><x-icon name="tag" class="size-5"/> Plans</a>
    @endcan
    @can('attendance.manage')
        <a href="{{ route('attendance.index') }}" wire:navigate class="{{ $link('attendance.index') }}"><x-icon name="check" class="size-5"/> Attendance</a>
    @endcan

    <div class="nav-heading">Personal training ⭐</div>
    <a href="{{ route('pt.clients') }}" wire:navigate class="{{ $link('pt.clients', 'pt.clients*') }}"><x-icon name="star" class="size-5"/> PT clients</a>
    @can('pt.schedule')
        <a href="{{ route('pt.schedule') }}" wire:navigate class="{{ $link('pt.schedule', 'pt.s*') }}"><x-icon name="calendar" class="size-5"/> PT schedule</a>
    @endcan
    @can('pt.packages')
        <a href="{{ route('pt.packages') }}" wire:navigate class="{{ $link('pt.packages') }}"><x-icon name="box" class="size-5"/> PT packages</a>
    @endcan
    @can('exercises.manage')
        <a href="{{ route('exercises.index') }}" wire:navigate class="{{ $link('exercises.index') }}"><x-icon name="dumbbell" class="size-5"/> Exercise library</a>
    @endcan

    @canany(['billing.view', 'finance.view', 'expenses.manage'])
        <div class="nav-heading">Money</div>
        @can('billing.view')
            <a href="{{ route('invoices.index') }}" wire:navigate class="{{ $link('invoices.index', 'invoices.*') }}"><x-icon name="document" class="size-5"/> Invoices</a>
            <a href="{{ route('payments.index') }}" wire:navigate class="{{ $link('payments.index') }}"><x-icon name="cash" class="size-5"/> Payments</a>
        @endcan
        @can('expenses.manage')
            <a href="{{ route('expenses.index') }}" wire:navigate class="{{ $link('expenses.index') }}"><x-icon name="receipt" class="size-5"/> Expenses</a>
        @endcan
        @can('finance.view')
            <a href="{{ route('finance.reports') }}" wire:navigate class="{{ $link('finance.reports') }}"><x-icon name="chart" class="size-5"/> Financial reports</a>
        @endcan
    @endcanany

    @canany(['trainers.manage', 'gym.settings', 'activity.view'])
        <div class="nav-heading">Team & settings</div>
        @can('trainers.manage')
            <a href="{{ route('trainers.index') }}" wire:navigate class="{{ $link('trainers.index', 'trainers.*') }}"><x-icon name="whistle" class="size-5"/> Trainers</a>
        @endcan
        @can('staff.manage')
            <a href="{{ route('settings.staff') }}" wire:navigate class="{{ $link('settings.staff') }}"><x-icon name="shield" class="size-5"/> Staff & roles</a>
        @endcan
        @can('branches.manage')
            <a href="{{ route('settings.branches') }}" wire:navigate class="{{ $link('settings.branches') }}"><x-icon name="building" class="size-5"/> Branches</a>
        @endcan
        @can('gym.settings')
            <a href="{{ route('settings.gym') }}" wire:navigate class="{{ $link('settings.gym') }}"><x-icon name="cog" class="size-5"/> Gym settings</a>
        @endcan
        @can('activity.view')
            <a href="{{ route('activity.index') }}" wire:navigate class="{{ $link('activity.index') }}"><x-icon name="clock" class="size-5"/> Activity log</a>
        @endcan
    @endcanany
@endif
