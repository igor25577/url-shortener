<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Link;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $now = Carbon::now();

        // Totais
        $totalLinks = Link::where('user_id', $userId)->count();
        $totalClicks = (int) Link::where('user_id', $userId)->sum('click_count');

        // Últimos 7 dias: links criados por dia
        $start = Carbon::now()->subDays(6)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $linksPerDayRaw = Link::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->all();

        $linksByDay = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $linksByDay[] = [
                'date' => $day,
                'count' => (int)($linksPerDayRaw[$day] ?? 0),
            ];
        }

        // Últimos 7 dias
        $visitsPerDayRaw = \App\Models\Visit::whereHas('link', function ($q) use ($userId) {
            $q -> where('user_id', $userId);
        })
        ->whereBetween('created_at', [$start, $end])
        ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
        ->groupBy('d')
        ->orderBy('d')
        ->pluck('c', 'd')
        ->all();

        $clicksByDay = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $clicksByDay[] = ['date' => $day, 'count' =>  (int) ($visitsPerDayRaw[$day] ?? 0)];
        }

        // Top 5 links 
        $topLinks = Link::where('user_id', $userId)
            ->orderByDesc('click_count')
            ->limit(5)
            ->get(['id', 'slug', 'original_url', 'click_count']);

        // Contagens por status
        $inactiveLinks = Link::where('user_id', $userId)
            ->where('status', 'inactive')
            ->count();

        $activeLinks = Link::where('user_id', $userId)
            ->where('status', '!=', 'inactive') // somente não-inativos
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', $now);
            })
            ->count();

        $expiredLinks = Link::where('user_id', $userId)
            ->where('status', '!=', 'inactive') // somente não-inativos
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->count();

        return response()->json([
            'totals' => [
                'total_links' => $totalLinks,
                'total_clicks' => $totalClicks,
            ],
            'by_status' => [
                'active' => $activeLinks,
                'expired' => $expiredLinks,
                'inactive' => $inactiveLinks,
            ],
            'last7_days' => [
                'links_by_day' => $linksByDay,
                'clicks_by_day' => $clicksByDay,
            ],
            'top_links' => $topLinks,
        ], 200);
    }
}