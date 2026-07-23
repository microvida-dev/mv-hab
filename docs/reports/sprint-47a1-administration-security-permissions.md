# Sprint 47A.1 — Administração, segurança e RGPD permission-first

## Resumo executivo

A Sprint 47A.1 migrou para permission-first as 72 rotas do manifesto de
Administração, utilizadores, equipas, segurança e RGPD. O trabalho removeu
middleware de roles fixas dessas rotas, introduziu isolamento municipal
fail-closed e preservou os fluxos candidate separados.

Classificação: **REPOSITORY_PASS**.

O rollout de operadores de plataforma continua classificado separadamente
como `REPOSITORY_PASS_DEPLOYMENT_GATED`, porque depende de um manifesto
externo aprovado. Nenhum bootstrap externo foi executado nesta sprint.

## Referências Git

- branch: `sprint-47a1-administration-security-permissions`;
- commit-base publicado: `74718eba58c928c6168341e8c645003add8ec185`;
- `39c738ba22a45f00064fe04e83607e5cdeced241` — manifesto imutável;
- `90f18247fb8b48251fc66fc880711744df0a8605` — administração municipal;
- `69e04dda8d48079db83c2217acb24c3e119ad30c` — segurança municipal;
- `642ba4a3d09e9bedf8054b994afd124c6cc725de` — RGPD municipal.

## Manifesto e reconciliação

O manifesto
`docs/access/manifests/sprint-47a-route-manifest.json` foi fixado antes da
implementação:

- 72 rotas previstas;
- 72 rotas reconciliadas;
- zero route names em falta;
- zero rotas do manifesto ainda com middleware fixo;
- zero rotas do manifesto sem permission final;
- nenhuma `FeatureKey` adicionada.

As 12 rotas permission-first de operadores de plataforma e associações de
roles já existentes foram explicitamente excluídas do universo histórico da
47A.1 e estão registadas no próprio manifesto.

## Administração e controlo de acesso

Foi criado `AccessMunicipalScopeService` e aplicado em profundidade a:

- utilizadores;
- roles e associações;
- equipas e membros;
- auditoria de acessos e alterações.

Controlos implementados:

- município derivado do utilizador autenticado;
- recusa fail-closed sem município;
- queries municipais com scope explícito;
- recursos de outro município recusados;
- roles de sistema protegidas;
- último administrador operacional protegido;
- self-promotion impedida;
- reset de password, revogação de sessões, MFA forçado, desativação e
  reativação com abilities próprias;
- alteração recusada sem efeitos laterais;
- auditoria municipal minimizada.

## Segurança

Foi criado `SecurityMunicipalScopeService` e aplicado a:

- alertas e regras;
- checklists;
- revisões de backups;
- revisões de permissões;
- logs de acesso;
- eventos de auditoria;
- acessos a dados sensíveis.

O fluxo MFA passou a usar `security.manage_own_mfa` e só permite gerir o
dispositivo do próprio utilizador. As páginas GET de diagnóstico de campos
cifrados deixaram de produzir mutações. O auditor permanece read-only e os
controlos mutáveis ficam ocultos nas views quando a Policy recusa a ação.

## RGPD e privacidade

Foi criado `PrivacyMunicipalScopeService` com duas regras distintas:

- catálogos globais de finalidades e retenção são visíveis, mas imutáveis;
- pedidos, exportações, anonimizações e execuções são estritamente municipais.

O fluxo candidate foi preservado. O backoffice passou a usar
`StoreBackofficeDataSubjectRequestRequest`, evitando misturar ownership
candidate com administração municipal.

Foram reforçados:

- pedidos de titular: criar, atribuir, aprovar/concluir e rejeitar;
- exportações privadas: gerar e descarregar;
- anonimização: pedir, aprovar e executar;
- políticas e simulações de retenção;
- métricas e prazos do dashboard;
- provider RGPD da timeline;
- Case Workspace RGPD.

O auditor perdeu `privacy.export`, recebeu apenas leitura de anonimização e
continua sem ações mutáveis.

## Modelo de permissions

As ações sensíveis foram separadas semanticamente. Entre as permissions
adicionadas/reforçadas estão:

- `security.view`, `security.update`, `security.resolve`,
  `security.approve`, `security.manage_own_mfa`;
- `permission_reviews.view`, `permission_reviews.create`,
  `permission_reviews.update`, `permission_reviews.complete`,
  `permission_reviews.audit`;
- `privacy.assign`;
- `rgpd.retention.approve`, `rgpd.retention.execute`;
- `rgpd.anonymization.view`.

