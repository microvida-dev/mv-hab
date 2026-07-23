# Sprint 47B — Candidaturas, documentos e processos permission-first

## Resumo executivo

A Sprint 47B migrou as 102 rotas do manifesto de candidaturas, intake,
agregados, simulador interno, documentos, IA documental, pedidos de
aperfeiçoamento e processos administrativos para autorização permission-first.

As rotas combinam agora:

```text
auth
&& active.backoffice
&& mfa.backoffice
&& log.backoffice
&& permission:<ação>
&& municipality.feature:<feature>, quando aplicável
&& Policy com scope do registo
```

Classificação: **PASS**.

## Referências Git

- branch: `sprint-47b-applications-documents-permissions`;
- commit-base publicado:
  `639f9d426f571ad4fba58e2ca85dc00559b59a95`;
- `a0550899` — manifesto imutável da 47B;
- `f96282d4` — intake e simulador com scope municipal;
- `2ba6f943` — processos administrativos permission-first;
- `6d46ace7` — IA documental com isolamento municipal;
- `a6c1f224` — documentos gerados com isolamento municipal;
- `c3dbb3e5` — middleware exato nas 102 rotas;
- `e84ed78d` — testes de rotas e limites municipais;
- `abf786d5` — baseline do comando de auditoria;
- `ed307be1` — normalização Pint do teste municipal;
- `0382959d` — correção do domínio financeiro e sensibilidade MFA.

## Manifesto e reconciliação

O ficheiro
`docs/access/manifests/sprint-47b-route-manifest.json` foi criado antes da
implementação e mantido como evidência imutável:

- 102 route names únicos;
- 102 rotas existentes na Route Collection;
- zero rotas candidate;
- 102 rotas sem middleware `role:*` ativo;
- 102 rotas com `auth`, `active.backoffice`, `mfa.backoffice` e
  `log.backoffice`;
- 102 rotas com uma permission exata;
- 43 permissions efetivamente usadas;
- 80 rotas com entitlement candidatural;
- 22 rotas sem entitlement candidatural.

O manifesto propunha 85 entitlements. Cinco rotas de atualização documental
anual foram corrigidas para o domínio financeiro, sem
`applications.review`. A decisão está documentada em
`docs/access/permission-decisions/sprint-47b-permission-decisions.md`.

## Permissions

Foram distinguidas semanticamente, entre outras:

- candidaturas: `view`, `create`, `update`, `delete`, `submit`, `withdraw`;
- documentos: `view`, `create`, `update`, `replace`, `delete`, `download`,
  `analyze`, `review_ai`, `generate`, `activate`, `archive`, `preview`,
  `issue`, `cancel`, `approve`, `reject`, `audit`;
- processos administrativos: `view`, `create`, `update`, `delete`, `assign`,
  `decide`, `complete`, `cancel`, `issue`, `mark_overdue`, `audit`;
- atualizações anuais: `finance.view`, `finance.create`, `finance.approve` e
  `finance.reject`;
- cidadãos, agregados e simulador: permissions CRUD/leitura já existentes,
  aplicadas de forma exata.

O catálogo e os modelos municipais `operador-recolha` e
`analista-candidaturas` foram atualizados sem atribuições diretas a
utilizadores e sem novos wildcards. O `financial_manager` não recebeu
`documents.view`, evitando acesso ao repositório documental geral.

## FeatureKeys

Foram reutilizadas exclusivamente:

- `applications.intake`;
- `applications.review`.

Não foi criada qualquer `FeatureKey`. Intake é aplicado à recolha inicial;
review é aplicado à análise candidatural/documental. Configuração de templates,
simulador e atualizações financeiras anuais não recebem um entitlement
candidatural artificial.

## Isolamento municipal

Foi criado/reforçado `MunicipalRecordScopeService` para resolver, filtrar e
validar o Município de:

