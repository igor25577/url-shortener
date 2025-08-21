<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Link;

class LoadTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1 usuário de teste (ou use o seu)
        $user = User::first() ?? User::factory()->create([
            'name' => 'Perf Tester',
            'email' => 'perf@example.com',
            'password' => bcrypt('password'),
        ]);

        // Cria 5.000 links (em chunks)
        $totalLinks = 5000;
        $now = Carbon::now();
        $links = [];

        for ($i = 0; $i < $totalLinks; $i++) {
            $links[] = [
                'user_id'     => $user->id,
                'original_url'=> 'https://example.com/' . Str::random(10),
                'slug'        => Str::lower(Str::random(10)),
                'status'      => 'active',
                'expires_at'  => $now->copy()->addDays(rand(5, 60)),
                'click_count' => 0, // será ajustado depois
                'created_at'  => $now->copy()->subDays(rand(0, 180)),
                'updated_at'  => $now,
            ];
        }

        foreach (array_chunk($links, 1000) as $chunk) {
            DB::table('links')->insert($chunk);
        }

        // Recarrega IDs dos links
        $linkIds = Link::where('user_id', $user->id)->pluck('id')->all();

        // 100.000 visits (média de 20 por link), em chunks
        $targetVisits = 100000;
        $batch = [];
        $batchSize = 5000;

        for ($i = 0; $i < $targetVisits; $i++) {
            $linkId = $linkIds[array_rand($linkIds)];
            // Distribuir no último 1–180 dias
            $dt = $now->copy()->subDays(rand(0, 180))->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

            $batch[] = [
                'link_id'    => $linkId,
                'user_agent' => 'SeederBot/1.0',
                'ip_address' => '127.0.0.' . rand(1, 254),
                'created_at' => $dt,
                'updated_at' => $dt,
            ];

            if (count($batch) === $batchSize) {
                DB::table('visits')->insert($batch);
                $batch = [];
            }
        }
        if ($batch) {
            DB::table('visits')->insert($batch);
        }

        // Atualizar click_count de forma consistente com visits
        $counts = DB::table('visits')
            ->select('link_id', DB::raw('COUNT(*) as c'))
            ->groupBy('link_id')
            ->pluck('c', 'link_id');
        foreach (array_chunk($counts->toArray(), 1000, true) as $chunk) {
            foreach ($chunk as $linkId => $c) {
                DB::table('links')->where('id', $linkId)->update(['click_count' => $c]);
            }
        }

        $this->command->info('LoadTestSeeder: 5k links e 100k visits criados.');
        $this->command->info('Usuário: ' . $user->email . ' / senha: password');
    }
}