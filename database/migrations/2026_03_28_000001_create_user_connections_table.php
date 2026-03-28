<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'blocked'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamps();
            $table->unique(['requester_id', 'receiver_id']);
            $table->index(['receiver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_connections');
    }
};
