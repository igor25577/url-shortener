<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Link;

class MetricsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Força uso SQLite em memória
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        // Executa migrations antes de cada teste
        $this->artisan('migrate', ['--force' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function metrics_summary_returns_correct_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Link::factory()->create([
            'user_id' => $user->id,
            'click_count' => 5,
            'status' => 'active',
        ]);

        Link::factory()->create([
            'user_id' => $user->id,
            'click_count' => 3,
            'expires_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/metrics/summary');

        $response->assertStatus(200);
        $response->assertJson([
            'total_links'   => 2,
            'active_links'  => 1,
            'expired_links' => 1,
            'total_clicks'  => 8
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function metrics_top_returns_links_ordered_by_clicks()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Link::factory()->create([
            'user_id' => $user->id,
            'original_url' => 'https://google.com',
            'click_count' => 10,
        ]);

        Link::factory()->create([
            'user_id' => $user->id,
            'original_url' => 'https://laravel.com',
            'click_count' => 5,
        ]);

        $response = $this->getJson('/api/metrics/top');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('https://google.com', $data[0]['original_url']);
        $this->assertEquals('https://laravel.com', $data[1]['original_url']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function metrics_by_month_groups_links_and_clicks()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Link::factory()->create([
            'user_id' => $user->id,
            'click_count' => 3,
            'created_at' => now()->subMonths(1),
        ]);

        Link::factory()->create([
            'user_id' => $user->id,
            'click_count' => 2,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/metrics/by-month');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('month', $data[0]);
        $this->assertArrayHasKey('total_links', $data[0]);
        $this->assertArrayHasKey('total_clicks', $data[0]);
    }
}