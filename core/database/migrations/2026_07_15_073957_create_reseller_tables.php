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
        Schema::create('reseller_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('level')->default(1);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('group_id')->constrained('reseller_groups')->restrictOnDelete();
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resellers');
        Schema::dropIfExists('reseller_groups');
    }
};
