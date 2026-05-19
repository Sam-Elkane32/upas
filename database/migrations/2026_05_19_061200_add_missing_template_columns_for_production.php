<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns the app expects on templates (PostgreSQL / partial migrate paths).
     */
    public function up(): void
    {
        if (!Schema::hasTable('templates')) {
            return;
        }

        Schema::table('templates', function (Blueprint $table) {
            if (!Schema::hasColumn('templates', 'form_id')) {
                $table->unsignedBigInteger('form_id')->nullable()->after('id');
                if (Schema::hasTable('forms')) {
                    $table->foreign('form_id')->references('id')->on('forms')->nullOnDelete();
                }
            }
            if (!Schema::hasColumn('templates', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('created_by');
                if (Schema::hasTable('users')) {
                    $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        // non-destructive for production data
    }
};