- cidadãos, agregados, candidaturas e documentos;
- sessões/configuração do simulador;
- processos, notas, tarefas, pedidos e respostas de aperfeiçoamento;
- análises, validações, sugestões e campos de IA documental;
- templates e versões documentais;
- documentos oficiais, processuais e contratuais;
- pedidos documentais anuais e respetivas contas financeiras.

O scope é fail-closed:

- `municipality_id = null` não concede poderes de plataforma;
- Município A não consulta nem altera registos do Município B;
- scope de plataforma exige assignment estrutural;
- queries de listagem são filtradas antes da paginação;
- validações relacionais recusam IDs de outro Município;
- downloads e artefactos continuam em storage privado.

## Base de dados

Foi criada a migration reversível:

`2026_07_23_000040_add_municipal_scope_to_application_intake_tables.php`

Adiciona `municipality_id`, com foreign key e `nullOnDelete`, a:

- `citizens`;
- `households`;
- `housing_applications`;
- `documents`;
- `simulation_sessions`.

Não é inferido um Município para registos históricos sem relação autoritativa.
Esses registos ficam fail-closed até regularização operacional. A migration e
as migrations 47A pendentes foram aplicadas com sucesso na base local durante
o fecho.

## Policies

Foram criadas ou reforçadas abilities backoffice específicas em 27 Policies,
incluindo:

- `HousingApplicationPolicy`, `CitizenPolicy`, `HouseholdPolicy` e
  `DocumentPolicy`;
- `AdministrativeProcessPolicy`, `AdministrativeProcessNotePolicy`,
  `AdministrativeTaskPolicy`, `CorrectionRequestPolicy` e
  `CorrectionResponsePolicy`;
- Policies de análise, validação, campos e sugestões de IA documental;
- Policies de templates, versões, documentos oficiais, processuais e
  contratuais;
- `AnnualDocumentUpdateRequestPolicy`;
- Policies do simulador e de reutilização de dados.

Foi criada `createVersionBackoffice` para versões de templates. A criação usa
`documents.create`, em vez de reutilizar indevidamente `updateBackoffice`.

## Form Requests

Foram revistos 35 Form Requests abrangidos. Nenhum mantém autorização
incondicional:

- usam a mesma ability do controller;
- validam o modelo associado à rota;
- não aceitam Município manipulável;
- preservam as regras de validação anteriores;
- recusam candidato/auditor nas mutações;
- validam relações municipais antes do service.

## Controllers e Services

Os controllers continuam finos:

- autorizam com Gate/Policy;
- obtêm o utilizador autenticado;
- delegam mutações nos Services;
- usam queries municipais antes de eager loading/paginação.

Foram reforçados os Services de intake, agregado, tarefas administrativas,
aperfeiçoamento, simulador, templates, geração documental, IA documental e
documentos processuais. Não foram alteradas regras de elegibilidade,
classificação, contratos ou decisões administrativas.

## MFA

Todas as 102 rotas têm `mfa.backoffice`. A sensibilidade dinâmica usa a
permission completa para as novas ações de `applications`, `documents` e
`administrative_processes`.

Foi removido o efeito transversal que faria `work_tasks.complete` tornar o
perfil `support_agent` sensível apenas por partilhar o sufixo `complete`.
Perfis legacy sensíveis e flags manuais de MFA mantêm o comportamento anterior.

## Auditoria e feedback

- `log.backoffice` está presente nas 102 rotas;
- mutações continuam a usar os eventos de auditoria dos Services existentes;
- recusas não executam efeitos laterais;
- respostas JSON usam o feedback seguro da Sprint 46D;
- caminhos privados, payloads técnicos e dados pessoais não aparecem no
  feedback;
- a view de detalhe documental deixou de mostrar o caminho interno;
- ações mutáveis são ocultadas na UI quando a Policy as recusa.

## Testes

Cobertura específica criada/reforçada:

- manifesto e middleware efetivo das 102 rotas;
- feature, permission e Policy independentes;
- Município A/B;
- candidato e auditor;
- role/utilizador inativos;
- MFA;
- download e caminhos privados;
- IA documental;
- templates e versões;
- processos e pedidos de aperfeiçoamento;
- mutação recusada sem efeitos;
- compatibilidade de contratos, finanças, apoio e Case Workspace.

