<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bmc_canvases', function (Blueprint $table) {
            $table->string('canvas_type', 20)->default('bmc')->after('status');
            $table->index(['team_id', 'canvas_type'], 'bmc_canvases_team_canvas_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bmc_canvases', function (Blueprint $table) {
            $table->dropIndex('bmc_canvases_team_canvas_type_idx');
            $table->dropColumn('canvas_type');
        });
    }
};
