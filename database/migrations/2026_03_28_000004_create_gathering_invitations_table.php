<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gathering_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gathering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('email')->nullable();
            $table->enum('status', ['pending', 'attending', 'maybe', 'declined'])->default('pending');
            $table->unsignedSmallInteger('guests_count')->default(0);
            $table->text('notes')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['gathering_id', 'user_id']);
            $table->index(['gathering_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gathering_invitations');
    }
};
