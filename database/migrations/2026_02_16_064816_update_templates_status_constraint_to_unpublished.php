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
            $templates = DB::table('templates')->get()->toArray();
            
            // Step 2: Drop the old table
            Schema::dropIfExists('templates');
            
            // Step 3: Recreate with updated enum constraint
            Schema::create('templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id')->nullable();
                $table->string('sg_code')->nullable();
                $table->string('template_code');
                $table->string('kra_title');
                $table->string('kpi_title', 300);
                $table->json('fields_json');
                $table->enum('status', ['Unpublished', 'Published'])->default('Unpublished');
                $table->string('created_by');
                $table->string('campus_code');
                $table->unsignedBigInteger('assigned_user_id')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('cascade');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
            
            // Step 4: Restore the data with updated status values
            if (!empty($templates)) {
                foreach ($templates as $template) {
                    $templateArray = (array) $template;
                    // Convert 'Draft' to 'Unpublished' during data restoration
                    if (isset($templateArray['status']) && $templateArray['status'] === 'Draft') {
                        $templateArray['status'] = 'Unpublished';
                    }
                    unset($templateArray['id']);
                    DB::table('templates')->insert($templateArray);
                }
            }
        } else {
            // For MySQL/PostgreSQL: First update data, then modify enum
            DB::table('templates')
                ->where('status', 'Draft')
                ->update(['status' => 'Unpublished']);
            
            DB::statement("ALTER TABLE templates MODIFY COLUMN status ENUM('Unpublished', 'Published') DEFAULT 'Unpublished'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            // Recreate with old enum constraint
            $templates = DB::table('templates')->get()->toArray();
            Schema::dropIfExists('templates');
            
            Schema::create('templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id')->nullable();
                $table->string('sg_code')->nullable();
                $table->string('template_code');
                $table->string('kra_title');
                $table->string('kpi_title', 300);
                $table->json('fields_json');
                $table->enum('status', ['Draft', 'Published'])->default('Draft');
                $table->string('created_by');
                $table->string('campus_code');
                $table->unsignedBigInteger('assigned_user_id')->nullable();
                $table->timestamps();
                
                $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('campus_code')->references('code')->on('campuses')->onDelete('cascade');
                $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            });
            
            if (!empty($templates)) {
                foreach ($templates as $template) {
                    $templateArray = (array) $template;
                    // Convert 'Unpublished' back to 'Draft' during data restoration
                    if (isset($templateArray['status']) && $templateArray['status'] === 'Unpublished') {
                        $templateArray['status'] = 'Draft';
                    }
                    unset($templateArray['id']);
                    DB::table('templates')->insert($templateArray);
                }
            }
        } else {
            // For MySQL/PostgreSQL: Revert enum column, then update data
            DB::statement("ALTER TABLE templates MODIFY COLUMN status ENUM('Draft', 'Published') DEFAULT 'Draft'");
            
            DB::table('templates')
                ->where('status', 'Unpublished')
                ->update(['status' => 'Draft']);
        }
    }
};
