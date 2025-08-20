<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;

class LinkStoreTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // dtb in memory somente para teste
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
    }

    public function test_store_returns_qrcode_rl()
    {
        $user = User::factory() -> create();
        $this->actingAs($user, 'sanctum');

        $payload = ['original_url' => 'https://laravel.com'];
        $res = $this->postJson('/api/links', $payload);

        $res->assertStatus(201);
        $res->assertJsonStructure(['qrcode_url']);
        $this->assertStringContainsString('/api/qrcode/', $res->json('qrcode_url'));
    }
}