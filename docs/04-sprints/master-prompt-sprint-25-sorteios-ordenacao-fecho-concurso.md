# MASTER PROMPT — EXECUÇÃO DA SPRINT 25: SORTEIOS, ORDENAÇÃO E FECHO DO CONCURSO

Atua como arquiteto sénior Laravel, tech lead, product engineer, QA engineer, especialista em backoffice municipal, procedimentos administrativos, sorteios auditáveis, ranking, atribuição habitacional, RGPD, auditoria e plataformas públicas de Habitação/Arrendamento Acessível.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 25 — Sorteios, Ordenação e Fecho do Concurso
```

Esta sprint pertence à fase de conclusão do procedimento de atribuição, com foco em sorteios auditáveis, ordenação final, convocatórias, gestão de presenças, registo de vencedor, atualização de ranking, relatórios pós-sorteio, entrega de chaves, fecho do concurso e transição para área do inquilino.

A implementação deve preservar os módulos existentes de registo, simulador, candidaturas, gestão documental, workflow administrativo, classificação, ranking, listas provisórias/definitivas, audiência prévia, reclamações, atribuição, contratos, notificações, acompanhamento processual, relatórios, atas, auditoria, RGPD e área do inquilino.

---

# 1. Regra principal

Executa apenas a Sprint 25.

Não avances para Sprint 26, Sprint 27 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper a execução por causa da branch atual.

---

# 2. Ficheiro principal da sprint

Usa como referência principal:

```text
docs/backlog/sprint-25-sorteios-ordenacao-fecho-concurso.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 25.

Não improvisar uma implementação sem o ficheiro de sprint.

---

# 3. Documentação obrigatória a ler antes de implementar

Antes de alterar código, lê, se existirem:

```text
docs/architecture/technical-architecture.md
docs/architecture/data-model-overview.md
docs/product/product-vision.md
docs/product/functional-requirements.md
docs/product/user-roles.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/backlog/roadmap.md

docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-10-matriz-classificacao-ranking.md
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-17-relatorios-indicadores-dashboard-executivo.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/backlog/sprint-23-acompanhamento-processual-avancado.md
docs/backlog/sprint-24-backoffice-operacional-gestao-procedimento.md
docs/backlog/sprint-25-sorteios-ordenacao-fecho-concurso.md

docs/backoffice/list-automation.md
docs/backoffice/procedure-minutes.md
docs/backoffice/process-confirmations.md
docs/candidate-experience/process-tracking.md
docs/candidate-experience/process-timeline.md

docs/backoffice/lottery-draws.md
docs/backoffice/draw-convocations.md
docs/backoffice/draw-attendance.md
docs/backoffice/winner-registration.md
docs/backoffice/ranking-update-after-draw.md
docs/backoffice/post-draw-reports.md
docs/backoffice/key-handover.md
docs/backoffice/tenant-transition.md
docs/backoffice/contest-closure.md

docs/qa/test-coverage-matrix.md
docs/qa/quality-gates.md
docs/qa/regression-test-plan.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

---

# 4. Inspeção inicial obrigatória

Antes de implementar, inspeciona o repositório e identifica:

```text
Versão do Laravel
Versão do PHP
Stack frontend real
Sistema de autenticação
Sistema de backoffice
Sistema de roles/permissões
Sistema de policies
Sistema de Form Requests
Sistema de candidaturas
Sistema de concursos
Sistema de classificação/ranking
Sistema de listas provisórias/finais
Sistema de reclamações/audiência prévia
Sistema de atribuição de habitações
Sistema de contratos
Sistema de área do inquilino
Sistema de visitas/agendamentos
Sistema de notificações
Sistema de relatórios/exportações
Sistema de atas/minutas
Sistema de timeline/acompanhamento processual
Sistema de auditoria/RGPD
Sistema de testes
Configuração PHPStan/Larastan
Configuração Pint
Configuração PHPUnit/Pest
Comandos disponíveis em composer.json
Comandos disponíveis em package.json
```

Inspeciona models, migrations, controllers, services, requests, policies, factories e views existentes relacionados com:

```text
User
Role
Permission
Application
ApplicationStatusHistory
ApplicationSnapshot
Contest
ContestHousingUnit
HousingUnit
HousingVisit
VisitSlot
EligibilityCheck
ApplicationScore
RankingSnapshot
RankingEntry
ProvisionalList
DefinitiveList
Complaint
AllocationRun
Allocation
LotteryRun
LotteryParticipant
LotteryResult
Contract
LeaseContract
Tenant
TenantProfile
TenantArea
KeyHandover
OfficialNotification
CommunicationLog
ProcessTimelineEvent
ProcedureMinute
GeneratedProcedureDocument
ApplicationReport
AuditEvent
SensitiveDataAccessLog
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
LotteryDraw
LotteryRun
LotteryParticipant
LotteryResult
DrawConvocation
DrawAttendance
ContestClosure
WinnerRegistration
RankingUpdateRun
PostDrawReport
KeyHandoverAppointment
TenantTransition
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não apagar histórico existente.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não criar integrações externas obrigatórias.

Não criar assinatura digital nesta sprint.

Não criar pagamentos nesta sprint.

Não substituir o motor de classificação existente.

Não substituir o módulo de listas existente.

Não substituir o módulo de contratos existente.

Não criar publicação automática definitiva sem validação humana, salvo regra já existente e explícita.

---

# 5. PHPStan obrigatório antes de publicar — contexto com 2471 erros legados

O projeto tem atualmente:

```text
2471 erros PHPStan legados
```

A Sprint 25 não tem como objetivo corrigir todos os erros legados.

A Sprint 25 tem como objetivo obrigatório:

```text
Não aumentar o número de erros PHPStan.
Não introduzir novos erros PHPStan nos ficheiros criados ou alterados.
Identificar claramente erros legados versus erros introduzidos pela sprint.
Executar PHPStan antes da implementação e antes da publicação.
Corrigir todos os erros PHPStan diretamente causados pela Sprint 25.
```

## 5.1 Verificação PHPStan inicial

Antes de criar ou alterar ficheiros, executar, se PHPStan existir:

```bash
mkdir -p storage/phpstan

php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint25-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint25-before.txt || true
```

Se existir `phpstan.neon`, usar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint25-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint25-before.txt || true
```

Se existir script no `composer.json`, usar também o comando do projeto, por exemplo:

```bash
composer phpstan
```

Registar no relatório final:

```text
PHPStan inicial executado: sim/não
Total de erros legados conhecido: 2471
Ficheiro de output inicial criado
Comando usado
Falhou por memória: sim/não
Falhou por configuração: sim/não
```

Se PHPStan não existir, documentar:

```text
PHPStan/Larastan não está instalado/configurado. Não foi possível executar análise estática.
```

## 5.2 Estratégia para não misturar erros legados

Durante a implementação:

```text
Não corrigir erros PHPStan fora do âmbito da Sprint 25, salvo se bloquearem diretamente a sprint.
Não alterar ficheiros apenas para reduzir ruído PHPStan legado.
Não criar baseline artificial sem autorização.
Não esconder erros novos com ignoreErrors genéricos.
Não adicionar @phpstan-ignore sem justificação objetiva.
Não reduzir o nível do PHPStan.
Não remover paths analisados.
Não alterar configuração PHPStan para ocultar problemas.
Não alterar regras de análise estática para “passar”.
```

## 5.3 Verificação PHPStan antes de publicação

Antes de considerar a Sprint 25 pronta para publicação, executar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint25-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint25-after.txt || true
```

Com config, se existir:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint25-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint25-after.txt || true
```

Depois, identificar erros nos ficheiros criados ou alterados nesta sprint.

Se existirem erros PHPStan em ficheiros da Sprint 25:

```text
Corrigir antes de concluir.
Não publicar como concluído enquanto houver erro novo causado pela Sprint 25.
```

Se existirem apenas os 2471 erros legados:

```text
Documentar que o passivo PHPStan legado permanece.
Confirmar que a Sprint 25 não adicionou erros novos nos ficheiros alterados.
```

Se a contagem aumentar:

```text
Identificar ficheiros novos/alterados.
Corrigir erros introduzidos.
Reexecutar PHPStan.
Documentar diferença.
```

## 5.4 Resultado PHPStan obrigatório no relatório final

A resposta final deve incluir:

```text
Estado PHPStan inicial
Estado PHPStan antes de publicação
Contagem legada assumida: 2471
Novos erros introduzidos pela Sprint 25: sim/não
Erros PHPStan em ficheiros criados/alterados: sim/não
Correções PHPStan aplicadas
Bloqueia publicação: sim/não
```

---

# 6. Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição de Habitações
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 17 — Relatórios, Indicadores e Dashboard Executivo
Sprint 18 — RGPD, Segurança e Auditoria Avançada
Sprint 23 — Acompanhamento Processual Avançado
Sprint 24 — Backoffice Operacional e Gestão do Procedimento
```

