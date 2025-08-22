# URL Shortener – Expiração, QR Code, Métricas em Tempo Real e TDD

Aplicação web para encurtar URLs com expiração opcional, QR Code, redirecionamento com contagem de cliques, dashboard de métricas e desenvolvimento orientado a testes (TDD). Construída com Laravel, autenticação via Sanctum, PostgreSQL (dev/prod) e SQLite in-memory nos testes.

## Visão Geral

A aplicação permite:
- Registro, login e logout de usuários.
- Criação de links encurtados (slug único), com expiração opcional e QR Code.
- Redirecionamento público por slug, incrementando cliques e respeitando expiração.
- Dashboard protegido (MVC) com métricas: totais, ativos/expirados, top por cliques e evolução por mês.
- Fluxo de versionamento com branches por feature, PRs descritivos e commits atômicos.

## Stackk

- Backend: Laravel (PHP 8.x)
- Autenticação: Laravel Sanctum
- Banco de dados: PostgreSQL (desenvolvimento/produção)
- Testes: PHPUnit (SQLite in-memory)
- Front (mínimo): Blade/Routes MVC para o dashboard (opcional)
- QR Code: SimpleSoftwareIO/QRCode
- Ambiente local sugerido: Laragon (Windows) ou Valet/Herd (macOS)

## Requisitos

- PHP 8.1+
- Composer
- PostgreSQL
- Extensões PHP comuns do Laravel
- Node.js (opcional para front)

## Setup Rápido

1 - Clonar e instalar dependências
    git clone https://github.com/igor25577/url-shortener.git
    cd url-shortener
    composer install
2 - Variáveis de ambiente
    cp .env.example .env
    php artisan key:generate
    Ajuste as variáveis DB_* no .env conforme seu ambiente (por padrão, PostgreSQL local).
3 - Migrations
    php artisan migrate
4 - Servir a aplicação
    php artisan serve
    Acesse: http://localhost:8000


## Enddppoints Principais

Autenticação:
- POST /api/auth/register — registra um usuário (name, email, password)
- POST /api/auth/login — autentica e emite token (Sanctum)
- POST /api/auth/logout — encerra sessão (requer auth)

Links (protegidos — requer auth):
- POST /api/links — cria link curto
- Campos: original_url (obrigatório), expires_at (opcional, > now)
- Response inclui: link.slug, short_url, qr_code (data URI)
- GET /api/links — lista links do usuário autenticado
- GET /api/links/{id} — detalhes do link do usuário (ACL aplicada)

Redirecionamento (público):
- GET /api/s/{slug} — redireciona para original_url se ativo e não expirado
- Incrementa click_count
- Se expirado, retorna 410 (Gone)

Dashboard e Métricas (protegidos — requer auth):
- GET /api/dashboard — página MVC (opcional)
- GET /api/metrics/summary — totais, ativos, expirados, cliques
- GET /api/metrics/top — top links por cliques
- GET /api/metrics/by-month — agregação mensal

Observação: caminhos variam conforme sua configuração de rotas. No projeto atual, o redirecionamento está sob /api/s/{slug}.

## Modelo de Dados (resumo)

users
- id, name, email (unique), password, timestamps.

links
- id, user_id (FK), original_url, slug (unique), status (active|expired|inactive), expires_at (nullable), click_count (int, default 0), timestamps.

visits (opcional, se implementado)
- id, link_id (FK), ip_hash (ou IP truncado), user_agent, created_at.

## Regras Importantes

- Validação de criação de link:
- original_url: obrigatória e válida (http/https)
- expires_at: opcional; quando fornecida, deve ser posterior a now()
- Redirecionamento:
- Se expirado: retorna 410 (Gone) e não incrementa cliques
- Se ativo: incrementa click_count e redireciona (302) para original_url
- Segurança:
- Rotas privadas protegidas por Sanctum
- ACL: usuário só acessa os próprios links nos endpoints protegidos

## Testess

Os testes de feature cobrem:
- Auth: registro, login e logout; proteção de rotas privadas
- Links: criação (validações), listagem por usuário, ACL no show, redirect com incremento, expiração (410)
- Métricas: summary, top e by-month (compatível com SQLite nos testes)

Para rodar:
php artisan test


Notas:
- Os testes usam SQLite em memória (ajustado na suíte).
- Para cobertura HTML (opcional, requer Xdebug):
./vendor/bin/phpunit --coverage-html coverage


## Métricas em “Tempo Real”

- Implementação recomendada via polling a cada 3–5s no dashboard protegido.
- Alternativas: SSE/WebSockets (opcional, fora do escopo mínimo).
- Índices recomendados no banco:
- links.slug (unique)
- links.status, links.expires_at
- visits.link_id, visits.created_at (se tabela for utilizada)

## Boas Práticas de Versionamento (GitHub)

- Branch principal: main (ou master)
- Branches por feature: feat/auth, feat/shortener, feat/redirect, feat/dashboard
- Pull Requests com:
- Descrição objetiva do que foi feito
- Evidências (logs de testes, prints/GIFs)
- Commits atômicos e mensagens claras (Conventional Commits)

Exemplos de mensagens:
- feat(auth): register/login with Sanctum
- test(links): redirect increment and expiration (410)
- fix(metrics): correct active/expired counts based on expires_at

## Observabilidade e Segurança

- Logs de validação/exceções via stack padrão do Laravel
- Rate limiting (opcional) na criação de links (ex.: 30/min por usuário)
- Não exponha IP completo em telas públicas; anonimização recomendada em visits

## Notas de Implementação

- QR Code gerado a partir da short_url (ex.: /api/s/{slug})
- RedirectController retorna 410 para links expirados; incrementa click_count antes de redirecionar
- Resposta de criação inclui `link.slug` (utilizado pelos testes)
- 

### Ratee limitingg

A criação de links é limitada a 30 requisições por minuto por usuário.

- Rota: POST /api/links
- Middleware: throttle:30,1 aplicado apenas nesta rota
- Teste automático: tests/Feature/RateLimitLinksTest.php garante 429 ao exceder o limite

## Desenvolvimento

Scripts úteis:
- Limpar cache/config (se necessário):
php artisan optimize:clear

- Rodar migrations:
php artisan migrate

- Servir aplicação:
php artisan serve


## Troubleshooting
# Erro: “Please provide a valid cache path.”

    Causa: ausência/permissão das pastas de cache do Laravel.
    Solução:
    Garanta que existam:
    storage/framework/cache
    storage/framework/sessions
    storage/framework/views
    storage/logs
    bootstrap/cache
    Rode:
    php artisan optimize:clear
    php artisan key:generate (se necessário)
    php artisan migrate
    Verifique permissões de escrita em storage/ e bootstrap/cache.


## Licença

Este projeto é disponibilizado para avaliação técnica. Ajuste a licença conforme sua necessidade.
