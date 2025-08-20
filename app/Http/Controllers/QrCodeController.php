<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function showById(Request $request, Link $link)
    {
        if (!$request->user() || $link->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($this->isExpired($link)) {
            return response()->json(['message' => 'Link expired'], 410);
        }

        $shortUrl = url("/api/s/{$link->slug}");
        $png = $this->makeQrPng($shortUrl, 300);

        return response($png, 200)->header('Content-Type', 'image/png');
    }

    public function showBySlug(string $slug)
    {
        $link = Link::where('slug', $slug)->first();
        if (!$link) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($this->isExpired($link)) {
            return response()->json(['message' => 'Link expired'], 410);
        }

        $shortUrl = url("/api/s/{$link->slug}");
        $png = $this->makeQrPng($shortUrl, 300);

        return response($png, 200)->header('Content-Type', 'image/png');
    }

    private function isExpired(Link $link): bool
    {
        return $link->expires_at && now()->greaterThan($link->expires_at);
    }

    private function makeQrPng(string $text, int $size = 300): string
    {
        return QrCode::format('png')
            ->size($size)
            ->margin(1)
            ->generate($text);
    }
}