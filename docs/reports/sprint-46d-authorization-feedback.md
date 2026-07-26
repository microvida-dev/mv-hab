# Sprint 46D - Feedback amigável de autorização

## 1. Objetivo

A Sprint 46D implementou tratamento central, seguro e acessível para recusas de
autorização. A operação continua recusada e o código HTTP continua correto, mas
os fluxos HTML deixam de apresentar páginas técnicas ou mensagens internas.

Identificador: `TECH-ACCESS-UX-001`.

## 2. Branch e base

- Branch: `sprint-46d-authorization-feedback`
- Commit-base: `ae2f68794d90f6d4bd4883a1d4acd9a5dc152ddf`
- Base funcional: Sprint 46C com PHPStan global a zero

Commits de implementação:

- `b275bc78 feat(access): centralizar recusas de autorização seguras`
- `dfc55f89 test(access): cobrir feedback e auditoria de recusas`
- `a0639c81 fix(access): suportar recusas 403 sem sessão autenticada`

## 3. Auditoria inicial

A auditoria confirmou:

- `bootstrap/app.php` apenas forçava JSON para `api/*`;
- não existiam views próprias para erros HTTP;
- não existia request/correlation ID global;
- os middleware de permissão, feature municipal, role, candidato e conta
  inativa usavam `abort(403)`;
- MFA já possuía challenge dedicado e seguro;
- `AuditLogger` e `AuditTrailService` podiam ser reutilizados;
- o projeto não usa Inertia nos fluxos abrangidos;
- `x-flash-message` não apresentava a flash `warning`;
- o layout autenticado pressupõe sempre um utilizador existente.

## 4. Decisões arquiteturais

### 4.1 Modelo tipado

Foi criado `AccessDenialReason` com seis razões internas:

- `MissingPermission`;
- `FeatureUnavailable`;
- `RecordOutOfScope`;
- `MfaRequired`;
- `InactiveAccount`;
- `CandidateBackofficeBoundary`.

Cada razão possui mensagem pública portuguesa, código público genérico
`access_denied` e política explícita de auditoria. A exceção
`AccessDeniedException` estende `AuthorizationException` e transporta apenas
contexto interno minimizado.

Uma `AuthorizationException` genérica continua classificada como falta de
permissão. `RecordOutOfScope` nunca é inferido genericamente: exige classificação
explícita pela camada que conhece o âmbito.

### 4.2 Responder central

`AuthorizationFailureResponder` foi integrado em `bootstrap/app.php` para
respostas HTTP 403:

- JSON/API: 403 JSON sem redirect, com `message`, `code` e `request_id`;
- GET/HEAD HTML: página integrada 403;
- mutação HTML com `Referer` same-origin e diferente da rota atual: redirect
  303 e flash warning;
- mutação direta, sem origem válida, com origem externa ou com loop: página
  integrada 403;
- 401, 404, 419 e 422 não são convertidos;
- mensagens originais de exceptions, permission names, roles, policies e
  feature keys não são apresentadas.

Esta distinção preserva o contrato histórico de segurança para chamadas diretas
e melhora os formulários Blade iniciados numa página válida.

### 4.3 Destino seguro

`AuthorizedLandingPageResolver` seleciona, por ordem aplicável:

- Área do Candidato;
- Painel Principal quando existe permissão efetiva;
- gestão da conta;
- Portal Público.

Só aceita rotas GET existentes e rejeita o caminho atual. Não usa URL fornecido
pelo cliente nem executa policies para reinterpretar a recusa.

### 4.4 Request ID

`RequestCorrelationId`:

- gera UUID server-side;
- ignora IDs enviados pelo cliente;
- guarda o valor no atributo `request_id`;
- adiciona `X-Request-ID` às respostas normais e de exception;
- não persiste PII.

### 4.5 Middleware

Foram tipados apenas bloqueios inequívocos:

- `RequirePermission`;
- `RequireSensitivePermission`;
- `EnsureMunicipalityFeatureIsEnabled`;
- `EnsureUserHasRole`;
- `BlockInactiveBackofficeUsers`.

Mantiveram-se:

- feature desconhecida como 404;
- feature desativada e ausência de Município em fail-closed 403;
- candidato fora do backoffice;
- conta inativa bloqueada;
- challenge MFA existente, sem conversão para mensagem genérica.

Não foram alteradas Policies, regras de negócio, controllers ou rotas.

### 4.6 Auditoria

`AuthorizationDenialAuditService` regista apenas recusas relevantes:

- mutações recusadas;
- candidato no backoffice;
- acesso explicitamente fora do Município;
- conta inativa.

A metadata está limitada a:

- actor e Município;
- nome da rota;
- método HTTP;
- razão interna;
- request ID;
- timestamp.

A deduplicação usa uma chave SHA-256 em cache com janela de 60 segundos. Não são
registados payloads, uploads, cookies, tokens ou conteúdo documental. Falhas de
cache/auditoria nunca transformam a recusa num erro 500.

## 5. UI e acessibilidade

Foi criada uma página 403 integrada e adaptativa:

- sessão autenticada: layout operacional;
- visitante: layout público, sem navegação que pressuponha utilizador.

O conteúdo inclui:

- heading explícito;
- mensagem segura;
- referência de suporte baseada no request ID;
- ação para destino seguro;
- `role="alert"`;
- `aria-live="assertive"`;
- `aria-atomic="true"`.

`x-mv.alert` passou a fornecer semântica live adequada por tom e
`x-flash-message` passou a apresentar warnings de autorização. Não foi criado
um segundo sistema de alertas.

## 6. Ficheiros alterados

Produção:

- `app/Enums/AccessDenialReason.php`
- `app/Exceptions/AccessDeniedException.php`
- `app/Http/Middleware/RequestCorrelationId.php`
- `app/Http/Middleware/BlockInactiveBackofficeUsers.php`
- `app/Http/Middleware/EnsureMunicipalityFeatureIsEnabled.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `app/Http/Middleware/RequirePermission.php`
- `app/Http/Middleware/RequireSensitivePermission.php`
- `app/Services/Security/AuthorizationDenialAuditService.php`
- `app/Services/Security/AuthorizationFailureResponder.php`
- `app/Services/Security/AuthorizedLandingPageResolver.php`
- `bootstrap/app.php`
- `resources/views/components/flash-message.blade.php`
- `resources/views/components/mv/alert.blade.php`
- `resources/views/errors/403.blade.php`
- `resources/views/errors/partials/403-content.blade.php`

Testes novos:

- `tests/Unit/Security/AccessDenialReasonTest.php`
- `tests/Unit/Security/AuthorizationFailureResponderTest.php`
- `tests/Feature/Security/AuthorizationFeedbackHtmlTest.php`
- `tests/Feature/Security/AuthorizationFeedbackJsonTest.php`
- `tests/Feature/Security/AuthorizationFeedbackDirectUrlTest.php`
- `tests/Feature/Security/AuthorizationFeedbackAccessibilityTest.php`
- `tests/Feature/Security/AuthorizationDenialAuditTest.php`

Testes existentes alterados: nenhum.

Migrations criadas: nenhuma.

Rotas alteradas/criadas: nenhuma.

## 7. Cobertura funcional

A cobertura nova valida:

- mensagens portuguesas e ausência de nomes técnicos;
- JSON 403 sem redirect;
- consistência do request ID entre payload/header/log;
- rejeição de request ID controlado pelo cliente;
- GET HTML integrado com 403;
- redirect 303 apenas com origem same-origin segura;
- bloqueio de origem externa e loops;
- ausência de mutação e de persistência de uploads;
- sessão preservada e ausência de `withInput()` global;
- fallback de `AuthorizationException`;
- feature municipal indisponível;
- scope municipal explicitamente classificado;
- 404 oculto preservado;
- candidato fora do backoffice;
- auditor read-only;
- conta inativa;
- MFA com challenge existente;
- página 403 autenticada e anónima;
- alerta acessível;
- auditoria minimizada, deduplicada e fail-safe.

## 8. Testes e gates

| Gate | Resultado |
|---|---|
| Testes novos de autorização | PASS - 22 testes, 134 asserções |
| Segurança + Entitlements + Auth | PASS - 346 testes, 3.055 asserções |
| PHPUnit completo | PASS - 1.070 testes, 7.529 asserções |
| UX | PASS - 130 testes, 645 asserções |
| Integridade dos testes | PASS - 7 ficheiros, 0 violações, 0 avisos |
| Pint incremental | PASS - 23 ficheiros |
| Pint global | PASS |
| PHPStan global | PASS - 0 erros |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `npm run build` | PASS |
| `php artisan route:list --except-vendor` | PASS - 1.162 rotas |
| `git diff --check` | PASS |

O primeiro ensaio da suite completa identificou três falhas ligadas ao acesso
anónimo a storage privado: a view 403 autenticada tentava carregar navegação sem
utilizador. O erro foi corrigido com layout público adaptativo e os três testes
passaram antes da repetição integral da suite.

## 9. Auditoria de rotas

`php artisan access:audit-routes --format=json`:

- rotas totais: 1.165;
- rotas sem vendor: 1.162;
- rotas com role fixa: 926;
- backoffice com role fixa: 706;
- candidato com role fixa: 220;
- rotas com middleware de permissão: 195;
- sem `active.backoffice`: 594;
- sem `mfa.backoffice`: 594;
- sem `log.backoffice`: 594.

Os valores permanecem iguais aos da Sprint 46C. A Sprint 46D não altera a
superfície de rotas.

## 10. Segurança e RGPD

- nenhuma autorização foi convertida em sucesso;
- nenhuma policy ou permission foi enfraquecida;
- nenhuma mutação ocorre depois da recusa;
- 404 de recursos ocultos permanece 404;
- candidato continua sem acesso ao backoffice;
- auditor continua read-only;
- MFA não é contornado;
- JSON não recebe HTML nem redirect;
- logs não guardam payloads, uploads ou conteúdo documental;
- storage privado continua inacessível publicamente.

## 11. Riscos residuais e backlog

- O fallback de uma `AuthorizationException` genérica é
  `MissingPermission`; camadas que conheçam inequivocamente o âmbito municipal
  devem optar explicitamente por `RecordOutOfScope`.
- Pedidos `fetch` devem enviar `Accept: application/json` ou
  `X-Requested-With: XMLHttpRequest` para receber JSON. Sem indicação de
  conteúdo, o comportamento seguro é HTML 403.
- A deduplicação depende da cache disponível. Se a cache falhar, a recusa
  mantém-se correta e a auditoria tenta registar o evento sem deduplicação.
- A prevenção visual continua dependente dos serviços backend existentes; não
  foi introduzida autorização em JavaScript.

## 12. Classificação final

**PASS**

Todos os gates obrigatórios passaram, não existem alterações a regras de
negócio, rotas, Policies ou base de dados, e a branch não introduz dívida
estática ou regressões conhecidas.
