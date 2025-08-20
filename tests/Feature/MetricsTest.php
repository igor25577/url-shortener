<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Link;

class MetricsTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $login = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $this->token = $login->status() === 200 && $login->json('token')
            ? $login->json('token')
            : $this->user->createToken('test')->plainTextToken;

        $now = Carbon::now();

        // Dados do usuário principal
        //  ativos (não expirados)
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
            'expires_at' => $now->copy()->addDays(2),
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
            'expires_at' => $now->copy()->subDay(),
            'status' => 'active',
            'created_at' => $now->copy()->subDays(3),
        ]);
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 2,
            'expires_at' => $now->copy()->subDays(5),
            'status' => 'active',
            'created_at' => $now->copy()->subDays(5),
        ]);

        //  inativo
        Link::factory()->create([
            'user_id' => $this->user->id,
            'click_count' => 0,
            'expires_at' => null,
            'status' => 'inactive',
            'created_at' => $now->copy()->subDays(4),
        ]);

        // Dados de outro usuário 
        $other = User::factory()->create();
        Link::factory()->count(2)->create([
            'user_id' => $other->id,
            'click_count' => 99,
            'expires_at' => null,
            'status' => 'active',
            'created_at' => $now->copy()->subDays(1),
        ]);
    }

    protected function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token, 'Accept' => 'application/json'];
    }

    public function test_summary_returns_totals_and_by_status(): void
    {
        $res = $this->getJson('/api/metrics/summary', $this->headers());

        $res->assertStatus(200)
            ->assertJsonStructure([
                'totals' => ['total_links', 'total_clicks'],
                'by_status' => ['active', 'expired', 'inactive'],
            ]);

        $json = $res->json();

        $this->assertSame(6, $json['totals']['total_links']);
        $this->assertSame(8, $json['totals']['total_clicks']);
        $this->assertSame(3, $json['by_status']['active']);
        $this->assertSame(2, $json['by_status']['expired']);
        $this->assertSame(1, $json['by_status']['inactive']);
    }

    public function test_top_returns_top5_links(): void
    {
        $res = $this->getJson('/api/metrics/top', $this->headers());
        $res->assertStatus(200)->assertJsonStructure([
            'top_links' => [['id', 'slug', 'original_url', 'click_count']]
        ]);

        $top = $res->json('top_links');
        $this->assertLessThanOrEqual(5, count($top));
        for ($i = 0; $i < count($top) - 1; $i++) {
            $this->assertGreaterThanOrEqual($top[$i+1]['click_count'], $top[$i]['click_count']);
        }
    }

    public function test_by_month_returns_last_6_months(): void
    {
        $res = $this->getJson('/api/metrics/by-month', $this->headers());
        $res->assertStatus(200)->assertJsonStructure([
            'months' => [['month', 'links', 'clicks']]
        ]);

        $months = $res->json('months');
        $this->assertCount(6, $months);
        $thisMonth = Carbon::now()->format('Y-m');
        $this->assertSame($thisMonth, $months[5]['month']);
    }
}