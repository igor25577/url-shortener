<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Link;
use App\Models\Visit;
use Carbon\Carbon;

class RedirectController extends Controller
{
    public function bySlug(Request $request, string $slug)
    {
        $link = Link::where('slug', $slug)->first();

        if (!$link) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!is_null($link->expires_at) && Carbon::parse($link->expires_at)->isPast()) {
            return response()->json(['message' => 'Link expired'], 410);
        }

        $link->increment('click_count');

        try {
            Visit::create([
                'link_id'    => $link->id,
                'user_agent' => $request->header('User-Agent'), 
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('visit create failed', ['error' => $e->getMessage()]);
        }

        return redirect()->away($link->original_url, 302);
    }
}