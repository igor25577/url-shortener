<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RateLimitLinksTest extends TestCase
{
    use DatabaseMigrations;

    public function test_create_links_is_rate_limited_to_30_per_minute(): void
    {
        $user = User::factory()->create();

        // login via Bearer
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(200);

        $token = $login->json('token');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        // Faz 30 requisições válidas
        for ($i = 1; $i <= 30; $i++) {
            $res = $this->postJson('/api/links', [
                'original_url' => 'https://example.com/' . $i,
            ]);
            // Aceitar 201 ou 200 dependendo da tua implementação (usamos 201)
            $res->assertStatus(201);
        }

        // A 31ª no mesmo "minuto" deve ser bloqueada por rate limit → 429
        $this->postJson('/api/links', [
            'original_url' => 'https://example.com/overflow',
        ])->assertStatus(429);
    }
}