Dependências mínimas:

```text
User
Application
Contest
HousingUnit
Ranking ou ordenação equivalente
Backoffice
Roles/permissions
```

Se o módulo de ranking existir:

```text
Usar ranking/classificação existente.
Não criar ranking paralelo incompatível.
Criar snapshot antes e depois do sorteio.
```

Se o módulo de listas existir:

```text
Usar lista definitiva/final como fonte preferencial.
Não sortear candidatos sem admissão/validação quando o procedimento exigir lista definitiva.
```

Se o módulo de atribuição existir:

```text
Reutilizar Allocation, AllocationRun ou equivalente.
Registar vencedor como atribuição formal ou pré-atribuição conforme estado existente.
```

Se o módulo de contratos existir:

```text
Criar apenas transição operacional para fase contratual/área do inquilino.
Não gerar contrato assinado nesta sprint.
```

Se o módulo de área do inquilino existir:

```text
Ativar acesso apenas após atribuição validada e estado permitido.
Não expor contratos/pagamentos inexistentes.
```

Se algum módulo não existir:

```text
Implementar camada tolerante a dependências parciais.
Documentar limitação.
Não inventar decisão administrativa inexistente.
Não criar fecho definitivo sem dados mínimos.
```

---

# 7. Validação funcional, administrativa e RGPD

Regras obrigatórias:

```text
Sorteios devem ser auditáveis.
Sorteios devem ter participantes fechados antes da execução.
Sorteios devem gerar snapshot dos participantes.
Sorteios devem registar algoritmo, seed/hash e resultado.
Sorteios aprovados/fechados não devem ser reexecutados sem nova versão/run.
Sorteios não devem apagar ranking anterior.
Convocatórias devem ser associadas ao concurso/candidato/ato.
Presenças devem ser registadas com data, técnico e origem.
Vencedor deve ser registado com rastreabilidade.
Atualização automática do ranking deve gerar snapshot.
Relatórios pós-sorteio devem ficar privados quando tiverem dados pessoais.
Entrega de chaves deve ser agendada apenas para candidato vencedor/atribuído.
Transição para área do inquilino deve ocorrer apenas após estado permitido.
Todas as ações críticas devem ser auditadas.
```

Copy obrigatório no sorteio:

```text
O sorteio deve ser validado pelos serviços competentes antes de produzir efeitos administrativos definitivos. O resultado registado na plataforma é auditável e fica associado ao procedimento.
```

Copy obrigatório na convocatória:

```text
A convocatória indica a data, hora, local e instruções do ato. A falta de comparência pode produzir efeitos no procedimento, nos termos aplicáveis ao concurso.
```

Copy obrigatório na entrega de chaves:

```text
A entrega de chaves só deve ocorrer após validação dos requisitos administrativos, contratuais e documentais aplicáveis.
```

---

# 8. Objetivo da implementação

Implementar:

```text
Sorteios auditáveis
Convocatórias automáticas
Gestão de presença
Registo do vencedor
Atualização automática do ranking
Relatórios pós-sorteio
Agendamento da entrega de chaves
Transição automática do candidato para a área do inquilino
```

A plataforma deve permitir que os serviços municipais:

```text
Criem sorteios associados a concursos, listas, rankings ou fogos
Selecionem participantes elegíveis/admitidos
Realizem sorteios auditáveis e rastreáveis
Bloqueiem alterações depois do sorteio validado
Gerem convocatórias automáticas
Registem presenças, ausências e justificações
Registem vencedor e suplentes
Atualizem ranking ou lista de ordenação após sorteio
Gerem relatórios pós-sorteio
Agendem entrega de chaves
Transitem o candidato vencedor para área do inquilino
Mantenham histórico processual e auditoria completa
```

A plataforma deve permitir que o candidato:

```text
Receba convocatória para sorteio ou ato de atribuição
Consulte local, data, hora e instruções
Confirme presença quando aplicável
Consulte o resultado quando publicado
Acompanhe a evolução do processo após sorteio
Receba informação sobre entrega de chaves
Passe para área do inquilino quando a atribuição estiver formalizada
```

---

# 9. Âmbito incluído

Implementar:

```text
Sorteios auditáveis
Criação de sorteios por concurso/fogo/lista
Seleção de participantes elegíveis/admitidos
Snapshot dos participantes
Execução de sorteio com rastreabilidade
Registo de vencedor e suplentes
Convocatórias automáticas
Gestão de presenças
Atualização automática do ranking/ordenação
Relatórios pós-sorteio
Agendamento da entrega de chaves
Transição automática para área do inquilino
Estados de fecho do concurso
Histórico processual
Notificações
Auditoria
Policies
Form Requests
Controllers
Services
Views/páginas
Factories
Seeders
Testes
Documentação
PHPStan antes/depois
```

---

# 10. Fora de âmbito

Não implementar nesta sprint:

```text
Novo motor de elegibilidade
Novo motor de classificação
Nova regra substantiva de pontuação
Assinatura digital
Contrato final assinado
Pagamento de caução
Pagamento de rendas
Integração bancária
Integração externa com sorteador oficial
Integração externa com calendário municipal
Notificação SMS real sem configuração existente
Publicação automática externa
Vistoria pós-entrega
Gestão de manutenção do inquilino
```

Esta sprint completa sorteio, ordenação, fecho operacional e transição para inquilino, mas não substitui a formalização contratual quando esta exigir passos próprios.

---

# 11. Fluxos funcionais obrigatórios

## 11.1 Criação de sorteio

```text
Técnico autorizado acede ao concurso
→ Seleciona criar sorteio
→ Define tipo de sorteio
→ Define fonte dos participantes
→ Sistema valida concurso/lista/ranking
→ Sistema gera participantes elegíveis
→ Sistema cria snapshot
→ Sistema guarda sorteio em rascunho
```

## 11.2 Fecho de participantes

```text
Técnico revê participantes
→ Sistema mostra incluídos e excluídos
→ Técnico confirma lista de participantes
→ Sistema bloqueia snapshot
→ Sistema calcula hash do snapshot
→ Sorteio fica pronto para execução
```

## 11.3 Execução de sorteio auditável

```text
Técnico autorizado executa sorteio
→ Sistema valida estado
→ Sistema valida participantes fechados
→ Sistema gera seed/hash ou usa seed registada conforme configuração
→ Sistema calcula ordem sorteada
→ Sistema regista vencedor e suplentes
→ Sistema guarda resultado
→ Sistema cria auditoria
→ Sistema cria evento processual
```

## 11.4 Convocatórias automáticas

```text
Técnico seleciona sorteio/ato
→ Sistema seleciona candidatos convocáveis
→ Sistema gera convocatórias
→ Sistema cria notificações/comunicações
→ Sistema regista estado de envio
→ Candidato consulta convocatória
```

## 11.5 Gestão de presenças

```text
No ato de sorteio/atribuição
→ Técnico abre lista de convocados
→ Regista presente/ausente/justificado
→ Sistema guarda hora e utilizador responsável
→ Sistema cria histórico
→ Sistema atualiza indicadores
```

## 11.6 Registo do vencedor

```text
Sorteio concluído
→ Técnico revê resultado
→ Sistema apresenta vencedor e suplentes
→ Técnico valida vencedor
→ Sistema regista vencedor
→ Sistema associa habitação, se aplicável
→ Sistema atualiza candidatura/atribuição
→ Sistema cria timeline e auditoria
```

## 11.7 Atualização automática do ranking

```text
Resultado validado
→ Sistema cria snapshot de ranking antes
→ Sistema aplica ordenação pós-sorteio
→ Sistema cria snapshot de ranking depois
→ Sistema marca fonte da atualização
→ Sistema cria histórico
```

## 11.8 Relatório pós-sorteio

```text
Sorteio concluído
→ Técnico seleciona gerar relatório
→ Sistema agrega participantes, método, hash, resultado, presenças e validações
→ Sistema gera relatório
→ Sistema guarda em storage privado
→ Sistema audita geração/download
```

## 11.9 Agendamento da entrega de chaves

```text
Vencedor validado
→ Técnico cria agendamento de entrega de chaves
→ Sistema define data/hora/local/instruções
→ Sistema notifica candidato
→ Técnico regista conclusão da entrega
→ Sistema atualiza estado
```

