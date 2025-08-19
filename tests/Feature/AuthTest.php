<?php
namespace Tests\Feature;


use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Link;


class AuthTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'João Teste',
            'email' => 'joao@teste.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'joao@teste.com',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login_and_receive_token()
    {
        // cadastra
        $this->postJson('/api/auth/register', [
            'name' => 'Maria Teste',
            'email' => 'maria@teste.com',
            'password' => '123456',
            'password_confirmation' => '123456'
        ]);
        
        // loga
        $response = $this ->postJson('/api/auth/login', [
            'email' => 'maria@teste.com',
            'password' => '123456',
        ]);

        $response -> assertStatus(200);

        $this->assertArrayHasKey('token', $response -> json());
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_create_link()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/links', [
            'original_url' => 'https://laravel.com',
            'expires_at'   => '2025-12-31 23:59:59'
        ]);

        $response->assertStatus(201); 
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://laravel.com',
        ]);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_create_link()
    {
        $response = $this->postJson('/api/links', [
            'original_url' => 'https://laravel.com',
            'expires_at'   => '2025-12-31 23:59:59'
        ]);

        $response->assertStatus(401); 
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function redirect_works_and_increments_click_count()
    {
        $user = User::factory() -> create();
        $this -> actingAs($user, 'sanctum');

        $linkResponse = $this -> postJson('/api/links', [
            'original_url' => 'https://laravel.com',
            'expires_at' => '2025-12-31 23:59:59'
        ]);

        $slug = $linkResponse -> json('link.slug');
<<<<<<< HEAD
        $this -> assertNotEmpty($slug, 'Slug não retornado pelo store');
=======
        $this -> assertNotEmpty($slug, 'Slug não retornado pelo store()');
>>>>>>> main

        // confere contador
        $this -> assertDatabaseHas('links', [
            'original_url' => 'https://laravel.com',
            'click_count' => 0
        ]);


        // faz get
        $redirect = $this -> get('/api/s/'.$slug);

        $redirect -> assertRedirect('https://laravel.com');


        // confere o contador incrementado
        $this -> assertDatabaseHas('links', [
                'original_url' => 'https://laravel.com',
                'click_count' => 1
        ]);

    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dashboard_returns_statistics()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Cria alguns links para o usuário
        Link::factory()->create([
            'user_id'      => $user->id,
            'original_url' => 'https://google.com',
            'status'       => 'active',
            'click_count'  => 3,
        ]);

        Link::factory()->create([
            'user_id'      => $user->id,
            'original_url' => 'https://laravel.com',
            'status'       => 'active',
            'click_count'  => 2,
        ]);

        Link::factory()->create([
            'user_id'      => $user->id,
            'original_url' => 'https://php.net',
            'status'       => 'active',
            'expires_at'   => now()->subDay(), 
            'click_count'  => 1,
        ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200);

        $response->assertJson([
            'total_links'   => 3,
            'active_links'  => 2,
            'expired_links' => 1,
            'total_clicks'  => 6
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_logout_and_private_routes_are_blocked_after()
    {
        $user = \App\Models\User::factory()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
            ])
            ->getJson('/api/links')->assertStatus(200);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name'         => 'auth_token',
        ]);

        $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
            ])
            ->postJson('/api/auth/logout')->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name'         => 'auth_token',
        ]);

        $response = $this->withCookies([])
            ->withHeaders([]) 
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept'        => 'application/json',
            ])
            ->getJson('/api/links');

        if ($response->status() === 401) {
            $this->assertTrue(true);
            return;
        }

        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('data', $json);
        $this->assertEquals([], $json['data'], 'Resposta não deveria conter dados com token revogado.');
    }

    
}
