<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Link;
use Carbon\Carbon;

class LinkController extends Controller
{
    // criar link curto
    public function store(Request $request)
    {
        // validação
        $request->validate([
            'original_url' => 'required|url',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // gerar slug unico 
        $slug = Str::random(6);
        while (Link::where('slug', $slug)->exists()) {
            $slug = Str::random(6);
        }

        // cria link
        $link = Link::create([
            'user_id' => $request->user()->id,
            'original_url' => $request->original_url,
            'slug' => $slug,
            'expires_at' => $request->expires_at ? Carbon::parse($request->expires_at) : null,
            'status' => 'active',
            'click_count' => 0,
        ]);

        // gera qrcode
        $shortUrl = url("/api/s/{$slug}");
        $qrcodeUrl = url("/api/qrcode/{$slug}");

        return response()->json([
            'id' => $link->id,
            'slug' => $link->slug,
            'original_url' => $link->original_url,
            'click_count' => $link->click_count,
            'expires_at' => $link->expires_at,
            'status' => $link->status,
            'created_at' => $link->created_at,
            'updated_at' => $link->updated_at,
            'short_url' => $shortUrl,
            'qrcode_url' => $qrcodeUrl,
        ], 201);
    }

    public function index(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated (debug)'], 401);
        }

        $links = Link::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Link $link) {
                return [
                    'id' => $link->id,
                    'user_id' => $link->user_id,
                    'slug' => $link->slug,
                    'original_url' => $link->original_url,
                    'click_count' => $link->click_count,
                    'expires_at' => $link->expires_at,
                    'status' => $link->status,
                    'created_at' => $link->created_at,
                    'updated_at' => $link->updated_at,
                    'short_url' => url("/api/s/{$link->slug}"),
                    'qrcode_url' => url("/api/qrcode/{$link->slug}"),
                ];
            });

        return response()->json(['data' => $links], 200);
    }

    public function show(Request $request, int $id)
    {
        if(!$request ->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $link = Link::where('id', $id) ->where('user_id', $request->user()->id)->first();

        if(!$link) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $link->id,
            'user_id'=>$link->user_id,
            'slug' => $link->slug,
            'original_url' => $link->original_url,
            'click_count' => $link->click_count,
            'expires_at' => $link->expires_at,
            'status' => $link->status,
            'created_at' => $link->created_at,
            'updated_at' => $link->updated_at,
            'short_url' => url("/api/s/{$link->slug}"),
            'qrcode_url' => url("/api/qrcode/{$link->slug}"),
        ], 200);
    }
}