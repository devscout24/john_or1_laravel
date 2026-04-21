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
        Schema::create('user_watch_drama_reward_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('reward_date');
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->json('claimed_milestones')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reward_date'], 'user_watch_drama_reward_unique_date');
            $table->index(['user_id', 'reward_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_watch_drama_reward_states');
    }
};