## 11.10 Transição para área do inquilino

```text
Atribuição/entrega validada
→ Sistema verifica pré-condições
→ Sistema cria ou ativa perfil de inquilino
→ Sistema associa contrato/atribuição/imóvel quando existir
→ Sistema ativa área do inquilino
→ Sistema cria notificação
→ Sistema cria auditoria
```

---

# 12. Estados e tipos obrigatórios

## LotteryDrawStatus

```text
draft
participants_loaded
participants_locked
ready
running
completed
validated
cancelled
superseded
failed
```

## LotteryDrawType

```text
general
by_housing_unit
by_typology
by_priority_group
tie_breaker
reserve_list
allocation_order
```

## LotteryParticipantStatus

```text
included
excluded
withdrawn
notified
present
absent
justified_absence
winner
reserve
disqualified
```

## LotteryResultStatus

```text
draft
generated
validated
approved
cancelled
superseded
```

## ConvocationStatus

```text
draft
generated
sent
delivered
read
failed
cancelled
expired
```

## AttendanceStatus

```text
pending
present
absent
justified
late
not_required
```

## ContestClosureStatus

```text
open
pending_draw
draw_completed
allocation_completed
keys_pending
tenant_transition_pending
closed
archived
cancelled
```

## RankingUpdateStatus

```text
pending
applied
reviewed
approved
reverted
failed
```

## KeyHandoverStatus

```text
pending_schedule
scheduled
rescheduled
cancelled
completed
missed
blocked
```

## TenantTransitionStatus

```text
pending
ready
completed
blocked
failed
cancelled
```

---

# 13. Modelo de dados a implementar

## 13.1 LotteryDraw

Criar ou adaptar entidade existente:

```text
LotteryDraw
```

Tabela:

```text
lottery_draws
```

Campos mínimos:

```text
id
draw_number
contest_id
housing_unit_id nullable
ranking_snapshot_id nullable
provisional_list_id nullable
definitive_list_id nullable

type
status
title
description

participants_locked_at nullable
participants_locked_by nullable
participants_hash nullable

algorithm
seed_value nullable
seed_hash nullable
result_hash nullable

started_at nullable
completed_at nullable
validated_at nullable
validated_by nullable
cancelled_at nullable
cancelled_by nullable
cancellation_reason nullable

metadata
created_by
updated_by nullable
created_at
updated_at
deleted_at
```

Regras:

```text
draw_number deve ser único.
participants_hash deve representar snapshot fechado.
result_hash deve representar resultado final.
Sorteios validados não devem ser reexecutados.
Alterações devem criar novo run/sorteio ou superseded.
```

## 13.2 LotteryParticipant

Criar ou adaptar:

```text
LotteryParticipant
```

Tabela:

```text
lottery_participants
```

Campos:

```text
id
lottery_draw_id
application_id
user_id nullable
contest_id
housing_unit_id nullable

participant_number
status
ranking_position_before nullable
score_before nullable
priority_group nullable

included_reason nullable
excluded_reason nullable
metadata

created_at
updated_at
deleted_at
```

Regras:

```text
Não duplicar application_id no mesmo lottery_draw_id.
Participante deve estar associado a candidatura válida.
```

## 13.3 LotteryResult

Criar ou adaptar:

```text
LotteryResult
```

Tabela:

```text
lottery_results
```

Campos:

```text
id
lottery_draw_id
lottery_participant_id
application_id
user_id nullable

status
drawn_position
is_winner
is_reserve
winning_order nullable
result_payload
validated_at nullable
validated_by nullable

created_at
updated_at
deleted_at
```

Regras:

```text
drawn_position deve ser único por sorteio.
Apenas um vencedor por fogo quando sorteio for por fogo, salvo regra configurada.
```

## 13.4 DrawConvocation

Criar entidade:

```text
DrawConvocation
```

Tabela:

```text
draw_convocations
```

Campos:

```text
id
convocation_number
lottery_draw_id nullable
contest_id
application_id
user_id

status
subject
message
scheduled_for
location
instructions
sent_at nullable
read_at nullable
failed_at nullable
failure_reason nullable

created_by nullable
created_at
updated_at
deleted_at
```

## 13.5 DrawAttendance

Criar entidade:

```text
DrawAttendance
```

Tabela:

```text
draw_attendances
```

Campos:

```text
id
lottery_draw_id nullable
draw_convocation_id nullable
contest_id
application_id
user_id

status
checked_in_at nullable
checked_in_by nullable
justification nullable
notes nullable

created_at
updated_at
deleted_at
```

## 13.6 WinnerRegistration

Criar entidade:

```text
WinnerRegistration
```

Tabela:

```text
winner_registrations
```

Campos:

```text
id
winner_number
lottery_draw_id nullable
lottery_result_id nullable
contest_id
housing_unit_id nullable
application_id
user_id

status
registered_at
registered_by
allocation_id nullable
contract_id nullable
notes nullable

created_at
updated_at
deleted_at
```

Estados recomendados:

```text
draft
registered
validated
allocation_created
contract_pending
cancelled
superseded
```

## 13.7 RankingUpdateRun

Criar entidade:

```text
RankingUpdateRun
```

Tabela:

```text
ranking_update_runs
```

Campos:

```text
id
run_number
contest_id
lottery_draw_id nullable

status
source_ranking_snapshot_id nullable
result_ranking_snapshot_id nullable

changes_payload
warnings
applied_by nullable
applied_at nullable
reviewed_by nullable
reviewed_at nullable

created_at
updated_at
deleted_at
```

## 13.8 PostDrawReport

Criar entidade:

```text
PostDrawReport
```

Tabela:

```text
post_draw_reports
```

Campos:

```text
id
report_number
lottery_draw_id
contest_id

status
format
title
summary
payload
file_path nullable
generated_by
generated_at
approved_by nullable
approved_at nullable

created_at
updated_at
deleted_at
```

## 13.9 KeyHandoverAppointment

Criar ou adaptar entidade existente:

```text
KeyHandoverAppointment
```

Tabela:

```text
key_handover_appointments
```

Campos:

```text
id
appointment_number
contest_id
housing_unit_id
application_id
user_id
winner_registration_id nullable
allocation_id nullable

status
scheduled_at nullable
location
instructions
rescheduled_from_id nullable
completed_at nullable
completed_by nullable
cancelled_at nullable
cancelled_by nullable
cancellation_reason nullable
notes nullable

created_at
updated_at
deleted_at
```

## 13.10 TenantTransition

Criar entidade:

```text
TenantTransition
```

Tabela:

```text
tenant_transitions
```

Campos:

```text
id
transition_number
user_id
application_id
contest_id
housing_unit_id nullable
allocation_id nullable
contract_id nullable
tenant_id nullable

status
preconditions
warnings
started_at nullable
completed_at nullable
completed_by nullable
failed_at nullable
failure_reason nullable

created_at
updated_at
deleted_at
```

## 13.11 ContestClosure

Criar entidade:

```text
ContestClosure
```

Tabela:

```text
contest_closures
```

Campos:

```text
id
closure_number
contest_id

status
summary
closed_at nullable
closed_by nullable
archived_at nullable
archived_by nullable

lottery_draw_id nullable
list_automation_run_id nullable
procedure_minute_id nullable

metadata
created_at
updated_at
deleted_at
```

---

# 14. Índices e performance

Adicionar índices seguros:

```text
lottery_draws.draw_number unique
lottery_draws.contest_id
lottery_draws.housing_unit_id
lottery_draws.status
lottery_draws.completed_at

lottery_participants.lottery_draw_id
lottery_participants.application_id
lottery_participants.status
lottery_participants.participant_number
lottery_participants.priority_group
unique(lottery_draw_id, application_id)

lottery_results.lottery_draw_id
lottery_results.application_id
lottery_results.drawn_position
lottery_results.is_winner
lottery_results.is_reserve
unique(lottery_draw_id, drawn_position)

draw_convocations.convocation_number unique
draw_convocations.lottery_draw_id
draw_convocations.contest_id
draw_convocations.application_id
draw_convocations.user_id
draw_convocations.status
draw_convocations.scheduled_for

draw_attendances.lottery_draw_id
draw_attendances.application_id
draw_attendances.user_id
draw_attendances.status

winner_registrations.winner_number unique
winner_registrations.lottery_draw_id
winner_registrations.application_id
winner_registrations.housing_unit_id
winner_registrations.status

ranking_update_runs.run_number unique
ranking_update_runs.contest_id
ranking_update_runs.lottery_draw_id
ranking_update_runs.status

post_draw_reports.report_number unique
post_draw_reports.lottery_draw_id
post_draw_reports.contest_id
post_draw_reports.status

key_handover_appointments.appointment_number unique
key_handover_appointments.application_id
key_handover_appointments.user_id
key_handover_appointments.housing_unit_id
key_handover_appointments.status
key_handover_appointments.scheduled_at

tenant_transitions.transition_number unique
tenant_transitions.user_id
tenant_transitions.application_id
tenant_transitions.status

contest_closures.closure_number unique
contest_closures.contest_id
contest_closures.status
```

