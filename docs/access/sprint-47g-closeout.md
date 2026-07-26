# Fecho da Sprint 47G

## Estado

`REPOSITORY_PASS_DEPLOYMENT_GATED`

O repositório está tecnicamente apto para revisão e merge. O deployment
continua sujeito ao processo normal de aprovação e não foi executado.

## Objetivo

A Sprint 47G migrou Manutenção, Vistorias, Visitas e Agenda para uma
arquitetura permission-first, com MFA, auditoria e isolamento municipal
fail-closed. O fecho partiu do commit:

```text
f4f8f00acf696c429cb1e5a60e78f4cb393bd15e
```

Esta intervenção preservou o trabalho já existente no checkpoint,
concluiu o hardening municipal do domínio de visitas e eliminou as nove
regressões conhecidas da suite completa.

## Arquitetura permission-first

A autorização das operações da Sprint 47G segue cumulativamente:

```text
permission exata
&& Policy
&& scope municipal
&& estado ou transição válida
&& MFA
&& auditoria
```

As roles continuam a ser agregadores de permissions. Não foi criado
qualquer bypass de administrador, wildcard, grant direto, middleware de
role fixa ou entitlement comercial inadequado. A ausência de um
`FeatureKey` próprio para estes domínios foi mantida deliberadamente.

### Manifesto de rotas

O manifesto imutável
`docs/access/manifests/sprint-47g-route-manifest.json` mantém:

| Domínio | Rotas |
|---|---:|
| Agenda | 1 |
| Visitas | 18 |
| Manutenção | 51 |
| Vistorias | 26 |
| **Total** | **96** |

As 96 rotas possuem permission exata, guards de utilizador ativo, MFA e
logging, sem middleware efetivo de role fixa. O teste de arquitetura
mantém as asserções sobre URI, método, controller, action e middleware.

### Catálogo de permissions

Foram preservadas as decisões já reconciliadas na sprint:

- 68 permissions únicas;
- 12 permissions existentes reutilizadas;
- 56 permissions semânticas novas;
- 43 operações de leitura;
- 53 operações de mutação.

As operações de ciclo de vida utilizam abilities específicas, incluindo
confirmar, concluir, cancelar, rejeitar, bloquear, desbloquear e gerar
slots. Permissions genéricas não substituem estas abilities.

## Isolamento municipal

### Manutenção

O scope municipal central já presente no checkpoint foi preservado nos
recursos, services, Policies, dashboards, relatórios e timeline. As
relações entre pedido, imóvel, intervenção, custo, anexo, fornecedor e
atribuição continuam a ter de ser municipalmente coerentes.

### Vistorias

O scope municipal central e o modelo híbrido dos templates foram
preservados. Relatórios e anexos privados mantêm permissions de download
específicas, MFA, Policy, auditoria e storage privado.

### Visitas

Foi concluído o isolamento municipal de:

- `VisitAvailability`;
- `VisitSlot`;
- `HousingVisit`.

O campo `municipality_id` é a chave canónica para queries e índices, mas
não valida sozinho um registo. Os scopes também verificam a coerência das
relações presentes.

#### Disponibilidades

- Município resolvido por concurso e/ou imóvel;
- concurso e imóvel, quando coexistem, têm de pertencer ao mesmo
  Município;
- técnico opcional tem de pertencer ao Município resolvido;
- `municipality_id` é preenchido internamente;
- relações ausentes, estrangeiras ou contraditórias falham fechado.

#### Slots

- Município herdado da disponibilidade;
- concurso, imóvel e técnico replicados têm de coincidir exatamente com
  a disponibilidade;
- slots preexistentes incoerentes não são reutilizados;
- geração de slots valida o scope do ator antes de escrever.

#### Visitas

- slot válido é obrigatório;
- candidatura, programa, concurso, imóvel e técnico são comparados com o
  Município canónico;
- a candidatura tem de pertencer ao candidato da visita;
- o candidato não é usado como origem municipal;
- o candidato pode continuar sem `municipality_id`;
- dados órfãos ou contraditórios não são expostos.

### Operador global explícito

O scope global depende de um `PlatformOperatorAssignment` ativo e não
revogado, validado pelo `PlatformOperatorScopeService`. Um utilizador sem
Município e sem assignment válido recebe sempre uma query vazia.

Mesmo um operador global não torna estruturalmente válido um registo
órfão ou contraditório.

## Serviços e transações

Foi criado
`app/Services/Municipalities/VisitMunicipalContextService.php` para
centralizar:

- resolução municipal;
- validação relacional de disponibilidade, slot e visita;
- validação do scope municipal/global do ator;
- contexto de booking;
- validação de reagendamento;
- falhas controladas através de `ValidationException`.

Os services de disponibilidade, geração de slots, booking, cancelamento
e reagendamento foram integrados com este contexto. No reagendamento, a
visita e os dois slots são bloqueados e validados antes de qualquer
mutação. Uma tentativa cross-municipality não liberta o slot anterior,
não reserva o novo slot, não altera a visita e não cria histórico,
auditoria, tarefa ou notificação indevida.

As factories de visitas passaram a gerar cadeias coerentes por defeito.
A `ApplicationFactory` garante que o concurso gerado pertence ao mesmo
programa da candidatura, sem mascarar fixtures deliberadamente
incoerentes.

## Policies, requests e controllers

As Policies de disponibilidades, slots e visitas usam permissions
semânticas exatas e scope municipal. Não existem verificações por role
nem bypass administrativo.

