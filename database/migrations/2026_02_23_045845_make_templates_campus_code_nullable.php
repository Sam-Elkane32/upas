<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes templates.campus_code nullable so "All Campuses" can be stored as NULL.
     */
    public function up(): void
    {
        if (config('database.default') === 'sqlite') {
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
                $table->enum('status', ['Unpublished', 'Published'])->default('Unpublished');
                $table->string('created_by');
                $table->string('campus_code')->nullable();
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
            Schema::table('templates', function (Blueprint $table) {
                $table->string('campus_code')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
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
                $table->enum('status', ['Unpublished', 'Published'])->default('Unpublished');
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
                    if (empty($templateArray['campus_code'])) {
                        $templateArray['campus_code'] = 'LINGAYEN';
                    }
                    unset($templateArray['id']);
                    DB::table('templates')->insert($templateArray);
                }
            }
        } else {
            Schema::table('templates', function (Blueprint $table) {
                $table->string('campus_code')->nullable(false)->change();
            });
        }
    }
};
