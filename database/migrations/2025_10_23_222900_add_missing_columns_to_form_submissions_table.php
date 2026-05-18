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
        Schema::table('form_submissions', function (Blueprint $table) {
            // Add missing columns that DashboardController expects
            $table->string('campus_code')->nullable()->after('campus');
            $table->decimal('rate_of_accomplishment', 5, 2)->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('reviewer_id')->nullable()->after('reviewed_at');
            
            // Add foreign key constraint for reviewer
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['reviewer_id']);
            
            // Drop columns
            $table->dropColumn([
                'campus_code',
                'rate_of_accomplishment', 
                'reviewed_at',
                'reviewer_id'
            ]);
        });
    }
};
