<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    //Resumo com totais
    public function summary(Request $request)
    {
        $userId = $request->user()->id;

        $totalLinks    = Link::where('user_id', $userId)->count();
        $totalClicks   = Link::where('user_id', $userId)->sum('click_count');

        $activeLinks = Link::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->count();

        $expiredLinks = Link::where('user_id', $userId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        return response()->json([
            'total_links'   => $totalLinks,
            'total_clicks'  => $totalClicks,
            'active_links'  => $activeLinks,
            'expired_links' => $expiredLinks,
        ]);
    }

    // Os mais acessados
    public function top(Request $request)
    {
        $userId = $request->user()->id;

        $top = Link::where('user_id', $userId)
            ->orderByDesc('click_count')
            ->take(5)
            ->get(['id', 'original_url', 'slug', 'click_count']);

        return response()->json($top);
    }

    // Links criados por mês
    public function byMonth(Request $request)
    {
        $userId = $request->user()->id;

        if (DB::connection()->getDriverName() === 'sqlite') {
            // para testes (SQLite)
            $stats = Link::select(
                DB::raw("strftime('%Y-%m', created_at) as month"),
                DB::raw('COUNT(*) as total_links'),
                DB::raw('SUM(click_count) as total_clicks')
            )
            ->where('user_id', $userId)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        } else {
            // para Postgres/MySQL
            $stats = Link::select(
                DB::raw("DATE_TRUNC('month', created_at) as month"),
                DB::raw('COUNT(*) as total_links'),
                DB::raw('SUM(click_count) as total_clicks')
            )
            ->where('user_id', $userId)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        }

        return response()->json($stats);
    }
}
