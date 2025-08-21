<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Link;
use App\Models\Visit;

class VisitsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_redirect_records_visits_and_dashboard_shows_clicks_by_day(): void
    {
        // Usuário e token 
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Link ativo que expira amanhã
        $link = Link::factory()->create([
            'user_id'     => $user->id,
            'expires_at'  => now()->addDay(),
            'status'      => 'active',
            'click_count' => 0,
        ]);

        // Simula 3 acessos ao slug 
        for ($i = 0; $i < 3; $i++) {
            $this->get("/api/s/{$link->slug}", [
                'User-Agent'       => 'PHPUnit Test UA '.$i,
                'X-Forwarded-For'  => "127.0.0.$i", 
            ])->assertStatus(302); 
        }

        // Verifica incrementos
        $link->refresh();
        $this->assertSame(3, $link->click_count, 'click_count deveria ser 3 após 3 redirecionamentos');

        // Verifica visitas persistidas
        $this->assertSame(3, Visit::where('link_id', $link->id)->count(), 'Deveria existir 3 visitas para o link');

        // Consulta o dashboard do dono do link
        $res = $this->getJson('/api/dashboard', [
            'Authorization' => 'Bearer '.$token,
            'Accept'        => 'application/json',
        ])->assertStatus(200);

        $json = $res->json();
        $today = Carbon::now()->toDateString();

        // A série clicks_by_day deve ter 7 itens e o de hoje deve ser 3
        $this->assertCount(7, $json['last7_days']['clicks_by_day']);
        $rowToday = collect($json['last7_days']['clicks_by_day'])->firstWhere('date', $today);
        $this->assertNotNull($rowToday, 'Não encontrou o dia de hoje na série clicks_by_day');
        $this->assertSame(3, $rowToday['count'], 'clicks_by_day do dia de hoje deveria ser 3');
    }

    public function test_expired_link_returns_410_and_does_not_record_visit(): void
    {
        $user = User::factory()->create();

        $expiredLink = Link::factory()->create([
            'user_id'     => $user->id,
            'expires_at'  => now()->subDay(), 
            'status'      => 'active',
            'click_count' => 0,
        ]);

        $this->get("/api/s/{$expiredLink->slug}")
            ->assertStatus(410);

        $expiredLink->refresh();
        $this->assertSame(0, $expiredLink->click_count, 'Não deve incrementar clicks em link expirado');
        $this->assertSame(0, Visit::where('link_id', $expiredLink->id)->count(), 'Não deve registrar visita em link expirado');
    }
}