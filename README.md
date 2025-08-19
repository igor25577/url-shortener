# URL Shortener – Expiração, QR Code, Métricas em Tempo Real e TDD

Aplicação web para encurtar URLs com expiração opcional, QR Code, redirecionamento com contagem de cliques, dashboard de métricas e desenvolvimento orientado a testes (TDD). Construída com Laravel, autenticação via Sanctum, PostgreSQL (dev/prod) e SQLite in-memory nos testes.

## Visão Geral

A aplicação permite:
- Registro, login e logout de usuários.
- Criação de links encurtados (slug único), com expiração opcional e QR Code.
- Redirecionamento público por slug, incrementando cliques e respeitando expiração.
- Dashboard protegido (MVC) com métricas: totais, ativos/expirados, top por cliques e evolução por mês.
- Fluxo de versionamento com branches por feature, PRs descritivos e commits atômicos.

## Stack

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

1. Clonar e instalar dependências:
