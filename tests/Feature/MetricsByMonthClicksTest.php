<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Link;
use App\Models\Visit;

class MetricsByMonthClicksTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        // Token para autenticar no endpoint /api/metrics/by-month
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/json',
        ];
    }

    public function test_by_month_returns_clicks_aggregated_from_visits(): void
    {
        $now = Carbon::now();

        // Cria dois links do usuário
        $linkThisMonth = Link::factory()->create([
            'user_id'     => $this->user->id,
            'status'      => 'active',
            'expires_at'  => $now->copy()->addDays(5),
            'click_count' => 0,
            'created_at'  => $now->copy()->subDays(1),
        ]);

        $linkLastMonth = Link::factory()->create([
            'user_id'     => $this->user->id,
            'status'      => 'active',
            'expires_at'  => $now->copy()->addDays(10),
            'click_count' => 0,
            'created_at'  => $now->copy()->subDays(35), // mês anterior
        ]);

        // Cria visitas "manuais" com created_at no mês atual e no mês anterior
        Visit::factory()->count(3)->create([
            'link_id'     => $linkThisMonth->id,
            'user_agent'  => 'PHPUnit',
            'ip_address'  => '127.0.0.1',
            'created_at'  => $now->copy()->subDays(1), 
            'updated_at'  => $now->copy()->subDays(1),
        ]);

        Visit::factory()->count(2)->create([
            'link_id'     => $linkLastMonth->id,
            'user_agent'  => 'PHPUnit',
            'ip_address'  => '127.0.0.2',
            // Ajusta created_at para cair no mês anterior
            'created_at'  => $now->copy()->subMonth()->startOfMonth()->addDays(2),
            'updated_at'  => $now->copy()->subMonth()->startOfMonth()->addDays(2),
        ]);

        
        // Um link criado mês atual e outro no mês anterior já foram criados acima.

        $res = $this->getJson('/api/metrics/by-month', $this->headers())
            ->assertStatus(200)
            ->assertJsonStructure([
                'months' => [['month', 'links', 'clicks']]
            ]);

        $months = collect($res->json('months'));

        $thisMonthKey = $now->format('Y-m');
        $lastMonthKey = $now->copy()->subMonth()->format('Y-m');

        $thisMonthRow = $months->firstWhere('month', $thisMonthKey);
        $lastMonthRow = $months->firstWhere('month', $lastMonthKey);

        // Valida existência das linhas
        $this->assertNotNull($thisMonthRow, 'Não encontrou o mês atual na resposta');
        $this->assertNotNull($lastMonthRow, 'Não encontrou o mês anterior na resposta');

        // Valida os agregados 
        // Mês atual
        $this->assertSame(1, (int) $thisMonthRow['links'], 'Links do mês atual deveriam ser 1');
        $this->assertSame(3, (int) $thisMonthRow['clicks'], 'Clicks do mês atual deveriam ser 3');

        // Mês anterior
        $this->assertSame(1, (int) $lastMonthRow['links'], 'Links do mês anterior deveriam ser 1');
        $this->assertSame(2, (int) $lastMonthRow['clicks'], 'Clicks do mês anterior deveriam ser 2');
    }
}