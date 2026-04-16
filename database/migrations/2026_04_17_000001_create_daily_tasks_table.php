<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('action_type', [
                'watch_episode',
                'follow_series',
                'invite_friend',
                'custom',
            ]);
            $table->unsignedInteger('target_count')->default(1);
            $table->unsignedInteger('coins');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_tasks');
    }
};
