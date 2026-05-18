<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        $code = 'T'.$this->faker->unique()->numberBetween(1, 9999);

        return [
            'form_id' => null,
            'sg_code' => 'SG'.$this->faker->numberBetween(1, 5),
            'template_code' => $code,
            'kra_title' => $this->faker->sentence(4),
            'kpi_title' => $this->faker->sentence(6),
            'fields_json' => [
                'fields' => [],
                'summary_rules' => [],
            ],
            'status' => 'Published',
            'created_by' => fn () => (string) (User::query()->orderBy('id')->value('id') ?? User::factory()->create()->id),
            'campus_code' => null,
            'campus_codes' => null,
            'assigned_user_id' => null,
        ];
    }
}
