<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pivot table: multiple Planning Coordinators can be assigned to one template.
     */
    public function up(): void
    {
        if (Schema::hasTable('template_assigned_users')) {
            return;
        }

        Schema::create('template_assigned_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['template_id', 'user_id']);
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Migrate existing assigned_user_id into pivot (one row per template that has an assignment)
        try {
            $rows = DB::table('templates')
                ->whereNotNull('assigned_user_id')
                ->select('id', 'assigned_user_id')
                ->get();
            foreach ($rows as $row) {
                DB::table('template_assigned_users')->insertOrIgnore([
                    'template_id' => $row->id,
                    'user_id' => $row->assigned_user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore if templates table or column does not exist or DB driver differs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_assigned_users');
    }
};
