<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bmc_canvas_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('bmc_canvas_id')->constrained('bmc_canvases')->onDelete('cascade');
            $table->integer('version');
            $table->json('snapshot_data');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['bmc_canvas_id', 'version'], 'bmc_snapshots_canvas_version_uq');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bmc_canvas_snapshots');
    }
};
