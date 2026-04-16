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
        Schema::create('user_referral_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('referral_code', 20);
            $table->date('used_on');
            $table->timestamps();

            $table->unique(['user_id', 'used_on']);
            $table->index(['referrer_user_id', 'used_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_referral_usages');
    }
};
