<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->decimal('available_balance', 20, 2)->default(0);
            $table->decimal('held_balance', 20, 2)->default(0);
            $table->string('status')->default('active'); // active, locked
            $table->timestamps();
        });

        Schema::create('wallet_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->string('type'); // credit, debit
            $table->decimal('amount', 20, 2);
            $table->decimal('balance_before', 20, 2);
            $table->decimal('balance_after', 20, 2);
            $table->string('description');

            // Polymorphic relation to trace what caused this ledger entry (e.g. Deposit, Transaction)
            $table->nullableMorphs('reference');

            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 20, 2);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->string('payment_method')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('wallet_ledgers');
        Schema::dropIfExists('wallets');
    }
};
