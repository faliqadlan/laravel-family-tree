<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('duplicate_checks', 'team_id')) {
            Schema::table('duplicate_checks', function (Blueprint $table) {
                $table->foreignId('team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
                $table->index(['team_id', 'status']);
            });

            DB::statement('UPDATE duplicate_checks dc JOIN users u ON u.id = dc.user_id SET dc.team_id = u.current_team_id WHERE dc.team_id IS NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('duplicate_checks', 'team_id')) {
            Schema::table('duplicate_checks', function (Blueprint $table) {
                $table->dropIndex(['team_id', 'status']);
                $table->dropConstrainedForeignId('team_id');
            });
        }
    }
};
