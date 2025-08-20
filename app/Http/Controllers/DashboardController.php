<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $now = now();

        // totais por usuário
        $totalLinks = Link::where('user_id', $userId)->count();

        // ativo = não expirado
        $activeLinks = Link::where('user_id', $userId)
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', $now);
            })
            ->count();

        // expirado = expires_at passado
        $expiredLinks = Link::where('user_id', $userId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->count();

        // soma dos cliques
        $totalClicks = Link::where('user_id', $userId)->sum('click_count');

        return response()->json([
            'total_links'   => $totalLinks,
            'active_links'  => $activeLinks,
            'expired_links' => $expiredLinks,
            'total_clicks'  => $totalClicks,
        ], 200);
    }
}