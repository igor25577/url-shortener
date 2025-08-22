<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Link;

class LinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $this->artisan('migrate', ['--force' => true]);
    }

    private function loginAndGetToken(User $user): string
    {
        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertStatus(200);

        $token = $login->json('token');
        $this->assertNotEmpty($token, 'Token de login não retornado');
        return $token;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_create_link()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        $token = $this->loginAndGetToken($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->postJson('/api/links', [
            'original_url' => 'https://laravel.com',
            'expires_at'   => '2025-12-31 23:59:59',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('links', [
            'user_id'      => $user->id,
            'original_url' => 'https://laravel.com',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_create_link()
    {
        $response = $this->postJson('/api/links', [
            'original_url' => 'https://laravel.com',
        ]);

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_list_his_links()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);
        $other = User::factory()->create();

        Link::factory()->count(2)->create(['user_id' => $user->id]);
        Link::factory()->count(3)->create(['user_id' => $other->id]);

        $token = $this->loginAndGetToken($user);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/links');

        $res->assertStatus(200);
        $data = $res->json('data');

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        foreach ($data as $item) {
            $this->assertEquals($user->id, $item['user_id']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_access_others_links()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);
        $other = User::factory()->create();

        $linkOfOther = Link::factory()->create(['user_id' => $other->id]);

        $token = $this->loginAndGetToken($user);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/links/'.$linkOfOther->id);

        $res->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redirect_works_and_increments_click_count()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        $token = $this->loginAndGetToken($user);

        $create = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->postJson('/api/links', [
            'original_url' => 'https://laravel.com',
            'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertStatus(201);

        $slug = $create->json('slug');
        $this->assertNotEmpty($slug, 'Slug não retornado pelo store');

        // contador inicial
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://laravel.com',
            'click_count' => 0,
        ]);

        // rota pública de redirect
        $this->get('/api/s/'.$slug)->assertRedirect('https://laravel.com');

        // contador incrementado
        $this->assertDatabaseHas('links', [
            'original_url' => 'https://laravel.com',
            'click_count' => 1,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function expired_link_should_not_redirect()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        $token = $this->loginAndGetToken($user);

        $link = Link::factory()->create([
            'user_id' => $user->id,
            'original_url' => 'https://example.com',
            'expires_at' => now()->subMinute(),
            'click_count' => 0,
        ]);

        // Rota pública, não precisa token
        $this->get('/api/s/'.$link->slug)->assertStatus(410);

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'click_count' => 0,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_view_his_own_link_details()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        $link = Link::factory()->create([
            'user_id' => $user->id,
            'original_url' => 'https://my.com',
        ]);

        $token = $this->loginAndGetToken($user);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/links/'.$link->id);

        $res->assertStatus(200);
        $res->assertJson([
            'id' => $link->id,
            'user_id' => $user->id,
            'original_url' => 'https://my.com',
        ]);
    }
}