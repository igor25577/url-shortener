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

<<<<<<< HEAD
        $totalLinks = \App\Models\Link::where('user_id', $userId)->count();

        $activeLinks = \App\Models\Link::where('user_id', $userId)
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', $now);
            })
            ->count();

        $expiredLinks = \App\Models\Link::where('user_id', $userId)
=======
        $totalLinks = Link::where('user_id', $userId)->count();

        $activeLinks = Link::where('user_id', $userId)
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', $now);
            })
            ->count();

        $expiredLinks = Link::where('user_id', $userId)
>>>>>>> main
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->count();

<<<<<<< HEAD
        $totalClicks = \App\Models\Link::where('user_id', $userId)->sum('click_count');
=======
        $totalClicks = Link::where('user_id', $userId)->sum('click_count');
>>>>>>> main

        return response()->json([
            'total_links'   => $totalLinks,
            'active_links'  => $activeLinks,
            'expired_links' => $expiredLinks,
            'total_clicks'  => $totalClicks,
        ]);
    }
}
