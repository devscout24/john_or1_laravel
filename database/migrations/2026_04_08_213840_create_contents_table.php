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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            // movie or series
            $table->enum('type', ['movie', 'series']);

            $table->string('thumbnail')->nullable();
            $table->string('banner')->nullable();

            // access control (VERY IMPORTANT)
            $table->enum('access_type', ['free', 'coins', 'subscription', 'ads'])->default('free');

            $table->integer('coins_required')->default(0);

            // status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
