<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    public function definition(): array
    {
        return [
            'template_id' => null,
            'form_id' => null,
            'template_code' => 'T1',
            'form_title' => 'SG1 - T1',
            'sg_code' => 'SG1',
            'kra_title' => 'Test KRA',
            'kpi_title' => 'Test KPI',
            'campus' => 'Test Campus',
            'campus_code' => 'TEST',
            'quarter' => '1st Q',
            'table_data' => [],
            'status' => 'Unpublished',
            'submitted_by' => User::factory(),
            'submitted_at' => null,
            'last_updated' => now(),
            'is_draft' => true,
            'draft_version' => 1,
            'last_draft_at' => null,
        ];
    }
}
