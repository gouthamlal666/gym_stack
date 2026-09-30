<?php

namespace App\Providers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {

        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'gym' => \App\Models\Gym::class,
            'branch' => \App\Models\Branch::class,
            'member' => \App\Models\Member::class,
            'trainer' => \App\Models\Trainer::class,
            'membership_plan' => \App\Models\MembershipPlan::class,
            'membership' => \App\Models\Membership::class,
            'invoice' => \App\Models\Invoice::class,
            'payment' => \App\Models\Payment::class,
            'expense' => \App\Models\Expense::class,
            'pt_package' => \App\Models\PtPackage::class,
            'pt_subscription' => \App\Models\PtSubscription::class,
            'pt_session' => \App\Models\PtSession::class,
            'body_assessment' => \App\Models\BodyAssessment::class,
            'progress_photo' => \App\Models\ProgressPhoto::class,
            'fitness_assessment' => \App\Models\FitnessAssessment::class,
            'pt_workout_plan' => \App\Models\PtWorkoutPlan::class,
            'nutrition_plan' => \App\Models\NutritionPlan::class,
            'pt_goal' => \App\Models\PtGoal::class,
        ]);

        Notification::extend('whatsapp', fn () => new \App\Notifications\Channels\WhatsAppChannel);

        // Flat permission gates from config/gym.php, e.g. @can('members.manage').
        foreach (array_keys(config('gym.permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // ── PT privacy ─────────────────────────────────────────────────────
        // Only PT customers have PT data. Staff see PT clients per role; a trainer only sees clients they coach;
        // a member only sees their own.
        Gate::define('view-pt-member', function (User $user, Member $member) {
            if (! $member->isPt() || $user->gym_id !== $member->gym_id) {
                return false;
            }

            return match (true) {
                $user->isMember() => $user->member?->id === $member->id,
                $user->isTrainer() => $user->trainer && $member->coachId() === $user->trainer->id,
                default => $user->hasPermission('pt.all_clients'),
            };
        });

        Gate::define('coach-pt-member', function (User $user, Member $member) {
            if (! $member->isPt() || $user->gym_id !== $member->gym_id || ! $user->hasPermission('pt.coach')) {
                return false;
            }

            return $user->isTrainer() ? $user->trainer && $member->coachId() === $user->trainer->id : true;
        });

        // Progress photos: the member, their assigned trainer, and the gym admin only.
        Gate::define('view-progress-photos', function (User $user, Member $member) {
            if (! $member->isPt() || $user->gym_id !== $member->gym_id) {
                return false;
            }

            return match (true) {
                $user->isMember() => $user->member?->id === $member->id,
                $user->isTrainer() => $user->trainer && $member->coachId() === $user->trainer->id,
                default => $user->hasRole('gym_admin'),
            };
        });

        Gate::define('upload-progress-photos', fn (User $user, Member $member) => Gate::forUser($user)->allows('view-progress-photos', $member) && ! $user->isMember());
    }
}
