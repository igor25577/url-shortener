<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Link;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;




class LinkController extends Controller
{
    // criar link curto
    public function store(Request $request)
    {
            // validação
        $request ->validate([
            'original_url' => 'required|url',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // gerar slug unico semi-random
        $slug = Str::random(6);
        while (Link::where('slug', $slug)->exists()) {
            $slug = Str::random(6);
        }

        // cria link
        $link = Link::create([
            'user_id' => $request ->user() -> id,
            'original_url' => $request ->original_url,
            'slug' => $slug,
            'expires_at' => $request ->expires_at ? Carbon::parse($request ->expires_at) : null,
            'status' => 'active',
            'click_count' => 0,
        ]);

        // gera qrCode em png ou svg
        $shortUrl = config('app.url') . '/api/s/' . $slug;
        $qrCode = QrCode::size(200)->generate($shortUrl);


        // resposta em formato json
        return response()->json([
            'link' => $link,
            'short_url' => $shortUrl,
            'qr_code' => $qrCode,
        ], 201);

            

    }

    public function index(\Illuminate\Http\Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated (debug)'], 401);
        }

        $user = $request->user();

        $links = \App\Models\Link::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $links], 200);
    }

}
