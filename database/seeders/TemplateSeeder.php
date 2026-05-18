<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $campus = Campus::first();
        $user = User::first();
        if (!$campus || !$user) {
            return;
        }

        $baseData = [
            'created_by' => $user->id,
            'campus_code' => $campus->code,
            'status' => 'Published',
        ];

        $templates = [
            ['sg_code' => 'SG1', 'template_code' => 'T1', 'kra_title' => 'Basic Accomplishment', 'kpi_title' => 'Standard accomplishment reporting for general KPIs'],
        ];

        $fieldsBase = [
            ['label' => 'Campus', 'type' => 'text', 'required' => true, 'name' => 'campus'],
            ['label' => 'Quarter', 'type' => 'dropdown', 'options' => ['1st Q', '2nd Q', '3rd Q', '4th Q'], 'required' => true, 'name' => 'quarter'],
        ];

        foreach ($templates as $t) {
            if (Template::where('template_code', $t['template_code'])->exists()) {
                continue;
            }
            Template::create(array_merge($baseData, $t, [
                'fields_json' => ['fields' => array_merge($fieldsBase, [['label' => 'Remarks', 'type' => 'textarea', 'required' => false, 'name' => 'remarks']])],
            ]));
        }
    }
}
