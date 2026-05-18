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
        // SQLite doesn't support ALTER COLUMN directly, so we need to recreate the table
        if (DB::getDriverName() === 'sqlite') {
            // Step 1: Disable foreign key checks
            DB::statement('PRAGMA foreign_keys=OFF');
            
            // Step 2: Create a new table with accomp_term as nullable
            Schema::create('approvals_temp', function (Blueprint $table) {
                $table->id();
                $table->string('approval_id')->unique();
                $table->unsignedBigInteger('submission_id');
                $table->string('accomp_term')->nullable(); // Make nullable
                $table->string('sdp_ref')->nullable();
                $table->decimal('target_q1', 10, 2)->default(0);
                $table->decimal('target_q2', 10, 2)->default(0);
                $table->decimal('target_q3', 10, 2)->default(0);
                $table->decimal('target_q4', 10, 2)->default(0);
                $table->decimal('target_total', 10, 2)->default(0);
                $table->decimal('accomp_q1', 10, 2)->default(0);
                $table->decimal('accomp_q2', 10, 2)->default(0);
                $table->decimal('accomp_q3', 10, 2)->default(0);
                $table->decimal('accomp_q4', 10, 2)->default(0);
                $table->decimal('accomp_total', 10, 2)->default(0);
                $table->decimal('variance', 10, 2)->default(0);
                $table->decimal('rate', 5, 2)->default(0);
                $table->enum('rating', ['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Needs Improvement'])->default('Needs Improvement');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('verified_by');
                $table->unsignedBigInteger('validated_by');
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade');
                $table->foreign('validated_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('cascade');
                
                // Unique constraint
                $table->unique('submission_id');
            });

            // Step 3: Copy data from old table to new table
            DB::statement('
                INSERT INTO approvals_temp (
                    id, approval_id, submission_id, accomp_term, sdp_ref,
                    target_q1, target_q2, target_q3, target_q4, target_total,
                    accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                    variance, rate, rating, remarks, verified_by, validated_by,
                    validated_at, created_at, updated_at
                )
                SELECT 
                    id, approval_id, submission_id, accomp_term, sdp_ref,
                    target_q1, target_q2, target_q3, target_q4, target_total,
                    accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                    variance, rate, rating, remarks, verified_by, validated_by,
                    validated_at, created_at, updated_at
                FROM approvals
            ');

            // Step 4: Drop old table
            Schema::dropIfExists('approvals');

            // Step 5: Rename new table
            Schema::rename('approvals_temp', 'approvals');
            
            // Step 6: Re-enable foreign key checks
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            // For other databases (MySQL, PostgreSQL), use ALTER TABLE
            Schema::table('approvals', function (Blueprint $table) {
                $table->string('accomp_term')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Reverse the process for SQLite
            DB::statement('PRAGMA foreign_keys=OFF');
            
            Schema::create('approvals_temp', function (Blueprint $table) {
                $table->id();
                $table->string('approval_id')->unique();
                $table->unsignedBigInteger('submission_id');
                $table->string('accomp_term'); // NOT NULL again
                $table->string('sdp_ref')->nullable();
                $table->decimal('target_q1', 10, 2)->default(0);
                $table->decimal('target_q2', 10, 2)->default(0);
                $table->decimal('target_q3', 10, 2)->default(0);
                $table->decimal('target_q4', 10, 2)->default(0);
                $table->decimal('target_total', 10, 2)->default(0);
                $table->decimal('accomp_q1', 10, 2)->default(0);
                $table->decimal('accomp_q2', 10, 2)->default(0);
                $table->decimal('accomp_q3', 10, 2)->default(0);
                $table->decimal('accomp_q4', 10, 2)->default(0);
                $table->decimal('accomp_total', 10, 2)->default(0);
                $table->decimal('variance', 10, 2)->default(0);
                $table->decimal('rate', 5, 2)->default(0);
                $table->enum('rating', ['Outstanding', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Needs Improvement'])->default('Needs Improvement');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('verified_by');
                $table->unsignedBigInteger('validated_by');
                $table->timestamp('validated_at')->nullable();
                $table->timestamps();
                
                $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade');
                $table->foreign('validated_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('cascade');
                
                $table->unique('submission_id');
            });

            DB::statement('
                INSERT INTO approvals_temp (
                    id, approval_id, submission_id, accomp_term, sdp_ref,
                    target_q1, target_q2, target_q3, target_q4, target_total,
                    accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                    variance, rate, rating, remarks, verified_by, validated_by,
                    validated_at, created_at, updated_at
                )
                SELECT 
                    id, approval_id, submission_id, COALESCE(accomp_term, \'\') as accomp_term, sdp_ref,
                    target_q1, target_q2, target_q3, target_q4, target_total,
                    accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                    variance, rate, rating, remarks, verified_by, validated_by,
                    validated_at, created_at, updated_at
                FROM approvals
            ');

            Schema::dropIfExists('approvals');
            Schema::rename('approvals_temp', 'approvals');
            
            DB::statement('PRAGMA foreign_keys=ON');
        } else {
            Schema::table('approvals', function (Blueprint $table) {
                $table->string('accomp_term')->nullable(false)->change();
            });
        }
    }
};
