<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Link;

class DashboardTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria usuário e token
        $this->user = User::factory()->create();
        $res = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password', 
        ]);
        
        if ($res->status() !== 200) {
            $this->token = $this->user->createToken('test')->plainTextToken;
        } else {
            $this->token = $res->json('token');
        }

        // Semente de dados para o usuário
        $now = Carbon::now();

        //  ativos (sem expiração ou expiração futura)
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 3,
            'expires_at' => null,
            'status' => 'active',
            'created_at' => $now->copy()->subDays(1),
        ]);
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 1,
            'expires_at' => $now->copy()->addDay(), // futuro
            'status' => 'active',
            'created_at' => $now->copy()->subDays(2),
        ]);
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 0,
            'expires_at' => null,
            'status' => 'active',
            'created_at' => $now->copy()->subDays(6),
        ]);

        //  expirados
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 2,
            'expires_at' => $now->copy()->subDay(), // passado
            'status' => 'active',
            'created_at' => $now->copy()->subDays(3),
        ]);
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 2,
            'expires_at' => $now->copy()->subDays(5), // passado
            'status' => 'active',
            'created_at' => $now->copy()->subDays(5),
        ]);

        //  inativo (status = inactive)
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 0,
            'expires_at' => null,
            'status' => 'inactive',
            'created_at' => $now->copy()->subDays(4),
        ]);

        // Dados de outro usuário (não devem aparecer)
        $other = User::factory()->create();
        Link::factory()->count(2)->create([
            'user_id' => $other->id,
            'click_count' => 99,
            'expires_at' => null,
            'status' => 'active',
            'created_at' => $now->copy()->subDays(1),
        ]);
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token, 'Accept' => 'application/json'];
    }

    public function test_dashboard_returns_expected_contract_and_values(): void
    {
        $res = $this->getJson('/api/dashboard', $this->authHeaders());

        $res->assertStatus(200)
            ->assertJsonStructure([
                'totals' => ['total_links', 'total_clicks'],
                'by_status' => ['active', 'expired', 'inactive'],
                'last7_days' => [
                    'links_by_day' => [['date', 'count']],
                    'clicks_by_day' => [['date', 'count']],
                ],
                'top_links' => [['id', 'slug', 'original_url', 'click_count']],
            ]);

        $json = $res->json();

        // Totais esperados (para o usuário principal)
        $this->assertSame(6, $json['totals']['total_links']);
        $this->assertSame(8, $json['totals']['total_clicks']);

        // By status
        $this->assertSame(3, $json['by_status']['active']);
        $this->assertSame(2, $json['by_status']['expired']);
        $this->assertSame(1, $json['by_status']['inactive']);

        // Last 7 days: 7 itens em cada série
        $this->assertCount(7, $json['last7_days']['links_by_day']);
        $this->assertCount(7, $json['last7_days']['clicks_by_day']);

        // clicks_by_day são zeros no momento
        foreach ($json['last7_days']['clicks_by_day'] as $row) {
            $this->assertSame(0, $row['count']);
        }

        // Top links: ordenado desc por click_count
        $top = $json['top_links'];
        $this->assertLessThanOrEqual(5, count($top));
        for ($i = 0; $i < count($top) - 1; $i++) {
            $this->assertGreaterThanOrEqual($top[$i+1]['click_count'], $top[$i]['click_count']);
        }
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }
    
}