Migrations devem ser reversíveis.

Não adicionar índices duplicados.

Usar transações em sorteios, ranking update, winner registration e tenant transition.

Evitar N+1 em listagens de participantes, resultados, presenças e relatórios.

Paginar participantes e resultados quando aplicável.

---

# 15. Services obrigatórios

Criar namespaces:

```text
App\Services\Lottery
App\Services\Convocations
App\Services\Attendance
App\Services\ContestClosure
App\Services\KeyHandover
App\Services\TenantTransition
```

Criar services:

```text
App\Services\Lottery\LotteryDrawService
App\Services\Lottery\LotteryParticipantService
App\Services\Lottery\LotterySnapshotService
App\Services\Lottery\AuditableLotteryEngine
App\Services\Lottery\LotteryResultService
App\Services\Lottery\WinnerRegistrationService
App\Services\Lottery\RankingUpdateService
App\Services\Lottery\PostDrawReportService

App\Services\Convocations\DrawConvocationService
App\Services\Convocations\AutomaticConvocationService
App\Services\Convocations\ConvocationNotificationService

App\Services\Attendance\DrawAttendanceService
App\Services\Attendance\AttendanceSummaryService

App\Services\ContestClosure\ContestClosureService
App\Services\ContestClosure\ContestClosureValidator

App\Services\KeyHandover\KeyHandoverAppointmentService
App\Services\KeyHandover\KeyHandoverNotificationService

App\Services\TenantTransition\TenantTransitionService
App\Services\TenantTransition\TenantAccessProvisioningService
App\Services\TenantTransition\TenantTransitionValidator
```

## 15.1 LotteryDrawService

Responsável por:

```text
Criar sorteio
Atualizar sorteio em rascunho
Preparar sorteio
Bloquear participantes
Executar sorteio por engine auditável
Cancelar sorteio
Marcar sorteio como superseded quando aplicável
```

## 15.2 LotteryParticipantService

Responsável por:

```text
Carregar participantes a partir de ranking/lista
Validar elegibilidade para sorteio
Incluir participantes
Excluir participantes com motivo
Gerar participant_number
Listar participantes
```

## 15.3 LotterySnapshotService

Responsável por:

```text
Criar snapshot de participantes
Calcular participants_hash
Criar snapshot de resultado
Calcular result_hash
Garantir reprodutibilidade do sorteio
Guardar metadata mínima necessária
```

## 15.4 AuditableLotteryEngine

Responsável por:

```text
Executar ordenação aleatória auditável
Usar seed registada ou gerada de forma segura
Registar algoritmo
Gerar resultado determinístico quando seed é conhecida
Não alterar participantes durante execução
Gerar payload auditável
```

Requisitos mínimos:

```text
Usar randomização controlada e registável
Guardar seed_hash
Guardar algorithm
Guardar participants_hash
Guardar result_hash
Permitir auditoria posterior
Evitar reexecução silenciosa
```

Não usar `rand()` ou lógica não auditável para sorteios administrativos.

Usar fonte segura e controlada, por exemplo `random_bytes()` para seed quando não fornecida.

## 15.5 LotteryResultService

Responsável por:

```text
Persistir resultados
Determinar vencedor
Determinar suplentes
Validar duplicados
Validar número de vencedores
Gerar resultado ordenado
```

## 15.6 WinnerRegistrationService

Responsável por:

```text
Registar vencedor
Associar candidatura
Associar habitação quando aplicável
Criar ou atualizar atribuição se módulo existir
Criar histórico processual
Criar auditoria
Bloquear duplicidade de vencedor incompatível
```

## 15.7 RankingUpdateService

Responsável por:

```text
Criar snapshot antes do sorteio
Aplicar ordenação pós-sorteio
Criar snapshot depois do sorteio
Registar alterações
Permitir revisão
Impedir atualização sem resultado validado
```

## 15.8 PostDrawReportService

Responsável por:

```text
Gerar relatório pós-sorteio
Incluir participantes
Incluir presenças
Incluir método
Incluir hash/seed quando permitido
Incluir vencedor e suplentes
Guardar ficheiro privado
Auditar geração e download
```

## 15.9 DrawConvocationService

Responsável por:

```text
Criar convocatórias
Gerar número de convocatória
Associar ao sorteio/concurso/candidatura
Definir data/hora/local/instruções
Atualizar estado
```

## 15.10 AutomaticConvocationService

Responsável por:

```text
Selecionar candidatos convocáveis
Gerar convocatórias em lote
Evitar duplicados ativos
Criar notificações
Criar timeline events se existirem
```

## 15.11 ConvocationNotificationService

Responsável por:

```text
Emitir notificação interna
Criar comunicação oficial se módulo existir
Registar falhas de envio
Não enviar SMS/e-mail real sem configuração segura
```

## 15.12 DrawAttendanceService

Responsável por:

```text
Registar presença
Registar ausência
Registar justificação
Validar técnico responsável
Criar histórico
Atualizar participante/convocação quando aplicável
```

## 15.13 AttendanceSummaryService

Responsável por:

```text
Gerar resumo de presenças
Contar presentes
Contar ausentes
Contar ausências justificadas
Gerar payload para relatório pós-sorteio
```

## 15.14 ContestClosureService

Responsável por:

```text
Validar pré-condições de fecho
Registar fecho do concurso
Atualizar estado do concurso quando aplicável
Criar evento de timeline
Criar auditoria
Bloquear fecho se existirem pendências críticas
```

## 15.15 ContestClosureValidator

Responsável por validar:

```text
Sorteios por validar
Reclamações pendentes
Vencedor por registar
Entrega de chaves pendente
Transição de inquilino pendente
Relatório pós-sorteio por gerar
Ata por aprovar
Notificações críticas por enviar
```

## 15.16 KeyHandoverAppointmentService

Responsável por:

```text
Criar agendamento de entrega de chaves
Reagendar entrega
Cancelar entrega
Concluir entrega
Notificar candidato
Criar histórico
```

## 15.17 KeyHandoverNotificationService

Responsável por:

```text
Notificar agendamento
Notificar reagendamento
Notificar cancelamento
Notificar conclusão
Criar timeline event se existir
```

## 15.18 TenantTransitionService

Responsável por:

```text
Validar pré-condições
Criar perfil de inquilino se necessário
Associar user/application/allocation/contract/housing_unit
Ativar área do inquilino
Criar notificações
Criar auditoria
Bloquear transição se faltarem dados críticos
```

## 15.19 TenantAccessProvisioningService

Responsável por:

```text
Criar ou ativar role/perfil de inquilino
Garantir que o candidato mantém histórico de candidatura
Associar área do inquilino ao contrato/atribuição quando existir
Evitar duplicados
```

## 15.20 TenantTransitionValidator

Responsável por validar:

```text
Candidato é vencedor ou atribuído
Candidatura está em estado permitido
Habitação está associada
Contrato existe ou está pendente conforme regra
Documentação mínima validada
Não existe transição ativa duplicada
```

---

# 16. Controllers obrigatórios

Criar ou completar:

```text
App\Http\Controllers\Backoffice\LotteryDrawController
App\Http\Controllers\Backoffice\LotteryParticipantController
App\Http\Controllers\Backoffice\LotteryResultController
App\Http\Controllers\Backoffice\DrawConvocationController
App\Http\Controllers\Backoffice\DrawAttendanceController
App\Http\Controllers\Backoffice\WinnerRegistrationController
App\Http\Controllers\Backoffice\RankingUpdateRunController
App\Http\Controllers\Backoffice\PostDrawReportController
App\Http\Controllers\Backoffice\KeyHandoverAppointmentController
App\Http\Controllers\Backoffice\TenantTransitionController
App\Http\Controllers\Backoffice\ContestClosureController

App\Http\Controllers\Candidate\DrawConvocationController
App\Http\Controllers\Candidate\KeyHandoverAppointmentController
```

Controllers devem ser magros.

Toda lógica crítica deve ficar em Services.

Sorteios, ranking update, winner registration e tenant transition devem usar transações.

