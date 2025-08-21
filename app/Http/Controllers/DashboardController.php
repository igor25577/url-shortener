<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $ttl = 900; 
        $cacheKey = "dashboard:v1:user:{$userId}";

        $data = Cache::remember($cacheKey, $ttl, function () use ($userId) {
            $now   = now();
            $start = (clone $now)->subDays(6)->startOfDay();
            $end   = (clone $now)->endOfDay();

            $driver = DB::getDriverName(); 

            if ($driver === 'pgsql') {
                $dayExprLinks  = "date_trunc('day', created_at)";
                $dayExprVisits = "date_trunc('day', visits.created_at)";
            } elseif ($driver === 'sqlite') {
                $dayExprLinks  = "date(created_at)";
                $dayExprVisits = "date(visits.created_at)";
            } else {
                $dayExprLinks  = "date(created_at)";
                $dayExprVisits = "date(visits.created_at)";
            }

            $totalLinks = DB::table('links')
                ->where('user_id', $userId)
                ->count();

            $totalClicks = (int) DB::table('links')
                ->where('user_id', $userId)
                ->sum('click_count');

            $linksPerDayRaw = DB::table('links')
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("$dayExprLinks as d, COUNT(*) as c")
                ->groupBy('d')
                ->orderBy('d')
                ->get();

            $linksPerDayMap = [];
            foreach ($linksPerDayRaw as $row) {
                $dateString = is_string($row->d)
                    ? Carbon::parse($row->d)->toDateString()
                    : Carbon::parse($row->d)->toDateString();

                $linksPerDayMap[$dateString] = (int) $row->c;
            }

            $linksByDay = [];
            for ($i = 0; $i < 7; $i++) {
                $day = (clone $start)->addDays($i)->toDateString();
                $linksByDay[] = [
                    'date'  => $day,
                    'count' => $linksPerDayMap[$day] ?? 0,
                ];
            }

            $visitsPerDayRaw = DB::table('visits')
                ->join('links', 'visits.link_id', '=', 'links.id')
                ->where('links.user_id', $userId)
                ->whereBetween('visits.created_at', [$start, $end])
                ->selectRaw("$dayExprVisits as d, COUNT(*) as c")
                ->groupBy('d')
                ->orderBy('d')
                ->get();

            $visitsPerDayMap = [];
            foreach ($visitsPerDayRaw as $row) {
                $dateString = is_string($row->d)
                    ? Carbon::parse($row->d)->toDateString()
                    : Carbon::parse($row->d)->toDateString();

                $visitsPerDayMap[$dateString] = (int) $row->c;
            }

            $clicksByDay = [];
            for ($i = 0; $i < 7; $i++) {
                $day = (clone $start)->addDays($i)->toDateString();
                $clicksByDay[] = [
                    'date'  => $day,
                    'count' => $visitsPerDayMap[$day] ?? 0,
                ];
            }

            $topLinks = DB::table('links')
                ->where('user_id', $userId)
                ->orderByDesc('click_count')
                ->limit(5)
                ->get(['id', 'slug', 'original_url', 'click_count']);

            $statusAgg = DB::table('links')
                ->selectRaw("
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN status != 'inactive' AND (expires_at IS NULL OR expires_at > ?) THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status != 'inactive' AND expires_at IS NOT NULL AND expires_at <= ? THEN 1 ELSE 0 END) as expired
                ", [$now, $now])
                ->where('user_id', $userId)
                ->first();

            $payload = [
                'totals' => [
                    'total_links'  => (int) $totalLinks,
                    'total_clicks' => (int) $totalClicks,
                ],
                'by_status' => [
                    'active'   => (int) ($statusAgg->active ?? 0),
                    'expired'  => (int) ($statusAgg->expired ?? 0),
                    'inactive' => (int) ($statusAgg->inactive ?? 0),
                ],
                'last7_days' => [
                    'links_by_day'  => $linksByDay,
                    'clicks_by_day' => $clicksByDay,
                ],
                'top_links' => $topLinks,
            ];

            $payload['total_links']   = $payload['totals']['total_links'];
            $payload['total_clicks']  = $payload['totals']['total_clicks'];
            $payload['active_links']  = $payload['by_status']['active'];
            $payload['expired_links'] = $payload['by_status']['expired'];

            return $payload;
        });

        return response()->json($data, 200);
    }
}