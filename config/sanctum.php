<?php

use Laravel\Sanctum\Sanctum;

return [

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    // Deixe vazio para não usar guard 'web' (evita sessão/cookies em API de token)
    'guard' => [],

    // Tokens pessoais sem expiração (padrão; ajuste se quiser)
    'expiration' => null,

    // Prefixo opcional de token (não necessário)
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    // Sem middleware extra (não injete sessão aqui)
    'middleware' => [
        // vazio
    ],
];