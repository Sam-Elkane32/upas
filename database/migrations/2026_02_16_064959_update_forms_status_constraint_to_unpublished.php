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
        if (config('database.default') === 'sqlite') {
            // SQLite: Need to recreate table to modify enum constraint
            // Step 1: Get all data
            $forms = DB::table('forms')->get()->toArray();
            
            // Step 2: Drop the old table
            Schema::dropIfExists('forms');
            
            // Step 3: Recreate with updated enum constraint and ALL columns
            Schema::create('forms', function (Blueprint $table) {
                $table->id();
                $table->string('form_title');
                $table->string('division')->nullable();
                $table->string('sg_code')->nullable();
                $table->string('strategic_goal')->nullable();
                $table->string('kra_title');
                $table->string('kpi_title');
                $table->string('responsible_unit');
                $table->json('kra_kpi_data')->nullable();
                $table->decimal('target_q1', 10, 2)->default(0);
                $table->decimal('target_q2', 10, 2)->default(0);
                $table->decimal('target_q3', 10, 2)->default(0);
                $table->decimal('target_q4', 10, 2)->default(0);
                $table->decimal('target_total', 10, 2)->default(0);
                $table->string('template_id')->nullable();
                $table->string('template_code')->nullable();
                $table->enum('status', ['Unpublished', 'Published'])->default('Unpublished');
                $table->string('created_by');
                $table->string('campus_code');
                $table->timestamps();
                
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('cascade');
            });
            
            // Step 4: Restore the data with updated status values
            if (!empty($forms)) {
                foreach ($forms as $form) {
                    $formArray = (array) $form;
                    // Convert 'Draft' to 'Unpublished' during data restoration
                    if (isset($formArray['status']) && $formArray['status'] === 'Draft') {
                        $formArray['status'] = 'Unpublished';
                    }
                    unset($formArray['id']);
                    DB::table('forms')->insert($formArray);
                }
            }
        } else {
            // For MySQL/PostgreSQL: First update data, then modify enum
            DB::table('forms')
                ->where('status', 'Draft')
                ->update(['status' => 'Unpublished']);
            
            DB::statement("ALTER TABLE forms MODIFY COLUMN status ENUM('Unpublished', 'Published') DEFAULT 'Unpublished'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            // Recreate with old enum constraint
            $forms = DB::table('forms')->get()->toArray();
            Schema::dropIfExists('forms');
            
            Schema::create('forms', function (Blueprint $table) {
                $table->id();
                $table->string('form_title');
                $table->string('division')->nullable();
                $table->string('sg_code')->nullable();
                $table->string('strategic_goal')->nullable();
                $table->string('kra_title');
                $table->string('kpi_title');
                $table->string('responsible_unit');
                $table->json('kra_kpi_data')->nullable();
                $table->decimal('target_q1', 10, 2)->default(0);
                $table->decimal('target_q2', 10, 2)->default(0);
                $table->decimal('target_q3', 10, 2)->default(0);
                $table->decimal('target_q4', 10, 2)->default(0);
                $table->decimal('target_total', 10, 2)->default(0);
                $table->string('template_id')->nullable();
                $table->string('template_code')->nullable();
                $table->enum('status', ['Draft', 'Published'])->default('Draft');
                $table->string('created_by');
                $table->string('campus_code');
                $table->timestamps();
                
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('cascade');
            });
            
            if (!empty($forms)) {
                foreach ($forms as $form) {
                    $formArray = (array) $form;
                    // Convert 'Unpublished' back to 'Draft' during data restoration
                    if (isset($formArray['status']) && $formArray['status'] === 'Unpublished') {
                        $formArray['status'] = 'Draft';
                    }
                    unset($formArray['id']);
                    DB::table('forms')->insert($formArray);
                }
            }
        } else {
            // For MySQL/PostgreSQL: Revert enum column, then update data
            DB::statement("ALTER TABLE forms MODIFY COLUMN status ENUM('Draft', 'Published') DEFAULT 'Draft'");
            
            DB::table('forms')
                ->where('status', 'Unpublished')
                ->update(['status' => 'Draft']);
        }
    }
};
