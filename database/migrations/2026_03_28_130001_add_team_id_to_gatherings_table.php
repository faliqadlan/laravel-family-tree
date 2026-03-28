<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('gatherings', 'team_id')) {
            Schema::table('gatherings', function (Blueprint $table) {
                $table->foreignId('team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
                $table->index(['team_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('gatherings', 'team_id')) {
            Schema::table('gatherings', function (Blueprint $table) {
                $table->dropIndex(['team_id', 'status']);
                $table->dropConstrainedForeignId('team_id');
            });
        }
    }
};
