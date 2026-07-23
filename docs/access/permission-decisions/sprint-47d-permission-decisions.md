# Decisões de permissions — Sprint 47D

## Âmbito

O manifesto imutável reconciliou 78 rotas backoffice:

- 13 de audiências;
- 15 de reclamações;
- 23 de listas;
- 27 de atribuições e sorteios.

Foram identificadas 62 rotas cuja sugestão automática era inexistente,
genérica, de leitura para uma mutação ou pertencente a outro bounded context.
As cinco rotas `complaint-decisions` permanecem fora deste lote porque foram
migradas na Sprint 47C.

## Regras comuns

- Todas as rotas exigem `auth`, `active.backoffice`, `mfa.backoffice`,
  `log.backoffice` e `municipality.feature:applications.review`.
- Cada rota usa exatamente uma permission final e uma ability backoffice
  específica.
- A Policy confirma permission, bloqueia `candidate`, mantém `auditor`
  read-only e aplica scope municipal fail-closed.
- Nenhuma permission é atribuída diretamente a utilizadores.
- Não são criados wildcards, roles fixas ou uma nova `FeatureKey`.
- As permissions `complaints.decide`, `complaints.approve` e
  `complaints.cancel` da 47C não são duplicadas.
- Ler, gerar, aprovar, publicar, bloquear, executar, validar, enviar e
  cancelar são poderes distintos.

## Catálogo final

Permissions reutilizadas:

- `allocations.view`;
- `complaints.view`;
- `public_lists.view`;
- `public_lists.approve`;
- `public_lists.publish`.

Permissions novas:

- audiências: `hearings.view`, `create`, `issue`, `review`, `accept`,
  `reject`, `close`, `cancel`;
- reclamações: `complaints.assign`, `mark_received`, `review`,
  `request_information`, `mark_overdue`, `close`;
- listas: `public_lists.generate`, `review`, `lock`, `archive`, `cancel`,
  `open_complaint_period`, `close_complaint_period`;
- atribuições: `allocations.process_withdrawal`;
- sorteios: `lotteries.view`, `create`, `update`, `participants.load`,
  `participants.lock`, `run`, `validate`, `cancel`,
  `convocations.generate`, `convocations.send`, `attendance.manage`,
  `winners.register`, `reports.generate`, `export`.

Não são criadas permissions sem rota efetiva na Sprint 47D.

## Menor privilégio por template

| Template | Concessões 47D | Limites explícitos |
| --- | --- | --- |
| `administrator` | Todas através do wildcard estrutural existente | O wildcard não é novo e não é atribuído a utilizadores |
| `municipal_technician` | Operação completa das 78 rotas, conforme funções técnicas atuais | Continua sujeito a Município, feature, MFA, Policy e estado |
| `jury` | Leitura de audiências/reclamações/listas/sorteios; aceitação/rejeição de pronúncias; aprovação/publicação/bloqueio de listas já previsto; validação de resultado de sorteio | Sem criar/executar/cancelar sorteio, bloquear participantes, enviar convocatórias, registar presenças/vencedores ou tratar reclamações administrativamente |
| `legal_manager` | Audiências e reclamações processuais; leitura/aprovação de listas; leitura de sorteios | Sem publicação de listas, execução de sorteio ou atribuição |
| `housing_manager` | Operação de sorteios, relatórios pós-sorteio e desistências | Sem poderes jurídicos sobre audiência/reclamação |
| `auditor` | Apenas permissions `*.view` já previstas | Nunca recebe mutações da 47D |
| `candidate` | Nenhuma permission backoffice nova | Mantém apenas rotas próprias com ownership |
| Perfis personalizados | Nenhuma concessão automática | Devem receber apenas permissions exatas por decisão municipal |

O júri não recebe `lotteries.run`, `lotteries.cancel`,
`lotteries.participants.lock` ou `lotteries.convocations.send`. A validação do
resultado é mantida porque o catálogo anterior já lhe atribuía
`allocations.approve` e a Policy de validação dependia dessa capacidade.

## Feature, scope, MFA e auditoria

