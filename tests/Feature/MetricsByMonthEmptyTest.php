<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;

class MetricsByMonthEmptyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_by_month_returns_6_months_with_zeros_when_no_data(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->getJson('/api/metrics/by-month', [
            'Authorization' => 'Bearer '.$token,
            'Accept'        => 'application/json',
        ])->assertStatus(200)
          ->assertJsonStructure([
              'months' => [['month', 'links', 'clicks']]
          ]);

        $months = $res->json('months');
        $this->assertCount(6, $months);

        foreach ($months as $row) {
            $this->assertIsString($row['month']);
            $this->assertIsInt($row['links']);
            $this->assertIsInt($row['clicks']);
            $this->assertSame(0, $row['links']);
            $this->assertSame(0, $row['clicks']);
        }
    }
}