<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('specializations')->nullable();
            $table->json('certifications')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0); // % of PT revenue
            $table->boolean('is_pt_trainer')->default(true);
            $table->json('availability')->nullable(); // {"1":{"start":"06:00","end":"14:00"},...}
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_code');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('training_type')->default('regular'); // regular, pt
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->date('joined_on');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['gym_id', 'member_code']);
            $table->index(['gym_id', 'training_type']);
        });

        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('billing_cycle'); // monthly, quarterly, half_yearly, yearly, custom
            $table->unsignedSmallInteger('duration_days');
            $table->decimal('price', 10, 2);
            $table->decimal('admission_fee', 10, 2)->default(0);
            $table->boolean('is_trial')->default(false);
            $table->unsignedSmallInteger('max_freeze_days')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('previous_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->string('change_type')->default('new'); // new, renewal, upgrade, downgrade
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('status')->default('active'); // upcoming, active, frozen, expired, cancelled
            $table->date('frozen_from')->nullable();
            $table->date('frozen_until')->nullable();
            $table->unsignedSmallInteger('frozen_days_used')->default(0);
            $table->unsignedSmallInteger('extended_days')->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['gym_id', 'status', 'end_date']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            $table->string('method')->default('manual'); // manual, qr, biometric
            $table->timestamps();
            $table->index(['gym_id', 'check_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('member_documents');
        Schema::dropIfExists('members');
        Schema::dropIfExists('trainers');
    }
};
