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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('content_id')->constrained()->cascadeOnDelete();

            $table->string('title')->nullable();

            // for series (episode number), null for movies
            $table->integer('episode_number')->nullable();

            // video handling
            $table->enum('video_type', ['uploaded', 'external'])->default('external');

            $table->text('video_url')->nullable();     // CDN / external link
            $table->string('storage_path')->nullable(); // local/server file

            $table->integer('duration')->nullable(); // seconds

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
