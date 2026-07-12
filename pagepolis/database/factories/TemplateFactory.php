<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'category' => $this->faker->randomElement(['Servicios', 'Restaurante', 'SaaS']),
            'thumbnail' => null,
            'html' => '<h1>Hola</h1>',
            'css' => 'h1{color:red}',
            'js' => null,
            'tags' => ['moderno'],
            'is_premium' => false,
            'is_active' => true,
            'uses_count' => 0,
        ];
    }
}
