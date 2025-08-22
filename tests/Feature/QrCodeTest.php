<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Link;

class QrCodeTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config ([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
    }

    public function test_public_qrcode_by_slug_returns_png_for_active_link()
    {
        $user = User::factory() -> create();

        $link = Link::factory() -> create ([
            'user_id' => $user->id,
            'original_url' => 'https://laravel.com',
            'expires_at' => now() -> addDay(),
        ]);

        $res = $this -> get("/api/qrcode/{$link->slug}");
        $res -> assertStatus(200);
        $res -> assertHeader('Content-Type', 'image/png');
        $this ->assertNotEmpty($res->getContent());
    }

    public function test_public_qrcode_by_slug_returns_410_fo_expired_link()
    {
        $user = User::factory()->create();

        $link = Link::factory()->create([
            'user_id' => $user -> id,
            'original_url' => 'https://example.com',
            'expires_at' => now() ->subMinute(),
        ]);

        $this -> get("/api/qrcode/{$link -> slug}") ->assertStatus(410);
    }

    public function test_owner_only_can_acess_qrcode_by_id(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $link = Link::factory()->for($owner)->create([
            'original_url' => 'https://laravel.com',
            'expires_at' => now()->addDay(),
        ]);

        // autenticar como outro usuário via Bearer
        $login = $this->postJson('/api/login', [
            'email' => $other->email,
            'password' => 'password',
        ])->assertStatus(200);

        $token = $login->json('token');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        // como não-dono, deve responder 404 (oculta existência)
        $this->get("/api/links/{$link->id}/qrcode")->assertStatus(404);
    }

    public function test_autheticated_owner_gets_png_by_id(): void
    {
        $owner = User::factory()->create();

        $link = Link::factory()->for($owner)->create([
            'original_url' => 'https://laravel.com',
            'expires_at' => now()->addDay(),
        ]);

        // autenticar como dono via Bearer
        $login = $this->postJson('/api/login', [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertStatus(200);

        $token = $login->json('token');
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        $res = $this->get("/api/links/{$link->id}/qrcode");
        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'image/png');
        $this->assertNotEmpty($res->getContent());
    }

}