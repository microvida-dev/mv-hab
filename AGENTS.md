# AGENTS.md

## Projeto

MV HAB - Plataforma municipal de Habitação / Arrendamento Acessível.

## Pasta correta

Trabalhar apenas nesta pasta:

/Users/brunocorreia/Documents/CRM HAB/MV-HAB

Antes de alterar ficheiros, confirmar sempre:

- pwd
- git status
- git remote -v
- git branch --show-current

Nunca trabalhar noutra pasta antiga ou temporária.

## Stack

- Laravel 13.x
- PHP 8.4
- MySQL/MariaDB
- Blade
- Tailwind CSS
- Vite
- Alpine.js
- PHPUnit
- Pint
- PHPStan

## Regras técnicas

- Preservar funcionalidades existentes.
- Fazer alterações incrementais.
- Controllers devem ser finos.
- Regras de negócio devem ficar em Services.
- Validação HTTP deve usar Form Requests quando aplicável.
- Autorização deve usar Policies/Gates.
- Dados pessoais e documentos são privados por defeito.
- Toda decisão administrativa relevante deve ser auditável.
- Evitar queries N+1.
- Usar paginação em listagens.
- Usar migrations reversíveis.
- Criar ou ajustar testes quando lógica crítica for alterada.

## Segurança e RGPD

Nunca expor nem commitar:

- .env
- passwords
- tokens
- backups SQL
- documentos reais
- dados pessoais reais
- storage/logs
- storage/framework
- vendor
- node_modules

## Comandos de validação

Antes de fechar uma alteração importante, correr quando possível:

```bash
composer validate
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
npm run build

Ambiente local

A aplicação local corre em:

http://127.0.0.1:8001

Comandos habituais:

npm run dev
php artisan serve --host=127.0.0.1 --port=8001
Documentação prioritária

Antes de planear ou implementar sprints, consultar:

docs/README.md
docs/04-sprints/
docs/08-qa/enterprise-quality-gate.md
docs/09-seguranca-rgpd/security-rgpd-guardrails.md
docs/02-arquitetura/domain-boundaries.md