Resultados finais:

- testes dirigidos de rotas/limites/processos: 20 testes, 1 404 asserções;
- regressões identificadas na primeira suite e revalidadas: 51 testes,
  1 720 asserções;
- comandos de auditoria: 8 testes, 509 asserções;
- PHPUnit completo: 1 122 testes, 9 741 asserções, PASS;
- filtro UX: 130 testes, 645 asserções, PASS;
- integridade de testes: 0 violações críticas e 0 avisos.

A primeira execução completa identificou 13 fixtures incompatíveis com os
novos limites. Foram corrigidas através de Município, feature e MFA reais,
sem remover asserções nem converter 403 em sucesso.

## Quality gates

- `composer validate --strict`: PASS;
- `php artisan optimize:clear`: PASS;
- Pint incremental: PASS;
- Pint global: PASS;
- PHPStan global: 0 erros;
- PHPUnit completo: PASS;
- PHPUnit UX: PASS;
- `php artisan route:list --except-vendor`: PASS, 1 167 rotas apresentadas;
- `php artisan migrate:status`: PASS, migration 47B aplicada;
- `npm run build`: PASS;
- `git diff --check`: PASS;
- auditoria manual de enfraquecimento de testes: resultado vazio.

## Inventário antes/depois

| Métrica | Depois da 47A.1 | Depois da 47B | Delta |
| --- | ---: | ---: | ---: |
| Rotas totais | 1 170 | 1 170 | 0 |
| Rotas com role fixa | 854 | 752 | -102 |
| Rotas backoffice com role fixa | 634 | 532 | -102 |
| Rotas candidate com role fixa | 220 | 220 | 0 |
| Rotas com permission middleware | 272 | 374 | +102 |
| Backoffice fixas sem active/MFA/log | 593 | 491 | -102 |

O inventário residual coincide exatamente com o plano:

| Sprint | Rotas backoffice ainda fixas |
| --- | ---: |
| 47C | 78 |
| 47D | 78 |
| 47E | 58 |
| 47F | 99 |
| 47G | 96 |
| 47H | 123 |
| **Total** | **532** |

A evidência integral está em
`docs/access/progress/sprint-47b-after.json`.

## Segurança e RGPD

- candidate permanece fora das rotas backoffice;
- auditor permanece read-only;
- documentos e artefactos continuam privados;
- Município A não acede ao Município B;
- permissões financeiras não abrem o repositório documental geral;
- IA documental não expõe extrações sem `documents.audit`;
- dados pessoais não são incluídos no feedback de autorização;
- MFA e logging são obrigatórios;
- não foram criadas permissions diretas ou wildcards;
- não foi enfraquecida qualquer Policy.

## Riscos residuais

- 532 rotas backoffice ainda usam role fixa e pertencem integralmente às
  Sprints 47C–47H;
- 491 dessas rotas ainda não têm o trio de guards backoffice;
- registos históricos das cinco tabelas de intake com Município nulo ficam
  inacessíveis até regularização por fonte autoritativa;
- a migration deve preceder o deploy da aplicação;
- o rollout externo do operador de plataforma continua sujeito ao gate
  separado da 47A.0.

## Backlog

1. Executar 47C sobre a branch publicada da 47B.
2. Migrar as 78 rotas de elegibilidade, classificação e decisões.
3. Manter a reconciliação mixed-context antes de aplicar entitlements.
4. Repetir o inventário até `backoffice_fixed_role_routes = 0`.
5. Preparar operação de regularização de registos históricos sem Município,
   usando apenas relações autoritativas.

## Decisão

**PASS**

As 102 rotas do manifesto 47B estão permission-first, com guards completos,
scope municipal, MFA, auditoria e feedback seguro. A suite, UX, Pint, PHPStan,
Composer, build, migrations, rotas e integridade de testes passaram. A branch
pode ser publicada e usada como única base da Sprint 47C.