Não colocar algoritmo de sorteio diretamente em controller.

---

# 17. Form Requests obrigatórios

Criar:

```text
StoreLotteryDrawRequest
UpdateLotteryDrawRequest
LoadLotteryParticipantsRequest
LockLotteryParticipantsRequest
RunLotteryDrawRequest
ValidateLotteryResultRequest
CancelLotteryDrawRequest

GenerateDrawConvocationsRequest
StoreDrawConvocationRequest
UpdateDrawConvocationRequest
SendDrawConvocationRequest

RegisterDrawAttendanceRequest
BulkRegisterDrawAttendanceRequest

RegisterWinnerRequest
ApplyRankingUpdateRequest
GeneratePostDrawReportRequest
DownloadPostDrawReportRequest

StoreKeyHandoverAppointmentRequest
UpdateKeyHandoverAppointmentRequest
CompleteKeyHandoverAppointmentRequest
CancelKeyHandoverAppointmentRequest

RunTenantTransitionRequest
CancelTenantTransitionRequest
CloseContestRequest
```

## 17.1 StoreLotteryDrawRequest

```php
'contest_id' => ['required', 'exists:contests,id'],
'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
'type' => ['required', 'string', 'max:100'],
'title' => ['required', 'string', 'max:180'],
'description' => ['nullable', 'string', 'max:3000'],
'algorithm' => ['nullable', 'string', 'max:100'],
```

## 17.2 LoadLotteryParticipantsRequest

```php
'contest_id' => ['required', 'exists:contests,id'],
'source' => ['required', 'string', 'in:ranking,provisional_list,definitive_list,manual'],
'include_reserves' => ['nullable', 'boolean'],
```

## 17.3 RunLotteryDrawRequest

```php
'confirm_participants_locked' => ['accepted'],
'confirm_auditability' => ['accepted'],
'seed_value' => ['nullable', 'string', 'max:255'],
```

## 17.4 ValidateLotteryResultRequest

```php
'confirm_result_reviewed' => ['accepted'],
'validation_notes' => ['nullable', 'string', 'max:3000'],
```

## 17.5 CancelLotteryDrawRequest

```php
'cancellation_reason' => ['required', 'string', 'min:10', 'max:3000'],
'confirm_cancellation' => ['accepted'],
```

## 17.6 GenerateDrawConvocationsRequest

```php
'lottery_draw_id' => ['required', 'exists:lottery_draws,id'],
'scheduled_for' => ['required', 'date'],
'location' => ['required', 'string', 'max:255'],
'instructions' => ['nullable', 'string', 'max:5000'],
'participant_ids' => ['nullable', 'array'],
'participant_ids.*' => ['integer'],
```

## 17.7 RegisterDrawAttendanceRequest

```php
'application_id' => ['required', 'exists:applications,id'],
'status' => ['required', 'string', 'max:50'],
'justification' => ['nullable', 'string', 'max:3000'],
'notes' => ['nullable', 'string', 'max:3000'],
```

## 17.8 RegisterWinnerRequest

```php
'lottery_result_id' => ['required', 'exists:lottery_results,id'],
'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
'confirm_winner_validation' => ['accepted'],
'notes' => ['nullable', 'string', 'max:3000'],
```

## 17.9 StoreKeyHandoverAppointmentRequest

```php
'application_id' => ['required', 'exists:applications,id'],
'housing_unit_id' => ['required', 'exists:housing_units,id'],
'scheduled_at' => ['required', 'date'],
'location' => ['required', 'string', 'max:255'],
'instructions' => ['nullable', 'string', 'max:5000'],
```

## 17.10 CompleteKeyHandoverAppointmentRequest

```php
'confirm_handover_completed' => ['accepted'],
'notes' => ['nullable', 'string', 'max:3000'],
```

## 17.11 RunTenantTransitionRequest

```php
'application_id' => ['required', 'exists:applications,id'],
'housing_unit_id' => ['nullable', 'exists:housing_units,id'],
'allocation_id' => ['nullable', 'integer'],
'confirm_transition' => ['accepted'],
```

## 17.12 CloseContestRequest

```php
'contest_id' => ['required', 'exists:contests,id'],
'confirm_no_pending_critical_actions' => ['accepted'],
'notes' => ['nullable', 'string', 'max:3000'],
```

---

# 18. Policies obrigatórias

Criar ou completar:

```text
LotteryDrawPolicy
LotteryParticipantPolicy
LotteryResultPolicy
DrawConvocationPolicy
DrawAttendancePolicy
WinnerRegistrationPolicy
RankingUpdateRunPolicy
PostDrawReportPolicy
KeyHandoverAppointmentPolicy
TenantTransitionPolicy
ContestClosurePolicy
```

Regras:

```text
Guest não acede a sorteios.
Candidato só vê convocatórias próprias.
Candidato só vê entrega de chaves própria.
Candidato não executa sorteio.
Candidato não altera presença diretamente, salvo confirmação própria se existir regra.
Técnico autorizado cria e prepara sorteio.
Apenas perfil autorizado executa sorteio.
Apenas perfil autorizado valida resultado.
Apenas perfil autorizado regista vencedor.
Apenas perfil autorizado atualiza ranking.
Apenas perfil autorizado fecha concurso.
Auditor consulta sem alterar.
Admin pode cancelar/supersede sorteio conforme regra.
Downloads de relatórios pós-sorteio exigem autorização.
```

Nunca confiar apenas no frontend para esconder ações.

---

# 19. Rotas de backoffice obrigatórias

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('backoffice')->name('backoffice.')->group(function (): void {
    Route::resource('/sorteios', LotteryDrawController::class)->names('lottery-draws');

    Route::post('/sorteios/{lotteryDraw}/participantes/carregar', [LotteryParticipantController::class, 'load'])
        ->name('lottery-draws.participants.load');
    Route::post('/sorteios/{lotteryDraw}/participantes/bloquear', [LotteryParticipantController::class, 'lock'])
        ->name('lottery-draws.participants.lock');

    Route::post('/sorteios/{lotteryDraw}/executar', [LotteryDrawController::class, 'run'])
        ->name('lottery-draws.run');
    Route::post('/sorteios/{lotteryDraw}/validar', [LotteryDrawController::class, 'validateResult'])
        ->name('lottery-draws.validate');
    Route::post('/sorteios/{lotteryDraw}/cancelar', [LotteryDrawController::class, 'cancel'])
        ->name('lottery-draws.cancel');

    Route::get('/sorteios/{lotteryDraw}/resultados', [LotteryResultController::class, 'index'])
        ->name('lottery-draws.results.index');

    Route::post('/sorteios/{lotteryDraw}/convocatorias/gerar', [DrawConvocationController::class, 'generate'])
        ->name('lottery-draws.convocations.generate');
    Route::get('/convocatorias-sorteio', [DrawConvocationController::class, 'index'])
        ->name('draw-convocations.index');
    Route::post('/convocatorias-sorteio/{drawConvocation}/enviar', [DrawConvocationController::class, 'send'])
        ->name('draw-convocations.send');

    Route::get('/sorteios/{lotteryDraw}/presencas', [DrawAttendanceController::class, 'index'])
        ->name('lottery-draws.attendance.index');
    Route::post('/sorteios/{lotteryDraw}/presencas', [DrawAttendanceController::class, 'store'])
        ->name('lottery-draws.attendance.store');
    Route::post('/sorteios/{lotteryDraw}/presencas/lote', [DrawAttendanceController::class, 'bulkStore'])
        ->name('lottery-draws.attendance.bulk-store');

    Route::post('/resultados-sorteio/{lotteryResult}/vencedor', [WinnerRegistrationController::class, 'store'])
        ->name('lottery-results.winner.store');

    Route::post('/sorteios/{lotteryDraw}/ranking/atualizar', [RankingUpdateRunController::class, 'apply'])
        ->name('lottery-draws.ranking.update');

    Route::post('/sorteios/{lotteryDraw}/relatorio-pos-sorteio/gerar', [PostDrawReportController::class, 'generate'])
        ->name('lottery-draws.post-draw-report.generate');
    Route::get('/relatorios-pos-sorteio/{postDrawReport}/download', [PostDrawReportController::class, 'download'])
        ->name('post-draw-reports.download');

    Route::get('/entrega-chaves', [KeyHandoverAppointmentController::class, 'index'])
        ->name('key-handovers.index');
    Route::post('/entrega-chaves', [KeyHandoverAppointmentController::class, 'store'])
        ->name('key-handovers.store');
    Route::post('/entrega-chaves/{keyHandoverAppointment}/concluir', [KeyHandoverAppointmentController::class, 'complete'])
        ->name('key-handovers.complete');
    Route::post('/entrega-chaves/{keyHandoverAppointment}/cancelar', [KeyHandoverAppointmentController::class, 'cancel'])
        ->name('key-handovers.cancel');

    Route::post('/transicoes-inquilino', [TenantTransitionController::class, 'run'])
        ->name('tenant-transitions.run');
    Route::get('/transicoes-inquilino', [TenantTransitionController::class, 'index'])
        ->name('tenant-transitions.index');

    Route::post('/concursos/{contest}/fechar', [ContestClosureController::class, 'close'])
        ->name('contests.close');
});
```

Todas as rotas devem respeitar middleware, policies e convenções existentes.

---

# 20. Rotas da área do candidato obrigatórias

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('area-candidato')->name('candidate.')->group(function (): void {
    Route::get('/convocatorias', [DrawConvocationController::class, 'index'])
        ->name('draw-convocations.index');

    Route::get('/convocatorias/{drawConvocation}', [DrawConvocationController::class, 'show'])
        ->name('draw-convocations.show');

    Route::post('/convocatorias/{drawConvocation}/confirmar-leitura', [DrawConvocationController::class, 'markRead'])
        ->name('draw-convocations.mark-read');

    Route::get('/entrega-chaves', [KeyHandoverAppointmentController::class, 'index'])
        ->name('key-handovers.index');

    Route::get('/entrega-chaves/{keyHandoverAppointment}', [KeyHandoverAppointmentController::class, 'show'])
        ->name('key-handovers.show');
});
```

