use App\Models\User;
use App\Models\Link;

#[\PHPUnit\Framework\Attributes\Test]
public function dashboard_returns_statistics()
{
    $user = User::factory()->create();
    $this->actingAs($user);

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
        'expires_at'   => now()->subDay(), // expira ontem
        'click_count'  => 1,
    ]);

    // Faz a chamada para o dashboard
    $response = $this->getJson('/api/dashboard');

    $response->assertStatus(200);

    $response->assertJson([
        'total_links'   => 3,
        'active_links'  => 2,
        'expired_links' => 1,
        'total_clicks'  => 6
    ]);
}