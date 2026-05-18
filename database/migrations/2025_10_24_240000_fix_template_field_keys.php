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
        // Update existing templates to use proper field keys
        $templates = DB::table('templates')->get();
        
        foreach ($templates as $template) {
            $fieldsJson = json_decode($template->fields_json, true);
            
            if (isset($fieldsJson['fields'])) {
                $updatedFields = [];
                
                foreach ($fieldsJson['fields'] as $field) {
                    // Generate normalized key if not present
                    $key = $field['key'] ?? strtolower(str_replace([' ', '-'], '_', $field['label']));
                    
                    $updatedField = array_merge($field, ['key' => $key]);
                    $updatedFields[] = $updatedField;
                }
                
                $fieldsJson['fields'] = $updatedFields;
                
                // Update the template
                DB::table('templates')
                    ->where('id', $template->id)
                    ->update(['fields_json' => json_encode($fieldsJson)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert templates to original structure
        $templates = DB::table('templates')->get();
        
        foreach ($templates as $template) {
            $fieldsJson = json_decode($template->fields_json, true);
            
            if (isset($fieldsJson['fields'])) {
                $updatedFields = [];
                
                foreach ($fieldsJson['fields'] as $field) {
                    // Remove key field
                    unset($field['key']);
                    $updatedFields[] = $field;
                }
                
                $fieldsJson['fields'] = $updatedFields;
                
                // Update the template
                DB::table('templates')
                    ->where('id', $template->id)
                    ->update(['fields_json' => json_encode($fieldsJson)]);
            }
        }
    }
};