Todas as rotas devem respeitar middleware, policies e convenções existentes.

---

# 21. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

```text
resources/views/backoffice/lottery-draws/index.blade.php
resources/views/backoffice/lottery-draws/create.blade.php
resources/views/backoffice/lottery-draws/edit.blade.php
resources/views/backoffice/lottery-draws/show.blade.php
resources/views/backoffice/lottery-draws/participants.blade.php
resources/views/backoffice/lottery-draws/results.blade.php
resources/views/backoffice/lottery-draws/attendance.blade.php

resources/views/backoffice/draw-convocations/index.blade.php
resources/views/backoffice/draw-convocations/show.blade.php

resources/views/backoffice/post-draw-reports/show.blade.php

resources/views/backoffice/key-handovers/index.blade.php
resources/views/backoffice/key-handovers/create.blade.php
resources/views/backoffice/key-handovers/show.blade.php

resources/views/backoffice/tenant-transitions/index.blade.php
resources/views/backoffice/contest-closures/show.blade.php

resources/views/candidate/draw-convocations/index.blade.php
resources/views/candidate/draw-convocations/show.blade.php
resources/views/candidate/key-handovers/index.blade.php
resources/views/candidate/key-handovers/show.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes.

Não mudar stack frontend.

Não misturar Blade e Inertia se o projeto já tiver uma stack definida.

---

# 22. UX obrigatória

## 22.1 Backoffice de sorteios

Mostrar:

```text
Concurso
Tipo de sorteio
Estado
Fonte dos participantes
Número de participantes
Hash dos participantes
Algoritmo
Seed/hash
Resultado/hash
Vencedor
Suplentes
Ações disponíveis
Histórico
Warnings
```

## 22.2 Participantes

Mostrar:

```text
Número de participante
Candidatura
Estado
Posição anterior no ranking
Pontuação anterior
Grupo/prioridade
Incluído/excluído
Motivo de exclusão
```

## 22.3 Resultado

Mostrar:

```text
Posição sorteada
Candidato/candidatura conforme permissão
Vencedor
Suplente
Estado de validação
Hash do resultado
Data/hora
Técnico responsável
```

## 22.4 Convocatórias

Mostrar:

```text
Candidato
Candidatura
Data/hora
Local
Instruções
Estado de envio
Estado de leitura
Ações de envio/reenvio/cancelamento
```

## 22.5 Presenças

Mostrar:

```text
Lista de convocados
Estado de presença
Hora de check-in
Técnico responsável
Justificação
Notas
Resumo de presenças
```

## 22.6 Relatório pós-sorteio

Mostrar:

```text
Dados do concurso
Dados do sorteio
Método usado
Participantes
Presenças
Resultado
Vencedor
Suplentes
Hash/seed quando permitido
Data de geração
Responsável
Download autorizado
```

## 22.7 Entrega de chaves

Mostrar:

```text
Candidato vencedor
Habitação
Data/hora
Local
Instruções
Estado
Reagendamento/cancelamento
Conclusão
Notas internas autorizadas
```

## 22.8 Transição para inquilino

Mostrar:

```text
Candidato
Candidatura
Habitação
Atribuição
Contrato associado, se existir
Pré-condições
Warnings
Estado da transição
Ação de executar
Histórico
```

---

# 23. Regras de sorteio auditável

Regras obrigatórias:

```text
Sorteio deve ter participantes fechados.
Participantes fechados devem gerar hash.
Sorteio deve registar algoritmo.
Sorteio deve registar seed_hash.
Resultado deve gerar result_hash.
Resultado deve ser persistido.
Resultado validado não pode ser reexecutado.
Nova execução após validação deve criar novo sorteio/run ou marcar anterior como superseded.
Todos os passos críticos devem gerar auditoria.
```

Engine mínima:

```text
Recebe participantes ordenados por participant_number/application_id.
Recebe seed ou gera seed segura.
Mistura participantes de forma determinística quando seed é fornecida.
Gera drawn_position.
Define winner/reserve conforme regra.
Retorna payload auditável.
```

Não usar `rand()` ou lógica não auditável para sorteios administrativos.

Usar fonte segura e controlada, por exemplo `random_bytes()` para seed quando não fornecida.

---

# 24. Regras de convocatórias

Regras obrigatórias:

```text
Convocatória deve estar associada ao concurso e candidato.
Convocatória deve ter data/hora/local.
Convocatória deve ter estado.
Convocatória deve criar notificação se módulo existir.
Convocatória deve criar timeline event se módulo existir.
Não criar convocatórias duplicadas ativas para o mesmo candidato/ato.
Candidato só vê convocatórias próprias.
```

---

# 25. Regras de presenças

Regras obrigatórias:

```text
Presença deve estar associada a convocatória/sorteio/candidatura.
Presença deve registar técnico responsável.
Alterações de presença devem ser auditadas.
Ausência justificada deve guardar justificação.
Presença não deve alterar resultado do sorteio sem regra explícita.
```

---

# 26. Regras de vencedor e ranking

Regras obrigatórias:

```text
Vencedor deve resultar de sorteio validado ou regra de atribuição existente.
Registo de vencedor deve ser transacional.
Não permitir dois vencedores incompatíveis para a mesma habitação.
Não permitir vencedor para candidatura retirada/cancelada/inválida.
Atualização de ranking deve criar snapshot antes e depois.
Atualização de ranking deve ser auditável.
Ranking anterior não deve ser apagado.
```

---

# 27. Regras de relatórios pós-sorteio

Regras obrigatórias:

```text
Relatório deve ser gerado por utilizador autorizado.
Relatório deve conter método e rastreabilidade.
Relatório com dados pessoais deve ficar privado.
Download deve passar por controller autorizado.
Relatório deve registar auditoria.
Relatório deve indicar data/hora de geração.
Relatório não substitui validação administrativa.
```

Formatos:

```text
HTML obrigatório
PDF se infraestrutura existir
CSV/XLSX opcional
Fallback documentado se PDF real não existir
```

---

# 28. Regras de entrega de chaves

Regras obrigatórias:

```text
Entrega de chaves só para vencedor/atribuído.
Agendamento deve ter data/hora/local.
Agendamento deve permitir reagendamento/cancelamento/conclusão.
Conclusão deve registar técnico responsável.
Conclusão deve criar timeline event e auditoria.
Candidato só vê os seus agendamentos.
```

---

# 29. Regras de transição para área do inquilino

Regras obrigatórias:

```text
Transição só para candidato vencedor/atribuído.
Transição deve validar pré-condições.
Transição não deve apagar dados de candidato.
Transição deve criar ou ativar perfil de inquilino.
Transição deve associar habitação/atribuição/contrato quando existir.
Transição deve evitar duplicados.
Transição deve criar notificação e auditoria.
Área do inquilino não deve mostrar módulos sem dados existentes.
```

Pré-condições recomendadas:

```text
Candidatura atribuída
Vencedor validado
Habitação associada
Documentos finais mínimos validados quando aplicável
Contrato criado ou pendente conforme regra
Entrega de chaves agendada/concluída conforme regra municipal
```

---

# 30. Regras de fecho do concurso

Regras obrigatórias:

```text
Concurso só fecha se não existirem pendências críticas.
Fecho deve validar listas, sorteios, atribuições e comunicações.
Fecho deve criar snapshot/resumo.
Fecho deve criar timeline/procedure event se existir.
Fecho deve ser auditado.
Fecho não deve apagar dados.
Fecho deve ser reversível apenas por perfil autorizado, se aplicável.
```

Pendências críticas possíveis:

```text
Sorteio por validar
Reclamações pendentes
Vencedor não registado
Entrega de chaves pendente
Transição de inquilino pendente
Relatório pós-sorteio não gerado
Ata por aprovar
Notificações críticas por enviar
```

---

# 31. Notificações

Se Sprint 16 existir, emitir notificações para:

```text
Convocatória gerada
Convocatória enviada
Sorteio concluído
Resultado validado
Candidato vencedor
Candidato suplente
Entrega de chaves agendada
Entrega de chaves reagendada
Entrega de chaves cancelada
Entrega de chaves concluída
Transição para área do inquilino concluída
Concurso fechado
```

Não enviar e-mail/SMS real sem configuração segura.

Se notificações não existirem, criar eventos internos ou documentar pendência.

---

# 32. Auditoria e RGPD

Auditar, se existir auditoria:

```text
Criação de sorteio
Carregamento de participantes
Bloqueio de participantes
Execução de sorteio
Validação de resultado
Cancelamento de sorteio
Geração de convocatórias
Envio de convocatórias
Registo de presença
Alteração de presença
Registo de vencedor
Atualização de ranking
Geração de relatório pós-sorteio
Download de relatório pós-sorteio
Agendamento de entrega de chaves
Conclusão de entrega de chaves
Transição para área do inquilino
Fecho do concurso
```

RGPD:

```text
Não expor candidatos de terceiros.
Não expor relatórios privados por URL público.
Não guardar dados pessoais desnecessários nos hashes.
Não guardar seed pública se regra exigir reserva.
Não mostrar dados pessoais no resultado público sem permissão.
Não guardar dados sensíveis em logs técnicos.
Mascarar dados quando o perfil não tem permissão.
```

---

# 33. Factories e seeders

Criar factories:

```text
LotteryDrawFactory
LotteryParticipantFactory
LotteryResultFactory
DrawConvocationFactory
DrawAttendanceFactory
WinnerRegistrationFactory
RankingUpdateRunFactory
PostDrawReportFactory
KeyHandoverAppointmentFactory
TenantTransitionFactory
ContestClosureFactory
```

Criar seeder opcional:

```text
Database\Seeders\LotteryClosureDemoSeeder
```

Dados fictícios:

```text
Concurso com lista definitiva
Ranking com candidatos
Sorteio em rascunho
Sorteio com participantes bloqueados
Sorteio concluído
Vencedor registado
Convocatórias geradas
Presenças registadas
Relatório pós-sorteio
Entrega de chaves agendada
Transição para inquilino pendente/concluída
Concurso fechado
```

Não usar dados reais.

---

# 34. Testes obrigatórios

Criar ou completar testes.

## 34.1 Sorteios auditáveis

```text
tests/Feature/Backoffice/LotteryDrawTest.php
tests/Unit/Lottery/AuditableLotteryEngineTest.php
tests/Unit/Lottery/LotterySnapshotServiceTest.php
tests/Unit/Lottery/LotteryDrawServiceTest.php
```

Cobrir:

```text
Técnico autorizado cria sorteio
Participantes são carregados
Participantes são bloqueados
Hash de participantes é gerado
Sorteio não executa sem participantes bloqueados
Sorteio executa e gera resultado
Sorteio com mesma seed e participantes gera mesmo resultado
Sorteio validado não pode ser reexecutado
Cancelamento exige motivo
Utilizador sem permissão não executa sorteio
```

## 34.2 Participantes e resultados

```text
tests/Feature/Backoffice/LotteryParticipantTest.php
tests/Feature/Backoffice/LotteryResultTest.php
tests/Unit/Lottery/LotteryParticipantServiceTest.php
tests/Unit/Lottery/LotteryResultServiceTest.php
```

Cobrir:

```text
Participante duplicado no mesmo sorteio é impedido
Participante inválido é excluído com motivo
Resultado tem drawn_position único
Vencedor é identificado
Suplentes são identificados
Resultado gera result_hash
```

## 34.3 Convocatórias e presenças

```text
tests/Feature/Backoffice/DrawConvocationTest.php
tests/Feature/Backoffice/DrawAttendanceTest.php
tests/Feature/Candidate/DrawConvocationVisibilityTest.php
tests/Unit/Convocations/AutomaticConvocationServiceTest.php
tests/Unit/Attendance/DrawAttendanceServiceTest.php
```

Cobrir:

```text
Convocatórias são geradas
Convocatórias duplicadas ativas são impedidas
Candidato vê apenas convocatórias próprias
Técnico regista presença
Técnico regista ausência justificada
Alteração de presença é auditada se existir auditoria
```

## 34.4 Registo de vencedor e ranking

```text
tests/Feature/Backoffice/WinnerRegistrationTest.php
tests/Feature/Backoffice/RankingUpdateRunTest.php
tests/Unit/Lottery/WinnerRegistrationServiceTest.php
tests/Unit/Lottery/RankingUpdateServiceTest.php
```

Cobrir:

```text
Vencedor é registado a partir de resultado validado
Não permite vencedor de resultado não validado
Não permite dois vencedores incompatíveis para a mesma habitação
Ranking é atualizado após sorteio validado
Snapshot antes/depois é criado
Ranking anterior não é apagado
```

## 34.5 Relatórios pós-sorteio

```text
tests/Feature/Backoffice/PostDrawReportTest.php
tests/Unit/Lottery/PostDrawReportServiceTest.php
```

Cobrir:

```text
Relatório pós-sorteio é gerado
Relatório inclui método/hash/resultado
Relatório fica privado
Download exige autorização
Geração cria auditoria se existir
```

## 34.6 Entrega de chaves

```text
tests/Feature/Backoffice/KeyHandoverAppointmentTest.php
tests/Feature/Candidate/KeyHandoverAppointmentVisibilityTest.php
tests/Unit/KeyHandover/KeyHandoverAppointmentServiceTest.php
```

Cobrir:

```text
Entrega de chaves é agendada para vencedor
Não agenda entrega para candidatura não vencedora
Candidato vê apenas entrega própria
Entrega pode ser reagendada
Entrega pode ser cancelada
Entrega pode ser concluída por técnico autorizado
Conclusão cria timeline/auditoria se existir
```

## 34.7 Transição para inquilino

```text
tests/Feature/Backoffice/TenantTransitionTest.php
tests/Unit/TenantTransition/TenantTransitionServiceTest.php
tests/Unit/TenantTransition/TenantTransitionValidatorTest.php
```

Cobrir:

```text
Transição é criada para vencedor validado
Transição bloqueia candidato sem atribuição
Transição evita duplicados
Transição cria ou ativa perfil de inquilino
Transição associa habitação/atribuição/contrato se existir
Transição cria notificação/auditoria se existir
```

## 34.8 Fecho do concurso

```text
tests/Feature/Backoffice/ContestClosureTest.php
tests/Unit/ContestClosure/ContestClosureServiceTest.php
tests/Unit/ContestClosure/ContestClosureValidatorTest.php
```

Cobrir:

```text
Concurso fecha sem pendências críticas
Concurso não fecha com sorteio por validar
Concurso não fecha com reclamações pendentes se módulo existir
Concurso não fecha sem vencedor quando obrigatório
Fecho cria auditoria
Fecho não apaga dados
```

## 34.9 Segurança/RGPD

```text
tests/Feature/Security/LotteryClosurePrivacyTest.php
```

Cobrir:

```text
Guest não acede a sorteios
Candidato não acede ao backoffice de sorteios
Candidato não vê convocatória de terceiro
Candidato não vê entrega de chaves de terceiro
Relatório pós-sorteio privado não é acessível por URL público
Utilizador sem permissão não executa sorteio
Utilizador sem permissão não valida resultado
Mass assignment de status é bloqueado
Mass assignment de validated_by é bloqueado
Mass assignment de winner flags é bloqueado
```

---

# 35. PHPStan específico da Sprint 25

Após implementar testes e código:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint25-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint25-after.txt || true
```

