<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bmc_building_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('bmc_canvas_id')->constrained('bmc_canvases')->onDelete('cascade');
            $table->string('block_type');
            $table->string('label');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['bmc_canvas_id', 'block_type'], 'bmc_bb_canvas_type_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bmc_building_blocks');
    }
};
