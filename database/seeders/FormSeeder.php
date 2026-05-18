<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Template;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    /**
     * Create forms from existing templates (one form per template per campus).
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            return;
        }

        $templates = Template::all();
        $campuses = Campus::where('is_active', true)->get();

        if ($templates->isEmpty() || $campuses->isEmpty()) {
            return;
        }

        foreach ($templates as $template) {
            foreach ($campuses as $campus) {
                $exists = Form::where('template_code', $template->template_code)
                    ->where('campus_code', $campus->code)
                    ->exists();

                if ($exists) {
                    continue;
                }

                Form::create([
                    'form_title' => $template->kra_title . ' - ' . $campus->name,
                    'division' => null,
                    'sg_code' => $template->sg_code ?? 'SG1',
                    'strategic_goal' => null,
                    'kra_title' => $template->kra_title,
                    'kpi_title' => $template->kpi_title,
                    'responsible_unit' => 'Planning and Development Office',
                    'template_code' => $template->template_code,
                    'status' => 'Published',
                    'created_by' => $user->id,
                    'campus_code' => $campus->code,
                ]);
            }
        }
    }
}
