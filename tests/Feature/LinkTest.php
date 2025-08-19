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

        // Força uso de SQLite em memória nos testes
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        // Executa migrations antes de cada teste
        $this->artisan('migrate', ['--force' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authenticated_user_can_create_link()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/links', [
            'original_url' => 'https://laravel.com',
            'expires_at'   => '2025-12-31 23:59:59',
        ]);

        // Ajuste para o status que seu controller retorna: 201 (recomendado) ou 200.
        $response->assertStatus(201);

        $this->assertDatabaseHas('links', [
            'user_id'      => $user->id,
            'original_url' => 'https://laravel.com',
        ]);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_user_cannot_create_link()
    {
        $response = $this -> postJson('/api/links', [
            'original_url' => 'https://laravel.com',
        ]);

        $response -> assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_list_his_links() 
    {
        $user = User::factory() -> create();
        $other = User::factory() -> create();

        Link::factory() -> count(2) -> create (['user_id' => $user -> id]);
        Link::factory() -> count(3) -> create (['user_id' => $other -> id]);

        $this -> actingAs($user);

        $res = $this -> getJson('/api/links');

        $res -> assertStatus(200);
        $data = $res -> json();

        $this -> assertIsArray($data);
        $this -> assertCount(2, $data);
        foreach ($data as $item) {
            $this -> assertEquals($user ->id, $item['user_id']);
        }
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_access_others_links()
    {
        $user = User::factory() -> create();
        $other = User::factory() -> create();

        $linkOfOther = Link::factory() -> create (['user_id' => $other -> id]);

        $this -> actingAs ($user);

        $res = $this -> getJson ('/api/links/' .$linkOfOther -> id);

        $res -> assertStatus(404);
    }


    #[\PHPUnit\Framework\Attributes\Test]
    public function redirect_works_and_increments_click_count()
    {
        $user = User::factory() -> create();
        $this -> actingAs($user);

        $create = $this -> postJson('/api/links', [
            'original_url' => 'https://laravel.com',
            'expires_at' => now() -> addDay() -> format('Y-m-d H:i:s'),
        ]) -> assertStatus(201);

        $slug = $create -> json('link.slug');

        // contador
        $this -> assertDatabaseHas('links', [
            'original_url' => 'https://laravel.com',
            'click_count' => 0,
        ]);

        // rota de red.
        $this ->get('/api/s/'.$slug) -> assertRedirect('https://laravel.com');

        // cont. increment.
        $this -> assertDatabaseHas('links', [
            'original_url' => 'https://laravel.com',
            'click_count' => 1,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function expired_link_should_not_redirect()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $link = Link::factory()->create([
            'user_id' => $user->id,
            'original_url' => 'https://example.com',
            'expires_at' => now()->subMinute(),
            'click_count' => 0,
        ]);

        $this->get('/api/s/'.$link->slug)->assertStatus(410);

        $this->assertDatabaseHas('links', [
            'id' => $link->id,
            'click_count' => 0,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_view_his_own_link_details()
    {
        $user = User::factory() -> create();
        $link = Link::factory() -> create ([
            'user_id' => $user -> id,
            'original_url' => 'https://my.com',
        ]);

        $this -> actingAs($user);

        $res = $this -> getJson('/api/links/'. $link -> id);
        $res -> assertStatus(200);
        $res -> assertJson ([
            'id' => $link -> id,
            'user_id' => $user -> id,
            'original_url' => 'https://my.com',
        ]);
    }

}