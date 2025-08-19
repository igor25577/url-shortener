<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Link;

class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        return [
            'original_url' => $this->faker->url(),
            'slug' => $this->faker->unique()->slug(2), // slug curto
            'click_count' => 0,
            'status' => 'active',
            'expires_at' => null,
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
