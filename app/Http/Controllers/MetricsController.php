<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Link;

class MetricsController extends Controller
{
    public function summary(Request $request)
    {
        $userId = $request->user()->id;
        $now = Carbon::now();

        $totalLinks = (int) Link::where('user_id', $userId)->count();
        $totalClicks = (int) Link::where('user_id', $userId)->sum('click_count');

        // Categorias mutuamente exclusivas
        $inactive = (int) Link::where('user_id', $userId)
            ->where('status', 'inactive')
            ->count();

        $active = (int) Link::where('user_id', $userId)
            ->where('status', '!=', 'inactive')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->count();

        $expired = (int) Link::where('user_id', $userId)
            ->where('status', '!=', 'inactive')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->count();

        return response()->json([
            'totals' => [
                'total_links' => $totalLinks,
                'total_clicks' => $totalClicks,
            ],
            'by_status' => [
                'active' => $active,
                'expired' => $expired,
                'inactive' => $inactive,
            ],
        ], 200);
    }

    public function top(Request $request)
    {
        $userId = $request->user()->id;

        $top = Link::where('user_id', $userId)
            ->orderByDesc('click_count')
            ->limit(5)
            ->get(['id', 'slug', 'original_url', 'click_count']);

        return response()->json(['top_links' => $top], 200);
    }

    public function byMonth(Request $request)
    {
        $userId = $request->user()->id;

        // Últimos 6 meses 
        $start = Carbon::now()->subMonths(5)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        // Formatação por driver
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            $formatExpr = "TO_CHAR(created_at, 'YYYY-MM')";
        } elseif ($driver === 'sqlite') {
            $formatExpr = "strftime('%Y-%m', created_at)";
        } else {
            
            $formatExpr = "DATE_FORMAT(created_at, '%Y-%m')";
        }

        // Links criados por mês no período
        $linksPerMonthRaw = Link::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw("$formatExpr as ym"), DB::raw('COUNT(*) as c'))
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('c', 'ym')
            ->all();

        // Visits por mês (clicks reais) no período, considerando apenas links do usuário
        $visitsPerMonthRaw = \App\Models\Visit::whereHas('link', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereBetween('created_at', [$start, $end])
        ->select(\DB::raw("$formatExpr as ym"), \DB::raw('COUNT(*) as c'))
        ->groupBy('ym')
        ->orderBy('ym')
        ->pluck('c', 'ym')
        ->all();

        // Monta 6 meses contínuos
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $start->copy()->addMonths($i)->format('Y-m');
            $months[] = [
                'month' => $m,
                'links' => (int) ($linksPerMonthRaw[$m] ?? 0),
                'clicks' => (int) ($visitsPerMonthRaw[$m] ?? 0), 
            ];
        }

        return response()->json(['months' => $months], 200);
    }
}