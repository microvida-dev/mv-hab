# TECH-QUALITY-002 — Inventário PHPStan

## Baseline

- Branch-base: `fe8372e76b8313fb45f50565b828310ad1f30f4a`
- Data da auditoria: 23/07/2026
- Nível PHPStan: 8
- Diagnósticos reais: **156**
- Ficheiros afetados: **40**
- Baselines ou supressões adicionadas: **0**
- Resultado final: **0 erros**

A primeira execução sem verbosidade apresentou 156 como total, mas limitou
`error_details` aos primeiros 30 diagnósticos de oito ficheiros e marcou a resposta como
truncada. A baseline foi reproduzida num worktree isolado do commit-base com `-v`,
confirmando a distribuição integral abaixo. O total do wrapper estava correto; a lista
inicial de detalhes não estava completa.

## Distribuição por identificador

| Identificador | Diagnósticos |
|---|---:|
| `nullsafe.neverNull` | 47 |
| `method.nonObject` | 32 |
| `missingType.iterableValue` | 21 |
| `property.notFound` | 13 |
| `argument.type` | 12 |
| `nullCoalesce.expr` | 8 |
| `argument.templateType` | 6 |
| `property.nonObject` | 5 |
| `identical.alwaysFalse` | 3 |
| `method.unused` | 2 |
| `return.type` | 1 |
| `missingType.generics` | 1 |
| `function.alreadyNarrowedType` | 1 |
| `cast.string` | 1 |
| `method.notFound` | 1 |
| `instanceof.alwaysTrue` | 1 |
| `assign.propertyType` | 1 |
| **Total** | **156** |

## Distribuição por ficheiro

| Ficheiro | Diagnósticos |
|---|---:|
| `app/Console/Commands/AuditAccessRoutes.php` | 8 |
| `app/Data/Dashboard/TimelineEvent.php` | 2 |
| `app/Http/Controllers/Admin/DocumentReviewController.php` | 15 |
| `app/Http/Controllers/Backoffice/AgendaController.php` | 1 |
| `app/Http/Controllers/Backoffice/ProcedureMinuteController.php` | 1 |
| `app/Http/Controllers/Candidate/SimulationController.php` | 1 |
| `app/Http/Controllers/Navigation/FavoriteController.php` | 1 |
| `app/Http/Requests/Simulator/StoreCandidateSimulationRequest.php` | 2 |
| `app/Http/Requests/UpdateRequiredDocumentRequest.php` | 1 |
| `app/Services/Agenda/AgendaEventFilter.php` | 2 |
| `app/Services/Agenda/AgendaService.php` | 2 |
| `app/Services/Agenda/AgendaTimelineRepository.php` | 1 |
| `app/Services/Agenda/DTO/AgendaDay.php` | 1 |
| `app/Services/Agenda/DTO/AgendaMonth.php` | 1 |
| `app/Services/Agenda/DTO/AgendaWeek.php` | 1 |
| `app/Services/Dashboard/Operations/OperationsSummaryProvider.php` | 2 |
| `app/Services/Dashboard/Operations/TodayProvider.php` | 5 |
| `app/Services/Dashboard/ProfileDashboardService.php` | 2 |
| `app/Services/Dashboard/Timeline/Providers/AllocationTimelineProvider.php` | 9 |
| `app/Services/Dashboard/Timeline/Providers/ApplicationTimelineProvider.php` | 1 |
| `app/Services/Dashboard/Timeline/Providers/ComplaintTimelineProvider.php` | 5 |
| `app/Services/Dashboard/Timeline/Providers/ContractTimelineProvider.php` | 5 |
| `app/Services/Dashboard/Timeline/Providers/CorrectionRequestTimelineProvider.php` | 3 |
| `app/Services/Dashboard/Timeline/Providers/DeadlineTimelineProvider.php` | 2 |
| `app/Services/Dashboard/Timeline/Providers/DocumentTimelineProvider.php` | 9 |
| `app/Services/Dashboard/Timeline/Providers/HearingTimelineProvider.php` | 3 |
| `app/Services/Dashboard/Timeline/Providers/InspectionTimelineProvider.php` | 1 |
| `app/Services/Dashboard/Timeline/Providers/InternalAlertTimelineProvider.php` | 2 |
| `app/Services/Dashboard/Timeline/Providers/KeyHandoverTimelineProvider.php` | 1 |
| `app/Services/Dashboard/Timeline/Providers/LotteryTimelineProvider.php` | 9 |
| `app/Services/Dashboard/Timeline/Providers/MaintenanceTimelineProvider.php` | 8 |
| `app/Services/Dashboard/Timeline/Providers/RentTimelineProvider.php` | 18 |
| `app/Services/Dashboard/Timeline/Providers/RgpdTimelineProvider.php` | 4 |
| `app/Services/Dashboard/Timeline/Providers/TenantOperationsTimelineProvider.php` | 20 |
| `app/Services/Dashboard/Timeline/Providers/VisitTimelineProvider.php` | 1 |
| `app/Services/Dashboard/Timeline/TimelineAggregatorService.php` | 2 |
| `app/Services/Dashboard/Timeline/TimelineMetricsService.php` | 1 |
| `app/Services/Navigation/FavoritesService.php` | 1 |
| `app/Services/ProcedureMinutes/Renderers/AlcanenaAta01Renderer.php` | 1 |
| `database/seeders/AlcanenaProcedureTemplateSeeder.php` | 1 |
| **Total** | **156** |