Os Form Requests chamam as abilities correspondentes à operação. Os
controllers aplicam o scope antes de filtros, eager loading, ordenação e
paginação. Concursos, imóveis e técnicos apresentados nos formulários
também são limitados ao contexto autorizado.

Os fluxos de candidato mantêm ownership próprio e revalidação no service.
O candidato não recebe acesso às rotas backoffice.

## Calendário, dashboards, timeline e Agenda

`VisitCalendarService::backofficeCalendar()` recebe agora o ator e aplica
o scope municipal antes dos filtros temporais ou por técnico.

O `VisitTimelineProvider`:

- exige `visits.view`;
- começa pela query municipalmente scoped;
- omite integralmente dados sem permission ou sem contexto;
- é resolvido pelo container no registry e no provider de Hoje.

As métricas de visitas de `VisitStatisticsService` e
`CandidateSupportDashboardService` também recebem o ator e agregam apenas
visitas e slots estruturalmente válidos do seu scope.

A permission `agenda.view` permite abrir a Agenda, mas não concede acesso
implícito aos providers. Cada provider continua a aplicar a permission e
o scope do seu próprio domínio.

## Migrations

Foram preservadas as migrations reversíveis já existentes na Sprint 47G:

- `2026_07_25_181312_add_municipal_scope_to_maintenance_catalogs.php`;
- `2026_07_26_005952_add_municipal_scope_to_visit_domain_tables.php`.

A migration de visitas mantém as foreign keys e índices municipais para
datas de disponibilidades, slots e visitas. Não foi criada uma migration
duplicada neste fecho.

## MFA

O perfil `support_agent` continua abrangido pela regra MFA. Os testes de
autorização passam a validar MFA antes de esperar um `403`, evitando
confundir o redirect legítimo para MFA com uma decisão de Policy.

Nenhum middleware MFA foi removido ou enfraquecido.

## Correção das nove falhas conhecidas

1. `CustomRoleManagementTest`: sessão MFA adicionada antes de validar o
   bloqueio por autorização.
2. `QA30UserRoleCompetencyManagementTest`: `support_agent` passa a ser
   esperado como perfil com MFA obrigatório.
3. `MfaEnforcementBackofficeTest`: matriz de perfis sensíveis atualizada
   para incluir `support_agent`.
4. `PermissionMatrixTest`: rota de manutenção é testada com MFA válida e
   a sessão é limpa antes do cenário que verifica redirect para MFA.
5. `RoleEscalationProtectionTest`: MFA validada antes do `403`.
6. `WorkTaskAuthorizationTest`: MFA validada antes da verificação de
   permission da tarefa jurídica.
7. `FavoritesTest`: MFA validada antes do bloqueio do workspace não
   autorizado.
8. `AuditAccessRoutesCommandTest`: inventário atualizado para 343 rotas
   residuais com role fixa, 123 no backoffice e 783 rotas com permission
   middleware, refletindo a migração das 96 rotas.
9. `InventoryBackofficeRoutesCommandTest`: download crítico de manutenção
   passa a ser validado por permission exata, ausência de role fixa e
   presença de active/MFA/logging.

## Testes permanentes adicionados

### `VisitOperationalMunicipalScopeTest`

Cobre:

- isolamento A/B de disponibilidades, slots e visitas;
- `owns*`;
- calendário e métricas scoped;
- utilizador sem Município;
- operador global ativo, revogado e inativo;
- registos nulos, órfãos ou contraditórios;
- permission, MFA, candidato e acesso HTTP.

### `VisitMunicipalContextServiceTest`

Cobre:

- resolução pelas diferentes origens;
- técnico e ator estrangeiros;
- herança municipal do slot;
- candidatura coerente e incoerente;
- booking por candidato sem Município;
- validação antes de incrementar `booked_count`;
- slots órfãos;
- reagendamento no mesmo Município;
- rejeição cross-municipality com atomicidade completa.

`OperationalTimelineProviderTest` foi expandido para validar permission e
scope municipal das visitas, incluindo operador global.

## Validação executada

| Validação | Resultado |
|---|---|
| Sintaxe PHP | PASS, 51 ficheiros |
| Testes focados 47G | PASS, 50 testes / 1.688 asserções |
| Nove regressões conhecidas | PASS, 34 testes / 770 asserções |
| QA35 visitas/candidato | PASS, 3 testes / 24 asserções |
| Suite PHPUnit completa final | PASS, 1.237 testes / 17.669 asserções |
| Laravel Pint | PASS |
| Laravel Pint `--test` | PASS |
| PHPStan | PASS, 0 erros |
| `php artisan route:cache` | PASS |
| `php artisan route:clear` | PASS |
| `php artisan route:list --except-vendor` | PASS |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `npm run build` | PASS |

A primeira execução completa identificou uma fixture QA35 com concurso,
imóvel e técnico de Municípios distintos. A fixture foi corrigida para
representar o cenário funcional pretendido e a suite completa foi
repetida integralmente com sucesso.

## Limitações deliberadas e Sprint 47H

Ficaram fora desta sprint:

- novos workflows ou estados de visitas;
- novos KPIs, relatórios ou redesign da Agenda;
- novos entitlements comerciais;
- alterações ao portal público, simulador ou workflows de candidatura;
- relatórios, comunicações, notificações e configuração previstos para a
  Sprint 47H;
- limpeza global de permissões wildcard fora do manifesto 47G.

## Decisão final

```text
REPOSITORY_PASS_DEPLOYMENT_GATED
```

Código, testes, análise estática, rotas e build estão aprovados. A branch
fica pronta para revisão e merge, sem alteração de `main` e sem deploy.
