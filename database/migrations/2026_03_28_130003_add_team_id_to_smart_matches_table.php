<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('smart_matches', 'team_id')) {
            Schema::table('smart_matches', function (Blueprint $table) {
                $table->foreignId('team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
                $table->index(['team_id', 'status']);
            });

            DB::statement('UPDATE smart_matches sm JOIN users u ON u.id = sm.user_id SET sm.team_id = u.current_team_id WHERE sm.team_id IS NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('smart_matches', 'team_id')) {
            Schema::table('smart_matches', function (Blueprint $table) {
                $table->dropIndex(['team_id', 'status']);
                $table->dropConstrainedForeignId('team_id');
            });
        }
    }
};
