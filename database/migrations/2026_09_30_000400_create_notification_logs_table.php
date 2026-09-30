<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete(); // null = automatic
            $table->string('type');    // membership_expiring, membership_expired
            $table->string('channel'); // whatsapp, mail
            $table->string('recipient')->nullable();
            $table->string('status');  // sent, logged (dev driver), failed, skipped
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['membership_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
