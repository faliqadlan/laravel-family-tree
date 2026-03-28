<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('profile_visibility', ['public', 'connections', 'private'])->default('connections');
            $table->json('field_visibility')->nullable()->comment('field => public|masked|hidden');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_privacy_settings');
    }
};