Verificar especialmente ficheiros novos:

```text
app/Models/LotteryDraw.php
app/Models/LotteryParticipant.php
app/Models/LotteryResult.php
app/Models/DrawConvocation.php
app/Models/DrawAttendance.php
app/Models/WinnerRegistration.php
app/Models/RankingUpdateRun.php
app/Models/PostDrawReport.php
app/Models/KeyHandoverAppointment.php
app/Models/TenantTransition.php
app/Models/ContestClosure.php
app/Services/Lottery/*
app/Services/Convocations/*
app/Services/Attendance/*
app/Services/ContestClosure/*
app/Services/KeyHandover/*
app/Services/TenantTransition/*
app/Http/Controllers/Backoffice/*
app/Http/Controllers/Candidate/*
app/Http/Requests/*
tests/Feature/*
tests/Unit/*
```

Corrigir:

```text
missingType.generics
missingType.iterableValue
argument.type
return.type
property.notFound
method.notFound
enum/value type mismatch
invalid relation generics
```

Em relações Eloquent, usar PHPDoc generics corretos:

```php
/** @return BelongsTo<Contest, LotteryDraw> */
public function contest(): BelongsTo
{
    return $this->belongsTo(Contest::class);
}
```

Em arrays estruturados, usar PHPDoc:

