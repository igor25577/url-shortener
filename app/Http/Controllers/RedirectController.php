<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use Carbon\Carbon;

class RedirectController extends Controller
{
    public function handle($slug) 
    {
        // buscar link pelo slug
        $link = Link::where('slug', $slug) -> first();

        // caso não exista
        if (!$link) {
            return response() -> json([
                'error' => 'Link Não Encontrado'
            ], 404);
        }

        // verificar se ta expirado
        if (!is_null($link ->expires_at) && Carbon::parse($link->expires_at) ->isPast()){
            return response() -> json([
                'error' => ' LINK EXPIRADO'
            ], 410);
        }

        // incrementar contador de cliques
        $link -> increment ('click_count');

        // redireciona para url original
        return redirect() -> away($link -> original_url);
    }
}
