<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request -> user() -> id;

        $totalLinks = Link::where('user_id', $userId) ->count();
        $activeLinks = Link::where('user_id', $userId) -> where('status', 'active') -> count();
        $expiredLinks = Link::where('user_id', $userId) -> whereNotNull ('expires_at') -> where('expires_at', '<', Carbon::now()) -> count();
        $totalClicks = Link::where('user_id', $userId)-> sum('click_count');

        return response() ->json([
            'total_links' => $totalLinks,
            'active_links' => $activeLinks,
            'expired_links' => $expiredLinks,
            'total_clicks' => $totalClicks,
        ]);
    }
}
