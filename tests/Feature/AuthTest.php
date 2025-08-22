<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Link;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Carbon\Carbon;

class AuthTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 8, 20, 12, 0, 0));
    }

    public function test_user_can_register(): void
    {
        $payload = [
            'name' => 'Alice Tester',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $res = $this->postJson('/api/register', $payload);
        $res->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
        ]);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $res = $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'secret123',
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure([
                'message', 'token', 'token_type', 'user' => ['id', 'name', 'email']
            ]);

        $this->assertTrue(str_starts_with($res->json('token'), $user->id.'|') === false);
    }

    public function test_authenticated_user_can_create_link(): void
    {
        $user = User::create([
            'name' => 'Carol',
            'email' => 'carol@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'carol@example.com',
            'password' => 'secret123',
        ])->assertStatus(200);

        $token = $login->json('token');

        $payload = [
            'original_url' => 'https://example.com/page',
            'expires_at'   => Carbon::now()->addDays(3)->toISOString(),
        ];

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->postJson('/api/links', $payload)->assertStatus(201);

        $this->assertDatabaseHas('links', [
            'user_id' => $user->id,
            'original_url' => 'https://example.com/page',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_link(): void
    {
        $payload = [
            'original_url' => 'https://example.com/noauth',
        ];

        $this->postJson('/api/links', $payload)->assertStatus(401);
    }

    public function test_redirect_works_and_increments_click_count(): void
    {
        $user = User::factory()->create();

        // login para criar link autenticado
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $login->json('token');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        $create = $this->postJson('/api/links', [
            'original_url' => 'https://laravel.com'
        ])->assertStatus(201);

        $slug = $create->json('slug');

        // A rota pública de redirect é /api/s/{slug}
        $this->get("/api/s/{$slug}")
            ->assertStatus(302)
            ->assertRedirect('https://laravel.com');

        $link = Link::where('slug', $slug)->first();
        $this->assertEquals(1, $link->click_count);
    }

    public function test_dashboard_returns_statistics(): void
    {
        $user = User::create([
            'name' => 'Eve',
            'email' => 'eve@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'eve@example.com',
            'password' => 'secret123',
        ])->assertStatus(200);
        $token = $login->json('token');

        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->postJson('/api/links', [
                'original_url' => "https://example.com/{$i}",
            ])->assertStatus(201);
        }

        $res = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard')->assertStatus(200);

        $json = $res->json();
        $this->assertArrayHasKey('totals', $json);
        $this->assertArrayHasKey('by_status', $json);
        $this->assertArrayHasKey('last7_days', $json);
        $this->assertArrayHasKey('top_links', $json);

        $this->assertArrayHasKey('total_links', $json);
        $this->assertArrayHasKey('total_clicks', $json);
        $this->assertArrayHasKey('active_links', $json);
        $this->assertArrayHasKey('expired_links', $json);

        $this->assertEquals(3, $json['totals']['total_links']);
    }

    public function test_user_can_logout_and_private_routes_are_blocked_after(): void
    {
        $user = User::factory()->create();

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(200);

        $token = $login->json('token');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        // acessar uma rota privada com token válido
        $this->getJson('/api/links')->assertStatus(200);

        // logout
        $this->postJson('/api/logout')->assertStatus(200);

        // limpar headers e verificar bloqueio sem Authorization
        $this->flushHeaders();
        $this->getJson('/api/links')->assertStatus(401);
    }
}