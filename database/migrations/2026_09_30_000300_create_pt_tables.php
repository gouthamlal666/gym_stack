<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->nullable()->constrained()->cascadeOnDelete(); // null = global library
            $table->string('name');
            $table->string('muscle_group')->nullable();
            $table->string('equipment')->nullable();
            $table->string('video_url')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('pt_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sessions_count');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('validity_days');
            $table->unsignedSmallInteger('session_minutes')->default(60);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pt_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pt_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('total_sessions');
            $table->date('start_date');
            $table->date('expiry_date');
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('status')->default('active'); // active, completed, expired, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pt_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pt_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('focus')->nullable(); // e.g. Chest + Cardio
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled, no_show
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->text('trainer_notes')->nullable();
            $table->text('trainer_feedback')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // trainer rating of session effort 1-5
            $table->string('cancel_reason')->nullable();
            $table->unsignedTinyInteger('reschedule_count')->default(0);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->index(['trainer_id', 'scheduled_at']);
            $table->index(['member_id', 'scheduled_at']);
        });

        Schema::create('pt_session_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pt_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('sets')->nullable();
            $table->string('reps')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('body_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('assessed_on');
            $table->boolean('is_initial')->default(false);
            foreach (['weight', 'height', 'bmi', 'body_fat', 'muscle_mass', 'chest', 'waist', 'hip', 'shoulder',
                'neck', 'biceps', 'forearm', 'thigh', 'calf'] as $metric) {
                $table->decimal($metric, 6, 2)->nullable();
            }
            $table->text('notes')->nullable();
            $table->date('next_assessment_on')->nullable();
            $table->timestamps();
            $table->index(['member_id', 'assessed_on']);
        });

        Schema::create('progress_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('body_assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('angle'); // front, back, left, right
            $table->string('path'); // private disk
            $table->date('taken_on');
            $table->timestamps();
        });

        Schema::create('fitness_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('assessed_on');
            foreach (['strength', 'flexibility', 'mobility', 'endurance', 'cardio', 'balance', 'posture',
                'functional_movement'] as $rating) {
                $table->unsignedTinyInteger($rating)->nullable(); // 1-10
            }
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fitness_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fitness_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('test_key'); // pushups, squats, plank, run_1km, vo2max, grip, sit_reach
            $table->decimal('value', 8, 2);
            $table->timestamps();
        });

        Schema::create('pt_workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('goal');
            $table->string('level'); // beginner, intermediate, advanced
            $table->unsignedTinyInteger('duration_weeks')->default(12);
            $table->date('start_date');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pt_workout_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pt_workout_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Monday ... 7 = Sunday
            $table->string('focus')->nullable();
            $table->boolean('is_rest')->default(false);
            $table->timestamps();
        });

        Schema::create('pt_workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pt_workout_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('sets')->nullable();
            $table->string('reps')->nullable();
            $table->string('weight')->nullable();
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->string('tempo')->nullable();
            $table->unsignedTinyInteger('rpe')->nullable();
            $table->text('instructions')->nullable();
            $table->string('video_url')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('nutrition_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('daily_calories');
            $table->unsignedSmallInteger('protein_g');
            $table->unsignedSmallInteger('carbs_g');
            $table->unsignedSmallInteger('fat_g');
            $table->decimal('water_liters', 4, 1)->default(3);
            $table->date('start_date');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('nutrition_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_plan_id')->constrained()->cascadeOnDelete();
            $table->string('meal_type'); // breakfast, morning_snack, lunch, pre_workout, post_workout, dinner
            $table->string('time')->nullable();
            $table->text('items');
            $table->unsignedSmallInteger('calories')->nullable();
            $table->unsignedSmallInteger('protein_g')->nullable();
            $table->unsignedSmallInteger('carbs_g')->nullable();
            $table->unsignedSmallInteger('fat_g')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('pt_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('goal_type'); // weight_loss, muscle_gain, recomposition, strength, endurance, fitness
            $table->string('title');
            $table->string('metric'); // body assessment column to track, e.g. weight, body_fat, waist
            $table->decimal('start_value', 8, 2);
            $table->decimal('target_value', 8, 2);
            $table->string('unit', 10);
            $table->date('start_date');
            $table->date('target_date')->nullable();
            $table->string('status')->default('active'); // active, achieved, abandoned
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['pt_goals', 'nutrition_meals', 'nutrition_plans', 'pt_workout_exercises', 'pt_workout_days',
            'pt_workout_plans', 'fitness_test_results', 'fitness_assessments', 'progress_photos', 'body_assessments',
            'pt_session_exercises', 'pt_sessions', 'pt_subscriptions', 'pt_packages', 'exercises'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