## Estratégia de remediação

### Contratos HTTP e comandos

- Foram definidos array shapes para o comando de auditoria de rotas, preservando o schema
  JSON/CSV e os contadores.
- Os utilizadores autenticados passaram a ser validados como `User` antes de entrar em
  serviços tipados.
- Os IDs de favoritos validados passaram a ser normalizados como lista de inteiros.
- O agregado opcional do simulador passou a ser tratado sem assumir relação obrigatória.
- A autorização de atualização de documentos obrigatórios foi corrigida de `ccan()` para
  `can()` e coberta por testes de autorização positiva e negativa.

### Revisão documental

- A fila de revisão recebeu shapes e collections genéricas.
- Relações e atributos foram alinhados com os modelos reais.
- O estado documental obrigatório foi formalizado como `DocumentStatus`.
- Métodos mortos e fallbacks impossíveis foram removidos.
- A auditoria mantém os valores anteriores e posteriores do enum.

### Agenda e Dashboard

- DTOs de dia, semana e mês receberam tipos concretos.
- Filtros nulos passaram a ter semântica explícita.
- A ordenação foi corrigida para usar comparadores reais; o array de callbacks fornecido a
  `sortBy()` era interpretado como comparadores e não como chaves de ordenação.
- Eventos sem data ou workspace passaram a ter fallback determinístico.
- Providers do Dashboard normalizam apenas payloads autorizados e mantêm os limites das
  consultas.

### Timeline processual e operacional

- Datas convertidas por casts Eloquent foram documentadas como `Carbon`.
- Estados convertidos foram documentados e comparados como enums.
- Foram removidos estados inexistentes e substituídos por valores atuais dos enums.
- Relações usadas nas descrições passaram a ser carregadas antecipadamente.
- Campos inexistentes foram substituídos por referências reais:
  - `RequiredDocument::name` por atributos existentes;
  - `CorrectionResponse::response_number` pelo número do pedido relacionado;
  - `HearingSubmission::submission_number` pelo número da audiência;
  - `Program::title` por `Program::name`;
  - `KeyHandoverAppointment::appointment_number` pela referência da candidatura;
  - `MaintenanceIntervention::intervention_number/title` pelo pedido de manutenção;
  - `DataSubjectRequest::type` por `request_type`;
  - `HousingUnit::reference` por `code`.
- Rendas, faturas, pagamentos, comunicações, sorteios, RGPD, visitas, vistorias e manutenção
  passaram a produzir metadata com valores de enum consistentes.

## Testes acrescentados

- `ProcessTimelineProviderTest`: estados e relações dos providers processuais.
- `HearingTimelineProviderTest`: audiências ativas e número real da audiência na pronúncia.
- `OperationalTimelineProviderTest`: vistorias, alertas, entrega de chaves, manutenção,
  RGPD e visitas.

Foram ainda reforçados testes existentes de Agenda, documentos, favoritos, sorteios,
rendas e operações do inquilino.

## Resultado

```text
Baseline: 156 erros / 40 ficheiros
Final:      0 erros /  0 ficheiros
Delta:   -156 erros
```

Não foi criado baseline, `ignoreErrors`, wildcard, cast falso, `@phpstan-ignore` ou
alteração do nível de análise.