O ADR
`docs/architecture/adr-hearings-lists-entitlement-scope.md` aprova
`applications.review` para as 78 rotas.

Fontes municipais:

- audiência/submissão → candidatura → programa → Município;
- reclamação/pedido de informação → candidatura → programa → Município;
- lista/execução de automação → concurso/programa → Município;
- sorteio/resultado/convocatória/desistência/fecho → procedimento,
  concurso ou programa → Município.

Todas as mutações e downloads exigem auditoria. Leituras mantêm logging de
acesso backoffice e podem gerar auditoria sensível quando o padrão existente
assim o determinar.

## Matriz das 62 decisões semânticas

Cada linha documenta uma sugestão insuficiente ou ausente. As outras 16 rotas
mantêm uma permission candidata semanticamente adequada, mas recebem os
mesmos guards, Policy, scope e testes.

| Rota | Ação | Risco | Sugestão | Insuficiência | Permission final | Tipo | Label | Templates que recebem | Templates que não recebem | Feature | Policy / ability | Scope | MFA | Auditoria | Testes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `backoffice.additional-information-requests.close` | `close` | high | `complaints.update` | Ação genérica não representa a transição específica. | `complaints.close` | nova | Fechar reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\AdditionalInformationRequestPolicy` / `closeBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.additional-information-requests.create` | `create` | high | `complaints.create` | Ação genérica não representa a transição específica. | `complaints.request_information` | nova | Pedir informação em reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ComplaintPolicy` / `requestInformationBackoffice` | reclamação → candidatura → programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.additional-information-requests.mark-overdue` | `markOverdue` | high | `complaints.view` | Leitura não autoriza mutação auditável. | `complaints.mark_overdue` | nova | Marcar como vencido reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\AdditionalInformationRequestPolicy` / `markOverdueBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.additional-information-requests.store` | `store` | high | `complaints.create` | Ação genérica não representa a transição específica. | `complaints.request_information` | nova | Pedir informação em reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ComplaintPolicy` / `requestInformationBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.complaints.assign` | `assign` | high | `—` | Sem permission; dependeria da role fixa. | `complaints.assign` | nova | Atribuir reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ComplaintPolicy` / `assignBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.complaints.close` | `close` | high | `complaints.update` | Ação genérica não representa a transição específica. | `complaints.close` | nova | Fechar reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ComplaintPolicy` / `closeBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.complaints.mark-received` | `markReceived` | high | `complaints.view` | Leitura não autoriza mutação auditável. | `complaints.mark_received` | nova | Marcar como recebido reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ComplaintPolicy` / `markReceivedBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.complaints.reviews.store` | `store` | high | `complaints.create` | Ação genérica não representa a transição específica. | `complaints.review` | nova | Rever reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ComplaintPolicy` / `createBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.complaints.start-review` | `startReview` | high | `complaints.update` | Ação genérica não representa a transição específica. | `complaints.review` | nova | Rever reclamações | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ComplaintPolicy` / `reviewBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.contest-closures.show` | `show` | high | `contests.view` | Ação genérica não representa a transição específica. | `allocations.view` | reutilizada | Consultar atribuições | templates existentes; candidate apenas nas rotas próprias | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\ContestClosurePolicy` / `viewBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.draw-convocations.index` | `index` | high | `allocations.view` | Atribuição genérica não distingue sorteio. | `lotteries.view` | nova | Consultar sorteios | administrator, municipal_technician, jury, legal_manager, housing_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\DrawConvocationPolicy` / `viewAnyBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.draw-convocations.send` | `send` | high | `allocations.view` | Leitura não autoriza mutação auditável. | `lotteries.convocations.send` | nova | Enviar convocatórias de sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\DrawConvocationPolicy` / `sendBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.draw-convocations.show` | `show` | high | `allocations.view` | Atribuição genérica não distingue sorteio. | `lotteries.view` | nova | Consultar sorteios | administrator, municipal_technician, jury, legal_manager, housing_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\DrawConvocationPolicy` / `viewBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.hearing-submissions.accept` | `accept` | high | `complaints.view` | Leitura não autoriza mutação auditável. | `hearings.accept` | nova | Aceitar audiências | administrator, municipal_technician, jury, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingSubmissionPolicy` / `acceptBackoffice` | audiência → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.hearing-submissions.reject` | `reject` | high | `complaints.reject` | Namespace de reclamações não distingue audiência. | `hearings.reject` | nova | Rejeitar audiências | administrator, municipal_technician, jury, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingSubmissionPolicy` / `rejectBackoffice` | audiência → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.hearing-submissions.show` | `show` | high | `complaints.view` | Namespace de reclamações não distingue audiência. | `hearings.view` | nova | Consultar audiências | administrator, municipal_technician, jury, legal_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingSubmissionPolicy` / `viewBackoffice` | audiência → candidatura → programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.hearings.cancel` | `cancel` | high | `—` | Sem permission; dependeria da role fixa. | `hearings.cancel` | nova | Cancelar audiências | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingPolicy` / `cancelBackoffice` | audiência → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.hearings.close` | `close` | high | `complaints.update` | Namespace de reclamações não distingue audiência. | `hearings.close` | nova | Fechar audiências | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingPolicy` / `closeBackoffice` | audiência → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.hearings.create` | `create` | high | `complaints.create` | Namespace de reclamações não distingue audiência. | `hearings.create` | nova | Criar audiências | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingPolicy` / `createBackoffice` | audiência → candidatura → programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.hearings.index` | `index` | high | `complaints.view` | Namespace de reclamações não distingue audiência. | `hearings.view` | nova | Consultar audiências | administrator, municipal_technician, jury, legal_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingPolicy` / `viewAnyBackoffice` | audiência → candidatura → programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.hearings.issue` | `issue` | high | `complaints.view` | Leitura não autoriza mutação auditável. | `hearings.issue` | nova | Emitir audiências | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingPolicy` / `issueBackoffice` | audiência → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.hearings.show` | `show` | high | `complaints.view` | Namespace de reclamações não distingue audiência. | `hearings.view` | nova | Consultar audiências | administrator, municipal_technician, jury, legal_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingPolicy` / `viewBackoffice` | audiência → candidatura → programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.hearings.store` | `store` | high | `complaints.create` | Namespace de reclamações não distingue audiência. | `hearings.create` | nova | Criar audiências | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingPolicy` / `createBackoffice` | audiência → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.automation.definitive` | `generateDefinitive` | high | `—` | Sem permission; dependeria da role fixa. | `public_lists.generate` | nova | Gerar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ContestPolicy` / `generateBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.automation.index` | `index` | high | `contests.view` | Consulta de concurso não autoriza listas. | `public_lists.view` | reutilizada | Consultar listas públicas | templates existentes; candidate apenas nas rotas próprias | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\ContestPolicy` / `viewBackoffice` | lista/run → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lists.automation.provisional` | `generateProvisional` | high | `—` | Sem permission; dependeria da role fixa. | `public_lists.generate` | nova | Gerar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ContestPolicy` / `generateBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.definitive.archive` | `archive` | high | `public_lists.update` | Ação genérica não representa a transição específica. | `public_lists.archive` | nova | Arquivar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\DefinitiveListPolicy` / `archiveBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.definitive.create` | `create` | high | `public_lists.create` | Ação genérica não representa a transição específica. | `public_lists.generate` | nova | Gerar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\DefinitiveListPolicy` / `generateAnyBackoffice` | lista/run → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lists.definitive.lock` | `lock` | high | `—` | Sem permission; dependeria da role fixa. | `public_lists.lock` | nova | Bloquear listas públicas | administrator, municipal_technician, jury | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\DefinitiveListPolicy` / `lockBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.definitive.review` | `review` | high | `public_lists.update` | Ação genérica não representa a transição específica. | `public_lists.review` | nova | Rever listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\DefinitiveListPolicy` / `reviewBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.definitive.store` | `store` | high | `public_lists.create` | Ação genérica não representa a transição específica. | `public_lists.generate` | nova | Gerar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\DefinitiveListPolicy` / `generateBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.provisional.archive` | `archive` | high | `public_lists.update` | Ação genérica não representa a transição específica. | `public_lists.archive` | nova | Arquivar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ProvisionalListPolicy` / `archiveBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.provisional.cancel` | `cancel` | high | `—` | Sem permission; dependeria da role fixa. | `public_lists.cancel` | nova | Cancelar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ProvisionalListPolicy` / `cancelBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.provisional.close-complaint-period` | `closeComplaintPeriod` | high | `complaints.update` | Ação genérica não representa a transição específica. | `public_lists.close_complaint_period` | nova | Fechar período de reclamações em listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ProvisionalListPolicy` / `closeComplaintPeriodBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.provisional.create` | `create` | high | `public_lists.create` | Ação genérica não representa a transição específica. | `public_lists.generate` | nova | Gerar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ProvisionalListPolicy` / `generateAnyBackoffice` | lista/run → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lists.provisional.open-complaint-period` | `openComplaintPeriod` | high | `complaints.view` | Leitura não autoriza mutação auditável. | `public_lists.open_complaint_period` | nova | Abrir período de reclamações em listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ProvisionalListPolicy` / `openComplaintPeriodBackoffice` | reclamação → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.provisional.review` | `review` | high | `public_lists.update` | Ação genérica não representa a transição específica. | `public_lists.review` | nova | Rever listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ProvisionalListPolicy` / `reviewBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lists.provisional.store` | `store` | high | `public_lists.create` | Ação genérica não representa a transição específica. | `public_lists.generate` | nova | Gerar listas públicas | administrator, municipal_technician | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ProvisionalListPolicy` / `generateBackoffice` | lista/run → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.attendance.bulk-store` | `bulkStore` | high | `allocations.create` | Atribuição genérica não distingue sorteio. | `lotteries.attendance.manage` | nova | Registar presenças em sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `registerAttendanceBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.attendance.index` | `index` | high | `allocations.view` | Atribuição genérica não distingue sorteio. | `lotteries.view` | nova | Consultar sorteios | administrator, municipal_technician, jury, legal_manager, housing_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `viewBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lottery-draws.attendance.store` | `store` | high | `allocations.create` | Atribuição genérica não distingue sorteio. | `lotteries.attendance.manage` | nova | Registar presenças em sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `registerAttendanceBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.cancel` | `cancel` | high | `—` | Sem permission; dependeria da role fixa. | `lotteries.cancel` | nova | Cancelar sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `cancelBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.convocations.generate` | `generate` | high | `—` | Sem permission; dependeria da role fixa. | `lotteries.convocations.generate` | nova | Gerar convocatórias de sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `generateConvocationsBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.create` | `create` | high | `allocations.create` | Atribuição genérica não distingue sorteio. | `lotteries.create` | nova | Criar sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `createBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lottery-draws.edit` | `edit` | high | `allocations.update` | Atribuição genérica não distingue sorteio. | `lotteries.update` | nova | Alterar sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `updateBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lottery-draws.index` | `index` | high | `allocations.view` | Atribuição genérica não distingue sorteio. | `lotteries.view` | nova | Consultar sorteios | administrator, municipal_technician, jury, legal_manager, housing_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `viewAnyBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lottery-draws.participants.load` | `load` | high | `allocations.view` | Leitura não autoriza mutação auditável. | `lotteries.participants.load` | nova | Carregar participantes de sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `loadParticipantsBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.participants.lock` | `lock` | high | `—` | Sem permission; dependeria da role fixa. | `lotteries.participants.lock` | nova | Bloquear participantes de sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `lockParticipantsBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.post-draw-report.generate` | `generate` | high | `—` | Sem permission; dependeria da role fixa. | `lotteries.reports.generate` | nova | Gerar relatórios de sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `generateReportBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.results.index` | `index` | high | `allocations.view` | Atribuição genérica não distingue sorteio. | `lotteries.view` | nova | Consultar sorteios | administrator, municipal_technician, jury, legal_manager, housing_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `viewBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lottery-draws.run` | `run` | high | `—` | Sem permission; dependeria da role fixa. | `lotteries.run` | nova | Executar sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `runBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.show` | `show` | high | `allocations.view` | Atribuição genérica não distingue sorteio. | `lotteries.view` | nova | Consultar sorteios | administrator, municipal_technician, jury, legal_manager, housing_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `viewBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.lottery-draws.store` | `store` | high | `allocations.create` | Atribuição genérica não distingue sorteio. | `lotteries.create` | nova | Criar sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `createBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.update` | `update` | high | `allocations.update` | Atribuição genérica não distingue sorteio. | `lotteries.update` | nova | Alterar sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `updateBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-draws.validate` | `validateResult` | high | `—` | Sem permission; dependeria da role fixa. | `lotteries.validate` | nova | Validar sorteios | administrator, municipal_technician, jury, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryDrawPolicy` / `validateBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.lottery-results.winner.store` | `store` | high | `allocations.create` | Atribuição genérica não distingue sorteio. | `lotteries.winners.register` | nova | Registar vencedor em sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\LotteryResultPolicy` / `registerWinnerBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.post-draw-reports.download` | `download` | critical | `—` | Sem permission; dependeria da role fixa. | `lotteries.export` | nova | Exportar sorteios | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\PostDrawReportPolicy` / `exportBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.post-draw-reports.show` | `show` | high | `allocations.view` | Atribuição genérica não distingue sorteio. | `lotteries.view` | nova | Consultar sorteios | administrator, municipal_technician, jury, legal_manager, housing_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\PostDrawReportPolicy` / `viewBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.preliminary-hearings.decide` | `decide` | high | `complaints.view` | Leitura não autoriza mutação auditável. | `hearings.review` | nova | Rever audiências | administrator, municipal_technician, legal_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingSubmissionPolicy` / `reviewBackoffice` | audiência → candidatura → programa → Município | Sim | Obrigatória | RTE, BND |
| `backoffice.preliminary-hearings.index` | `index` | high | `complaints.view` | Namespace de reclamações não distingue audiência. | `hearings.view` | nova | Consultar audiências | administrator, municipal_technician, jury, legal_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingSubmissionPolicy` / `viewAnyBackoffice` | audiência → candidatura → programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.preliminary-hearings.show` | `show` | high | `complaints.view` | Namespace de reclamações não distingue audiência. | `hearings.view` | nova | Consultar audiências | administrator, municipal_technician, jury, legal_manager, auditor | candidate no backoffice e perfis sem atribuição explícita | `applications.review` | `App\Policies\HearingSubmissionPolicy` / `viewBackoffice` | audiência → candidatura → programa → Município | Sim | Logging de leitura | RTE, BND |
| `backoffice.withdrawals.process` | `process` | high | `allocations.view` | Leitura não autoriza mutação auditável. | `allocations.process_withdrawal` | nova | Processar desistências em atribuições | administrator, municipal_technician, housing_manager | candidate, auditor e perfis sem atribuição explícita | `applications.review` | `App\Policies\ControlledWithdrawalPolicy` / `processBackoffice` | sorteio/recurso → concurso/programa → Município | Sim | Obrigatória | RTE, BND |

## Testes associados

- `HearingsComplaintsListsPermissionRoutesTest`: manifesto, middleware,
  permission, feature, candidate, auditor e recusas sem efeito;
- `HearingsComplaintsListsMunicipalBoundaryTest`: Município A/B e IDs
  relacionais;
- `Sprint11ListsComplaintsHearingTest`: regressão de listas, audiência e
  reclamações;
- `LotteryClosureFlowTest`: execução, resultado, convocatórias, presença,
  vencedor, ranking e relatório pós-sorteio.

## Decisão

As permissions finais separam leitura de transições críticas e evitam herdar
poderes por nome de role. A implementação só pode ser considerada concluída
quando permission, FeatureKey, Policy, scope municipal, estado e auditoria
forem cumulativamente satisfeitos.
