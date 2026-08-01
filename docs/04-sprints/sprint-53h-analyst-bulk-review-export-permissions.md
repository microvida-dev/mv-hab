# Sprint 53H — Perfil municipal do analista e segregação de funções

## Estado inicial

- Branch: `sprint-53h-analyst-bulk-review-export-permissions`.
- Commit-base: `ef91154c3333ffb78565448671c79862c445609a`.
- Stack observada: PHP 8.4.21, Laravel 13.12.0, Node 24.11.0 e npm 11.6.1.
- Working tree inicial: limpa.

## Auditoria inicial

O backoffice já é permission-first e não possui middleware de role fixa nas rotas do Programa 53. As rotas usam `active.backoffice`, `mfa.backoffice`, `log.backoffice`, permissions exatas, entitlement municipal quando aplicável, Policy/Form Request e scope municipal fail-closed.

O catálogo já contém todas as permissões consumidas. Não há justificação para criar aliases `application_review_batches.*` ou `correction_cycles.*`. O modelo `User` recebe permissões exclusivamente através de `roles`; não existe pivot `permission_user`.

O MFA já considera permissions sensíveis através de `PermissionCatalogService`, preservando também flags manuais. Não existe, porém, rate limiter nomeado para as operações críticas do Programa 53.

## Templates anteriores

- `operador-recolha`: intake e receção documental, sem decisão/exportação.
- `analista-candidaturas`: decisão documental e análise, sem `applications.export`.
- `exportador-candidaturas`: consulta/exportação, sem decisão documental.
- `gestor-visitas`: domínio de visitas, sem candidaturas/documentos/exportação.

As matrizes existentes serão preservadas sem alteração silenciosa.

## Gap de lifecycle

`roles` não guarda origem, versão ou fingerprint do template. A página de templates apenas pré-seleciona checkboxes e a criação genérica perde a proveniência. Para suportar idempotência e drift de forma não ambígua é necessária uma migration mínima com três campos nullable, sem backfill por nome.

O rollback será fail-closed caso existam metadados persistidos. Roles legacy permanecem válidas.

## Manifesto

O manifesto determinístico em `docs/access/manifests/sprint-53h-program-53-access-manifest.json` fixa 43 rotas de revisão progressiva, lotes, publicação, aperfeiçoamentos, segunda análise, exportação temporal, download e auditoria.

No estado inicial, os campos `rate_limiter` estão a `null`; serão reconciliados no Bloco 53H-C para preview/pedido/download de exportação e selagem/publicação.

## Plano de implementação

1. Adicionar o template versionado e metadata mínima em `roles`.
2. Aplicar o template no fluxo administrativo existente, sem UI paralela.
3. Introduzir preview de drift e reconciliação explícita sem remoção silenciosa.
4. Reforçar atribuição contra candidate, auditor incompatível e contas inativas.
5. Adicionar rate limiters configuráveis e sem PII.
6. Criar comando read-only `access:audit-program-53`.
7. Integrar o cenário demo sem ativar features em produção.
8. Cobrir matriz, scope municipal, MFA, entitlements, concorrência e regressão.

## Migrations previstas

- `roles.template_key`, nullable.
- `roles.template_version`, nullable.
- `roles.template_fingerprint`, nullable.
- unicidade municipal para a origem do template, sem associação retroativa.

## Ficheiros previstos

- Registry/service de templates municipais e lifecycle de roles.
- Role model, migration, requests, controller e views existentes de acesso.
- Role assignment e regras de segregação.
- App service provider/configuração de rate limits e rotas existentes.
- Comando e serviço de auditoria do Programa 53.
- Seeder demo e respetiva verificação.
- Testes unitários, feature, segurança, rate limiting e concorrência.
- Documentação e manifesto deste diretório.

## Riscos iniciais

- `roles.name` é globalmente único; a idempotência deve usar Município + template, não label.
- `RoleManagementService::sync()` remove permissões; a reconciliação de template deve ter confirmação explícita e serviço próprio.
- O rate limiter depende do cache configurado; os testes devem controlar o store e a janela.
- A geração real de exports continua dependente do worker `reports`, scheduler e storage no ambiente alvo.

## Estado

Implementação em curso. Classificação final máxima prevista: `REPOSITORY_PASS_DEPLOYMENT_GATED`.
