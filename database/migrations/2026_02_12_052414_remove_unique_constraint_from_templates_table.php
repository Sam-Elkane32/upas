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
            // SQLite: Need to recreate table to remove unique constraint
            // Step 1: Get all data
            $templates = DB::table('templates')->get()->toArray();
            
            // Step 2: Drop the old table
            Schema::dropIfExists('templates');
            
            // Step 3: Recreate without unique constraint on template_code
            Schema::create('templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id')->nullable();
                $table->string('sg_code')->nullable();
                $table->string('template_code'); // NO ->unique() here
                $table->string('kra_title');
                $table->string('kpi_title', 300);
                $table->json('fields_json');
                $table->enum('status', ['Draft', 'Published'])->default('Draft');
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
            
            // Step 4: Restore the data
            if (!empty($templates)) {
                foreach ($templates as $template) {
                    $templateArray = (array) $template;
                    // Remove 'id' to let it auto-increment, or keep it if you want to preserve IDs
                    unset($templateArray['id']);
                    DB::table('templates')->insert($templateArray);
                }
            }
        } else {
            // For MySQL/PostgreSQL: Drop unique constraint
            try {
                Schema::table('templates', function (Blueprint $table) {
                    $table->dropUnique(['template_code']);
                });
            } catch (\Exception $e) {
                // Try alternative constraint names
                try {
                    DB::statement('ALTER TABLE templates DROP INDEX templates_template_code_unique');
                } catch (\Exception $e2) {
                    try {
                        DB::statement('ALTER TABLE templates DROP INDEX template_code_unique');
                    } catch (\Exception $e3) {
                        // Constraint might not exist, which is fine
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            // Recreate with unique constraint (for rollback)
            $templates = DB::table('templates')->get()->toArray();
            Schema::dropIfExists('templates');
            
            Schema::create('templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id')->nullable();
                $table->string('sg_code')->nullable();
                $table->string('template_code')->unique(); // Restore unique
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
                    unset($templateArray['id']);
                    DB::table('templates')->insert($templateArray);
                }
            }
        } else {
            // Restore unique constraint
            try {
                Schema::table('templates', function (Blueprint $table) {
                    $table->unique('template_code');
                });
            } catch (\Exception $e) {
                // Might fail if constraint already exists
            }
        }
    }
};
