<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gathering_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gathering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('gathering_comments')->nullOnDelete();
            $table->text('content');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            $table->index(['gathering_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gathering_comments');
    }
};
