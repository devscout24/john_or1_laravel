<?php

use App\Models\Policy;
use Carbon\Carbon;
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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['beach', 'disclaimers']);
            $table->longText('content');
            $table->timestamps();
        });


        $policy_arr = [
            'beach' => 'Default beach policy content.',
            'disclaimers' => 'Default disclaimers content.',
        ];


        foreach ($policy_arr as $type => $content) {
            Policy::insert([
                'type'       => $type,
                'content'    => $content,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
