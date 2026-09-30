<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('billable'); // Membership, PtSubscription
            $table->string('number');
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_refunded', 10, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, partial, paid, refunded, void
            $table->timestamp('last_reminder_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['gym_id', 'number']);
            $table->index(['gym_id', 'status']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('payment'); // payment, refund
            $table->string('receipt_number');
            $table->decimal('amount', 10, 2);
            $table->string('method')->default('cash'); // cash, card, upi, bank_transfer, online
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['gym_id', 'paid_at']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category'); // rent, salary, utilities, equipment, maintenance, marketing, other
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['gym_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
