<?php

namespace App\Http\Controllers;

use App\Models\Link;
use Carbon\Carbon;

class RedirectController extends Controller
{
    public function bySlug(string $slug)
    {
        $link = Link::where('slug', $slug)->first();

        if (!$link) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!is_null($link->expires_at) && Carbon::parse($link->expires_at)->isPast()) {
            return response()->json(['message' => 'Link expired'], 410);
        }

        $link->increment('click_count');

        return redirect()->away($link->original_url, 302);
    }
}