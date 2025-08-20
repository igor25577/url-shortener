<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Link;
use App\Models\User;

class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slug' => Str::random(6),               // evita hífens e caracteres especiais
            'original_url' => $this->faker->url(),
            'click_count' => 0,
            'status' => 'active',
            'expires_at' => null,
            'created_at' => Carbon::now(),          // útil para testes que mexem em datas
            'updated_at' => Carbon::now(),
        ];
    }
}