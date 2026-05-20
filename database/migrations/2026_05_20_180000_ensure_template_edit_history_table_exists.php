<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure template_edit_history exists (Supabase / partial migrate paths).
     * Draft autosave on Field Structure calls logTemplateEdit after each save.
     */
    public function up(): void
    {
        if (Schema::hasTable('template_edit_history')) {
            return;
        }

        Schema::create('template_edit_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('user_id');
            $table->string('what_edited', 500);
            $table->timestamps();

            $table->index(['template_id', 'created_at']);

            if (Schema::hasTable('templates')) {
                $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
            }
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive for production data.
    }
};