Não foi usado `update` para resolver alertas, concluir revisões, aprovar
checklists, atribuir pedidos ou executar retenção. O detalhe das 38 lacunas
da 47A.1 está em
`docs/access/permission-decisions/sprint-47a-permission-decisions.md`.

Não existem permissions diretas por utilizador nem novos wildcards. O
`SystemRoleDefinitionRegistry` continua a consumir as definições estruturais
do catálogo existente.

## Policies, Form Requests e Services

As rotas passaram a combinar:

- middleware `permission:<ação>`;
- `active.backoffice`;
- `mfa.backoffice`;
- `log.backoffice`;
- Policy/ability específica quando existe recurso;
- scope municipal em controller e service.

Os Form Requests abrangidos deixaram de depender de autorização
incondicional e usam abilities compatíveis com a operação. Regras de
validação existentes foram preservadas; IDs relacionais usam validação
municipal quando aplicável.

## Base de dados

Foram criadas três migrations reversíveis:

1. `2026_07_23_000037_add_municipal_scope_to_access_management_tables.php`;
2. `2026_07_23_000038_add_municipal_scope_to_security_tables.php`;
3. `2026_07_23_000039_add_municipal_scope_to_privacy_tables.php`.

Os backfills usam relações autoritativas e fallback controlado. Registos de
catálogo realmente globais podem permanecer com `municipality_id = null`;
registos operacionais não usam `null` para inferir poderes de plataforma.

## Inventário antes/depois

| Métrica | Antes do Programa 47 | Depois da 47A.1 | Delta |
| --- | ---: | ---: | ---: |
| Rotas totais | 1 165 | 1 170 | +5 |
| Rotas com role fixa | 926 | 854 | -72 |
| Rotas backoffice com role fixa | 706 | 634 | -72 |
| Rotas candidate com role fixa | 220 | 220 | 0 |
| Rotas com permission middleware | 195 | 272 | +77 |
| Backoffice fixas sem active/MFA/log | 594 | 593 | -1 |

As cinco rotas adicionais pertencem à infraestrutura permission-first de
operadores de plataforma introduzida na 47A.0. A evidência integral está em
`docs/access/progress/sprint-47a-after.json`.

## Testes executados

Validação específica:

- RGPD herdado: 14 testes, 86 asserções, PASS;
- permission/scope RGPD novos: 5 testes, 207 asserções, PASS;
- filtro `Security`: 364 testes, 3 861 asserções, PASS;
- filtro `Rgpd`: 35 testes, 213 asserções, PASS;
- Dashboard/Timeline/privacidade candidate: 143 testes, 755 asserções, PASS.

Gates globais:

- PHPUnit: 1 115 testes, 8 367 asserções, PASS;
- UX: 130 testes, 645 asserções, PASS;
- PHPStan: 0 erros;
- Pint global: PASS;
- Pint de ficheiros alterados: PASS;
- Composer `validate --strict`: PASS;
- `php artisan optimize:clear`: PASS;
- `php artisan route:list --except-vendor`: PASS;
- build Vite: PASS;
- `git diff --check`: PASS.

O gate de integridade reportou 0 violações críticas e 2 avisos em
`InventoryBackofficeRoutesCommandTest`. A inspeção manual confirmou que os
asserts antigos de roles fixas foram substituídos por asserts mais estritos
de permission-first e que o inventário sem findings aceita legitimamente uma
coleção vazia. Não houve enfraquecimento de cobertura.

## Segurança e RGPD

- candidate continua sem acesso ao backoffice;
- município A não consulta nem altera dados do município B;
- auditor permanece apenas leitura;
- conta inativa é recusada;
- MFA é obrigatório nas rotas sensíveis;
- dados privados não são incluídos em feedback de recusa;
- exportações e artefactos RGPD continuam privados;
- recusas não produzem mutações;
- eventos críticos continuam auditados;
- não foi executado bootstrap de operador em ambiente externo.

## Riscos residuais

- permanecem 634 rotas backoffice com role fixa, atribuídas às Sprints 47B a
  47H;
- 593 dessas rotas ainda não têm o trio de guards backoffice;
- o rollout real de operadores continua dependente de manifesto externo;
- catálogos globais exigem disciplina operacional para não serem confundidos
  com registos municipais;
- as migrations devem ser aplicadas antes de publicar esta branch num
  ambiente persistente.

## Decisão

**REPOSITORY_PASS**

As 72 rotas do manifesto 47A.1 estão migradas, os limites municipais foram
testados, as ações críticas têm semântica própria e todos os quality gates
obrigatórios passaram. A próxima branch pode ser criada apenas depois de este
estado ser commitado, publicado e confirmado no remoto.

