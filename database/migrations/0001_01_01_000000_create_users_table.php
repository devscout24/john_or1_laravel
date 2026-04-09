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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->default('user.png');
            $table->string('name', 100)->nullable();
            $table->string('username', 50)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('title', 255)->nullable();

            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('password');
            $table->string('password_reset_token')->nullable();
            $table->timestamp('password_reset_token_expires_at')->nullable();

            $table->string('otp', 10)->nullable();
            $table->timestamp('otp_expired_at')->nullable();
            $table->timestamp('otp_verified_at')->nullable();

            $table->string('email_change_token')->nullable();
            $table->timestamp('email_change_token_expires_at')->nullable();

            // Social login
            $table->enum('provider', ['email', 'google', 'apple', 'guest'])->default('email');
            $table->string('provider_id')->nullable();

            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->string('reason', 255)->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->string('fcm_token', 255)->nullable();

            $table->string('pending_email')->nullable();

            // App logic
            $table->integer('coins')->default(0);
            $table->string('language')->default('en');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