```php
/** @return array{algorithm: string, participants_hash: string, result_hash: string, winner_application_id: int|null} */
```

Não adicionar `mixed` sem necessidade.

---

# 36. Comandos obrigatórios finais

Executar:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se o projeto usar frontend build:

```bash
npm run build
```

Se o projeto usar Pint:

```bash
./vendor/bin/pint
```

Se existir PHPStan:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse
```

Se existir Psalm:

```bash
./vendor/bin/psalm
```

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
Bloqueia publicação: sim/não
```

Não afirmar que comandos passaram se não foram executados.

Não ocultar erros.

---

# 37. Documentação obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-25-sorteios-ordenacao-fecho-concurso.md
docs/backoffice/lottery-draws.md
docs/backoffice/draw-convocations.md
docs/backoffice/draw-attendance.md
docs/backoffice/winner-registration.md
docs/backoffice/ranking-update-after-draw.md
docs/backoffice/post-draw-reports.md
docs/backoffice/key-handover.md
docs/backoffice/tenant-transition.md
docs/backoffice/contest-closure.md
docs/qa/test-coverage-matrix.md
docs/qa/sprint-25-quality-report.md
docs/backlog/roadmap.md
```

## docs/backoffice/lottery-draws.md

Incluir:

```text
Objetivo
Estados
Participantes
Hash dos participantes
Algoritmo
Seed/hash
Resultado
Validação
Cancelamento
Auditoria
Limitações
```

## docs/backoffice/draw-convocations.md

Incluir:

```text
Objetivo
Fluxo
Estados
Notificações
Leitura pelo candidato
Permissões
Limitações
```

## docs/backoffice/draw-attendance.md

Incluir:

```text
Objetivo
Estados de presença
Check-in
Ausência
Justificação
Auditoria
Limitações
```

## docs/backoffice/winner-registration.md

Incluir:

```text
Objetivo
Fonte do vencedor
Regras
Associação a habitação
Associação a atribuição
Auditoria
Limitações
```

## docs/backoffice/ranking-update-after-draw.md

Incluir:

```text
Objetivo
Snapshots antes/depois
Alterações
Warnings
Revisão
Auditoria
Limitações
```

## docs/backoffice/post-draw-reports.md

Incluir:

```text
Objetivo
Conteúdo
Formatos
Storage privado
Download autorizado
Auditoria
Limitações
```

## docs/backoffice/key-handover.md

Incluir:

```text
Objetivo
Agendamento
Reagendamento
Cancelamento
Conclusão
Notificações
Limitações
```

## docs/backoffice/tenant-transition.md

Incluir:

```text
Objetivo
Pré-condições
Criação/ativação de inquilino
Associações
Warnings
Auditoria
Limitações
```

## docs/backoffice/contest-closure.md

Incluir:

```text
Objetivo
Pré-condições
Pendências críticas
Fecho
Arquivo
Auditoria
Limitações
```

## docs/qa/sprint-25-quality-report.md

Incluir:

```text
PHPStan inicial
PHPStan final
Erros legados assumidos: 2471
Erros novos introduzidos: sim/não
Testes executados
Funcionalidades concluídas
Funcionalidades pendentes
Riscos RGPD
Riscos funcionais
Riscos de publicação
```

---

# 38. Critérios de aceitação

A Sprint 25 está concluída quando:

```text
Existe módulo de sorteios auditáveis.
Sorteios têm participantes fechados.
Sorteios geram hash de participantes.
Sorteios registam algoritmo e seed/hash.
Sorteios geram resultado auditável.
Sorteio validado não pode ser reexecutado silenciosamente.
Convocatórias automáticas existem.
Candidato vê apenas convocatórias próprias.
Gestão de presença existe.
Presenças são auditáveis.
Vencedor pode ser registado.
Vencedor é associado a candidatura/habitação/atribuição quando aplicável.
Ranking é atualizado após sorteio com snapshot antes/depois.
Relatório pós-sorteio pode ser gerado.
Relatório pós-sorteio fica privado.
Entrega de chaves pode ser agendada.
Candidato vê apenas entrega própria.
Entrega pode ser concluída por técnico autorizado.
Transição para área do inquilino existe.
Transição valida pré-condições.
Transição evita duplicados.
Fecho do concurso existe.
Fecho bloqueia pendências críticas.
Notificações são emitidas se módulo existir.
Timeline é atualizada se módulo existir.
Auditoria é criada se módulo existir.
Dados pessoais não são expostos indevidamente.
PHPStan foi executado antes da implementação, se disponível.
PHPStan foi executado antes da publicação, se disponível.
Foram considerados os 2471 erros legados.
Sprint 25 não introduz erros PHPStan novos nos ficheiros alterados.
php artisan route:list executa sem erro.
php artisan test executa sem erro ou falhas são documentadas.
npm run build executa sem erro ou falhas são documentadas.
./vendor/bin/pint executa sem erro ou alterações são documentadas.
Documentação foi criada/atualizada.
Não foram usadas credenciais.
Não foram usados dados pessoais reais.
Não foram implementadas funcionalidades fora de âmbito.
```

---

# 39. Resposta final obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Estado PHPStan inicial
4. Estado PHPStan antes de publicação
5. Erros PHPStan legados considerados: 2471
6. Novos erros PHPStan introduzidos pela Sprint 25: sim/não
7. Models criados ou alterados
8. Migrations criadas
9. Services criados ou alterados
10. Controllers criados ou alterados
11. Form Requests criados ou alterados
12. Policies criadas ou alteradas
13. Rotas de backoffice criadas ou alteradas
14. Rotas da área do candidato criadas ou alteradas
15. Views/components criados ou alterados
16. Estado dos sorteios auditáveis
17. Estado das convocatórias automáticas
18. Estado da gestão de presença
19. Estado do registo do vencedor
20. Estado da atualização automática do ranking
21. Estado dos relatórios pós-sorteio
22. Estado do agendamento da entrega de chaves
23. Estado da transição para área do inquilino
24. Estado do fecho do concurso
25. Estado das notificações/timeline/auditoria
26. Testes criados ou alterados
27. Resultado de php artisan route:list
28. Resultado de php artisan test
29. Resultado de php artisan migrate, se aplicável
30. Resultado de npm run build, se aplicável
31. Resultado de ./vendor/bin/pint, se aplicável
32. Resultado de PHPStan/Psalm, se aplicável
33. Riscos ainda existentes
34. Pendências técnicas
35. Confirmação de que não foram usados dados pessoais reais
36. Confirmação de que não foram usadas credenciais
37. Confirmação de que não foram implementadas funcionalidades fora de âmbito
38. Recomendação objetiva para avançar ou não para Sprint 26
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 40. Definition of Done

A Sprint 25 só está concluída quando existir um módulo completo de sorteios auditáveis, convocatórias automáticas, gestão de presença, registo do vencedor, atualização automática do ranking, relatórios pós-sorteio, agendamento de entrega de chaves, transição automática para área do inquilino e fecho do concurso, com permissões, RGPD, auditoria, testes e validação PHPStan sem aumento do passivo legado de 2471 erros.

---

# 41. Execução imediata

Executa agora apenas:

```text
Sprint 25 — Sorteios, Ordenação e Fecho do Concurso
```

Usa como referência principal:

```text
docs/backlog/sprint-25-sorteios-ordenacao-fecho-concurso.md
```

Fim da master prompt da Sprint 25.
