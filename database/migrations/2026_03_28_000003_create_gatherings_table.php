<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gatherings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tree_id')->nullable()->constrained('trees')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['reunion', 'memorial', 'wedding', 'birthday', 'holiday', 'other'])->default('reunion');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('location_url')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_virtual')->default(false);
            $table->string('meeting_url')->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->enum('privacy', ['public', 'connections', 'private'])->default('connections');
            $table->decimal('funding_goal', 10, 2)->nullable();
            $table->string('funding_currency', 3)->default('USD');
            $table->text('funding_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organizer_id', 'status']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gatherings');
    }
};
