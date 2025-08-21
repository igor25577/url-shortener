<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RedirectController extends Controller
{
    public function redirect(Request $request, string $slug)
    {
        $link = Link::where('slug', $slug)->first();

        if (!$link) {
            // Não encontrado
            return response()->json(['message' => 'Not Found'], 404);
        }

        // Se tiver expiração e já expirou, 410 Gone
        if ($link->expires_at && now()->greaterThanOrEqualTo($link->expires_at)) {
            return response()->json(['message' => 'Link expired'], 410);
        }

        // Registra visita e incrementa contador de forma atômica
        DB::transaction(function () use ($request, $link) {
            $ip = $request->header('X-Forwarded-For') ?: $request->ip();
            $ua = $request->userAgent() ?: '';

            Visit::create([
                'link_id' => $link->id,
                'ip'      => $ip,
                'user_agent' => $ua,
                'visited_at' => now(),
            ]);

            $link->increment('click_count');
        });

        // 302 para a URL original
        return redirect()->away($link->original_url, 302);
    }
}