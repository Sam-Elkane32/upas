<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            [
                'key' => 'default_password',
                'value' => 'UPAS@2025!',
                'type' => 'string',
                'description' => 'Default password for newly created users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'system_name',
                'value' => 'UPAS - University Planning Accomplishment System',
                'type' => 'string',
                'description' => 'System name',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'system_email',
                'value' => 'admin@psu.edu.ph',
                'type' => 'string',
                'description' => 'System email address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
