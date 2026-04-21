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
        Schema::create('episode_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->string('gift_key');
            $table->string('gift_label');
            $table->unsignedInteger('coins');
            $table->timestamps();

            $table->index(['episode_id', 'created_at']);
            $table->index(['recipient_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episode_gifts');
    }
};
