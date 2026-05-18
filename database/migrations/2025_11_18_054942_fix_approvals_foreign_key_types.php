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
        if (!Schema::hasTable('approvals')) {
            return;
        }

        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        // Step 1: Drop foreign key constraints if they exist
        try {
            DB::statement('PRAGMA foreign_keys=OFF');
            Schema::table('approvals', function (Blueprint $table) {
                try {
                    $table->dropForeign(['submission_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                try {
                    $table->dropForeign(['validated_by']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            });
        } catch (\Exception $e) {
            // Continue if foreign keys don't exist
        }

        // Step 2: Create a new table with correct types
        Schema::dropIfExists('approvals_new');

        Schema::create('approvals_new', function (Blueprint $table) {
            $table->id();
            $table->string('approval_id')->unique();
            $table->unsignedBigInteger('submission_id'); // Changed from string to unsignedBigInteger
            $table->string('accomp_term');
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
            $table->unsignedBigInteger('verified_by'); // Changed from string to unsignedBigInteger
            $table->unsignedBigInteger('validated_by'); // Changed from string to unsignedBigInteger
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            
            // Foreign keys with correct types
            $table->foreign('submission_id')->references('id')->on('submissions')->onDelete('cascade');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint
            $table->unique('submission_id');
        });

        // Step 3: Copy data from old table to new table (converting strings to integers)
        $hasVerifiedBy = Schema::hasColumn('approvals', 'verified_by');
        if ($hasVerifiedBy) {
            DB::statement('
            INSERT INTO approvals_new (
                id, approval_id, submission_id, accomp_term, sdp_ref,
                target_q1, target_q2, target_q3, target_q4, target_total,
                accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                variance, rate, rating, remarks, verified_by, validated_by,
                validated_at, created_at, updated_at
            )
            SELECT 
                id, approval_id, CAST(submission_id AS INTEGER), accomp_term, sdp_ref,
                target_q1, target_q2, target_q3, target_q4, target_total,
                accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                variance, rate, rating, remarks, 
                CAST(COALESCE(verified_by, validated_by) AS INTEGER) as verified_by,
                CAST(validated_by AS INTEGER), validated_at, created_at, updated_at
            FROM approvals
        ');
        } else {
            DB::statement('
            INSERT INTO approvals_new (
                id, approval_id, submission_id, accomp_term, sdp_ref,
                target_q1, target_q2, target_q3, target_q4, target_total,
                accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                variance, rate, rating, remarks, verified_by, validated_by,
                validated_at, created_at, updated_at
            )
            SELECT 
                id, approval_id, CAST(submission_id AS INTEGER), accomp_term, sdp_ref,
                target_q1, target_q2, target_q3, target_q4, target_total,
                accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                variance, rate, rating, remarks, 
                CAST(validated_by AS INTEGER), CAST(validated_by AS INTEGER),
                validated_at, created_at, updated_at
            FROM approvals
        ');
        }

        // Step 4: Drop old table
        Schema::dropIfExists('approvals');

        // Step 5: Rename new table
        Schema::rename('approvals_new', 'approvals');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the process
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropForeign(['submission_id']);
            $table->dropForeign(['validated_by']);
            $table->dropForeign(['verified_by']);
        });

        Schema::create('approvals_old', function (Blueprint $table) {
            $table->id();
            $table->string('approval_id')->unique();
            $table->string('submission_id');
            $table->string('accomp_term');
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
            $table->string('verified_by');
            $table->string('validated_by');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO approvals_old (
                id, approval_id, submission_id, accomp_term, sdp_ref,
                target_q1, target_q2, target_q3, target_q4, target_total,
                accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                variance, rate, rating, remarks, verified_by, validated_by,
                validated_at, created_at, updated_at
            )
            SELECT 
                id, approval_id, CAST(submission_id AS TEXT), accomp_term, sdp_ref,
                target_q1, target_q2, target_q3, target_q4, target_total,
                accomp_q1, accomp_q2, accomp_q3, accomp_q4, accomp_total,
                variance, rate, rating, remarks,
                CAST(verified_by AS TEXT), CAST(validated_by AS TEXT),
                validated_at, created_at, updated_at
            FROM approvals
        ');

        Schema::dropIfExists('approvals');
        Schema::rename('approvals_old', 'approvals');
    }
};
