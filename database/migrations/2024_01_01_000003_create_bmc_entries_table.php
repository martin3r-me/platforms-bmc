<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bmc_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('bmc_building_block_id')->constrained('bmc_building_blocks')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();
            $table->integer('position')->default(0);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['bmc_building_block_id', 'position'], 'bmc_entries_block_pos_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bmc_entries');
    }
};
