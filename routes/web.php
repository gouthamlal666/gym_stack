<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\PrintController;
use App\Livewire;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect(auth()->user()->homeRoute()) : redirect()->route('login'));

// ── Authentication ────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', Livewire\Auth\Login::class)->name('login');
    Route::get('/login/otp', Livewire\Auth\OtpLogin::class)->name('login.otp');
    Route::get('/two-factor', Livewire\Auth\TwoFactorChallenge::class)->name('two-factor');
    Route::get('/register', Livewire\Auth\Register::class)->name('register');
    Route::get('/forgot-password', Livewire\Auth\ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', Livewire\Auth\ResetPassword::class)->name('password.reset');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::get('/profile', Livewire\Account\Profile::class)->name('profile');

    // Private files (authorised per request)
    Route::get('/files/progress-photos/{photo}', [FileController::class, 'progressPhoto'])->name('pt.photos.show');
    Route::get('/files/documents/{document}', [FileController::class, 'memberDocument'])->name('documents.download');
    Route::get('/print/invoices/{invoice}', [PrintController::class, 'invoice'])->name('invoices.print');
    Route::get('/print/receipts/{payment}', [PrintController::class, 'receipt'])->name('payments.receipt');

    // ── Gym staff back-office ─────────────────────────────────────────────────
    Route::middleware('role:staff')->group(function () {
        Route::get('/dashboard', Livewire\Dashboard::class)->name('dashboard');

        Route::get('/members', Livewire\Members\Index::class)->name('members.index');
        Route::get('/members/create', Livewire\Members\Form::class)->name('members.create');
        Route::get('/members/{member}', Livewire\Members\Show::class)->name('members.show');
        Route::get('/members/{member}/edit', Livewire\Members\Form::class)->name('members.edit');

        Route::get('/plans', Livewire\Memberships\Plans::class)->name('plans.index');
        Route::get('/memberships', Livewire\Memberships\Index::class)->name('memberships.index');
        Route::get('/attendance', Livewire\Attendance\Index::class)->name('attendance.index');

        Route::get('/invoices', Livewire\Billing\Invoices::class)->name('invoices.index');
        Route::get('/invoices/{invoice}', Livewire\Billing\InvoiceShow::class)->name('invoices.show');
        Route::get('/payments', Livewire\Billing\Payments::class)->name('payments.index');
        Route::get('/expenses', Livewire\Finance\Expenses::class)->name('expenses.index');
        Route::get('/finance', Livewire\Finance\Reports::class)->name('finance.reports');

        Route::get('/trainers', Livewire\Trainers\Index::class)->name('trainers.index');
        Route::get('/trainers/{trainer}', Livewire\Trainers\Show::class)->name('trainers.show');
        Route::get('/exercises', Livewire\Exercises\Index::class)->name('exercises.index');

        Route::prefix('pt')->name('pt.')->group(function () {
            Route::get('/packages', Livewire\Pt\Packages::class)->name('packages');
            Route::get('/clients', Livewire\Pt\Clients::class)->name('clients');
            Route::get('/clients/{member}', Livewire\Pt\ClientShow::class)->name('clients.show');
            Route::get('/schedule', Livewire\Pt\Schedule::class)->name('schedule');
            Route::get('/sessions/{session}', Livewire\Pt\SessionShow::class)->name('sessions.show');
        });

        Route::get('/settings/gym', Livewire\Settings\GymSettings::class)->name('settings.gym');
        Route::get('/settings/branches', Livewire\Settings\Branches::class)->name('settings.branches');
        Route::get('/settings/staff', Livewire\Settings\Staff::class)->name('settings.staff');
        Route::get('/activity', Livewire\Settings\ActivityLogs::class)->name('activity.index');
    });

    // ── Member portal ─────────────────────────────────────────────────────────
    Route::middleware('role:member')->prefix('my')->name('portal.')->group(function () {
        Route::get('/', Livewire\Portal\Dashboard::class)->name('dashboard');
        Route::get('/membership', Livewire\Portal\Membership::class)->name('membership');
        Route::get('/attendance', Livewire\Portal\Attendance::class)->name('attendance');

        Route::middleware('pt.customer')->group(function () {
            Route::get('/progress', Livewire\Portal\Progress::class)->name('progress');
            Route::get('/workout', Livewire\Portal\Workout::class)->name('workout');
            Route::get('/nutrition', Livewire\Portal\Nutrition::class)->name('nutrition');
            Route::get('/sessions', Livewire\Portal\Sessions::class)->name('sessions');
        });
    });

    // ── SaaS super admin ──────────────────────────────────────────────────────
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Livewire\Admin\Dashboard::class)->name('dashboard');
        Route::get('/gyms', Livewire\Admin\Gyms::class)->name('gyms');
    });
});
