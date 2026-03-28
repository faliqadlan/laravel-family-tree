<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gathering_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gathering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable();
            $table->enum('status', ['pledged', 'paid', 'cancelled'])->default('pledged');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['gathering_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gathering_contributions');
    }
};
