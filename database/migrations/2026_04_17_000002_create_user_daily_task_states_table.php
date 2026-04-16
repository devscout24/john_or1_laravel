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
        Schema::create('user_daily_task_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_task_id')->constrained('daily_tasks')->cascadeOnDelete();
            $table->date('task_date');
            $table->unsignedInteger('progress')->default(0);
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'daily_task_id', 'task_date'], 'user_daily_task_unique_date');
            $table->index(['user_id', 'task_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_daily_task_states');
    }
};
