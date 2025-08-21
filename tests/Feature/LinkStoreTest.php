<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class LinkStoreTest extends TestCase
{
    use DatabaseMigrations;

    public function test_store_returns_qrcode_rl(): void
    {
        $user = User::factory()->create();

        // login para obter token
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(200);

        $token = $login->json('token');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        $payload = ['original_url' => 'https://laravel.com'];
        $res = $this->postJson('/api/links', $payload);

        $res->assertStatus(201);
        $res->assertJsonStructure(['qrcode_url']);
        $this->assertStringContainsString('/api/qrcode/', $res->json('qrcode_url'));
    }
}