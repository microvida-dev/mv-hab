# MASTER PROMPT — EXECUÇÃO DA SPRINT 21: SIMULADOR AVANÇADO E REGISTO INTELIGENTE

Atua como arquiteto sénior Laravel, tech lead, product engineer e especialista em plataformas públicas de Habitação/Arrendamento Acessível.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 21 — Simulador Avançado e Registo Inteligente
```

Esta sprint pertence à fase de melhoria da experiência do candidato antes da candidatura formal.

A Sprint 21 deve enriquecer o simulador, melhorar o registo de adesão, permitir reutilização de dados, identificar impedimentos, recomendar concursos compatíveis, estimar tipologia/renda e preparar o pré-preenchimento da candidatura sem substituir a análise formal dos serviços municipais.

---

# 1. Regra principal

Executa apenas a Sprint 21.

Não avances para Sprint 22, Sprint 23 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-21-simulador-avancado-registo-inteligente.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 21.

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

docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/sprint-5-agregado-rendimentos-situacao-habitacional.md
docs/backlog/sprint-6-documentacao-gestao-documental-avancada.md
docs/backlog/sprint-7-motor-elegibilidade.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/backlog/sprint-20-portal-publico-oferta-habitacional.md
docs/backlog/sprint-21-simulador-avancado-registo-inteligente.md

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
Sistema de registo
Sistema de área pessoal do candidato
Sistema de roles/permissões
Sistema de policies
Sistema de Form Requests
Sistema de notificações
Sistema de auditoria
Sistema de RGPD/consentimentos
Sistema de eligibility engine
Sistema de scoring engine
Sistema de rent calculation engine
Sistema de contests/programs
Sistema de public portal
Sistema de candidatura
Sistema documental
Sistema de storage privado
Sistema de testes
Configuração PHPStan/Larastan
Configuração Pint
Configuração PHPUnit/Pest
Comandos disponíveis em composer.json
Comandos disponíveis em package.json
```

Inspeciona os modelos existentes:

```text
User
Citizen/Candidate, se existir
AdhesionRegistration
Household
HouseholdMember
IncomeRecord
CurrentHousingSituation
DocumentType
RequiredDocument
DocumentSubmission
Program
Contest
ContestHousingUnit
HousingUnit
EligibilityRuleSet
EligibilityCriterion
EligibilityCheck
Application
ApplicationSnapshot
ApplicationPreference
RentRuleSet
RentCalculation
Allocation
AuditEvent
OfficialNotification
ConsentPurpose
UserConsent
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
EligibilitySimulation
SimulationSession
SimulatorResult
RegistrationRenewal
CandidateProfileSnapshot
CandidateDataReuseProfile
ApplicationPrefill
ContestRecommendation
TypologySimulation
RentEstimate
ImpedimentCheck
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não usar integrações externas.

Não criar regras legais rígidas sem configuração.

Não substituir o motor formal de elegibilidade, classificação ou renda; esta sprint deve reutilizar os motores existentes sempre que possível.

---

# 5. PHPStan obrigatório antes de publicar — contexto com 2471 erros legados

O projeto tem atualmente:

```text
2471 erros PHPStan legados
```

A Sprint 21 não tem como objetivo corrigir todos os erros legados.

A Sprint 21 tem como objetivo obrigatório:

```text
Não aumentar o número de erros PHPStan.
Não introduzir novos erros PHPStan nos ficheiros criados ou alterados.
Identificar claramente erros legados versus erros introduzidos pela sprint.
Executar PHPStan antes da implementação e antes da publicação.
Corrigir todos os erros PHPStan diretamente causados pela Sprint 21.
```

## 5.1 Verificação PHPStan inicial

Antes de criar ou alterar ficheiros, executar, se PHPStan existir:

```bash
mkdir -p storage/phpstan

php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint21-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint21-before.txt || true
```

Se existir `phpstan.neon`, usar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint21-before.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint21-before.txt || true
```

Se o projeto tiver script no `composer.json`, usar também o comando do projeto, por exemplo:

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
Não corrigir erros PHPStan fora do âmbito da Sprint 21, salvo se bloquearem diretamente a sprint.
Não alterar ficheiros apenas para reduzir ruído PHPStan legado.
Não criar baseline artificial sem autorização.
Não esconder erros novos com ignoreErrors genéricos.
Não adicionar @phpstan-ignore sem justificação objetiva.
Não reduzir o nível do PHPStan.
Não remover paths analisados.
Não alterar configuração PHPStan para ocultar problemas.
```

## 5.3 Verificação PHPStan antes de publicação

Antes de considerar a Sprint 21 pronta para publicação, executar:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint21-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint21-after.txt || true
```

Com config, se existir:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint21-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint21-after.txt || true
```

Depois, identificar erros nos ficheiros criados ou alterados nesta sprint.

Se existirem erros PHPStan em ficheiros da Sprint 21:

```text
Corrigir antes de concluir.
Não publicar como concluído enquanto houver erro novo causado pela Sprint 21.
```

Se existirem apenas os 2471 erros legados:

```text
Documentar que o passivo PHPStan legado permanece.
Confirmar que a Sprint 21 não adicionou erros novos nos ficheiros alterados.
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
Novos erros introduzidos pela Sprint 21: sim/não
Erros PHPStan em ficheiros criados/alterados: sim/não
Correções PHPStan aplicadas
Bloqueia publicação: sim/não
```

---

# 6. Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 5 — Agregado Familiar, Rendimentos e Situação Habitacional
Sprint 7 — Motor de Elegibilidade
Sprint 8 — Candidaturas e Submissão Formal
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 20 — Portal Público de Oferta Habitacional
```

Dependências mínimas:

```text
User
AdhesionRegistration ou equivalente
Contest ou equivalente
Application ou equivalente
```

Se o motor formal de elegibilidade da Sprint 7 existir:

```text
Reutilizar o motor formal em modo simulação.
Não duplicar regras.
Não criar motor paralelo incompatível.
```

Se o motor de renda da Sprint 13 existir:

```text
Reutilizar regras configuráveis de renda para estimativa.
Indicar que a estimativa é não vinculativa.
```

Se o portal público da Sprint 20 existir:

```text
Usar concursos e imóveis publicados para recomendações.
Não recomendar concursos escondidos ou não publicados.
```

Se algum módulo não existir:

```text
Implementar camada de simulação tolerante a dependências parciais.
Documentar limitação.
Não inventar resultados definitivos.
```

---

# 7. Validação funcional, administrativa e RGPD

O simulador é uma ferramenta de apoio ao cidadão.

Regras obrigatórias:

```text
A simulação é indicativa.
A simulação não substitui análise formal.
A simulação não garante atribuição.
A simulação não garante admissão.
A simulação não garante contrato.
A simulação não deve criar candidatura automaticamente sem confirmação.
Dados de simulação anónimos não devem ser persistidos com dados pessoais sem consentimento/autenticação.
Dados de utilizador autenticado podem ser guardados para reutilização com transparência.
Dados sensíveis devem ser minimizados.
Histórico de simulações deve respeitar RGPD e retenção.
```

Copy obrigatório no simulador:

```text
A simulação apresentada é meramente indicativa e não substitui a análise formal dos serviços municipais. A elegibilidade, tipologia, renda e possibilidade de candidatura dependem da validação dos dados, documentos e regras aplicáveis ao concurso.
```

---

# 8. Objetivo da implementação

Implementar:

```text
Simulador de elegibilidade enriquecido
Simulação de tipologia adequada
Estimativa de renda mensal
Identificação automática de impedimentos
Recomendações de concursos elegíveis
Pré-preenchimento da candidatura
Persistência dos dados para reutilização
Renovação simplificada do registo
```

A plataforma deve permitir ao cidadão:

```text
Simular a sua elegibilidade antes de submeter candidatura
Compreender critérios favoráveis e impedimentos
Saber qual a tipologia habitacional potencialmente adequada
Ver estimativa indicativa de renda mensal
Receber recomendações de concursos compatíveis
Guardar dados da simulação no seu registo
Reutilizar dados na candidatura
Atualizar dados antigos sem repetir todo o processo
Renovar o registo de forma simplificada
```

A plataforma deve permitir ao Município:

```text
Configurar regras de simulação
Configurar mensagens explicativas
Consultar estatísticas agregadas de simulações
Identificar impedimentos frequentes
Reduzir candidaturas manifestamente incompletas ou inelegíveis
Melhorar a qualidade dos dados recebidos
Preservar trilho de auditoria e RGPD
```

---

# 9. Âmbito incluído

Implementar:

```text
Simulador de elegibilidade enriquecido
Simulação para utilizador anónimo sem persistência sensível
Simulação para utilizador autenticado com possibilidade de guardar dados
Simulação de tipologia adequada
Estimativa indicativa de renda mensal
Identificação automática de impedimentos
Identificação de dados em falta
Recomendações de concursos elegíveis
Recomendações de próximos passos
Pré-preenchimento da candidatura
Persistência dos dados para reutilização
Renovação simplificada do registo
Histórico de simulações do candidato
Backoffice para consulta agregada de simulações, se aplicável
Configuração de mensagens do simulador
Form Requests
Services
Policies
Controllers
Views/páginas
Rotas públicas e autenticadas
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
Submissão formal completa de candidatura
Decisão administrativa
Validação documental formal
Pontuação final
Ranking
Publicação de listas
Atribuição de habitação
Contrato
Assinatura digital
Pagamento
Integração externa com AT/SS
Integração bancária
Consulta automática a bases externas
OCR documental
IA para validação de documentos
Garantia legal de elegibilidade
```

O simulador pode preparar dados para candidatura, mas a submissão formal continua no fluxo da Sprint 8.

---

# 11. Fluxos funcionais obrigatórios

## 11.1 Simulação anónima

```text
Cidadão acede ao simulador
→ Informa dados mínimos do agregado, rendimentos e situação habitacional
→ Sistema valida inputs
→ Sistema calcula resultado indicativo
→ Sistema apresenta elegibilidade provável
→ Sistema apresenta tipologia adequada
→ Sistema apresenta estimativa de renda
→ Sistema identifica impedimentos e dados em falta
→ Sistema recomenda concursos publicados compatíveis
→ Sistema convida a criar conta/registo para guardar dados
```

Regras:

```text
Não persistir dados pessoais identificáveis de simulação anónima.
Pode persistir estatística agregada sem identificação pessoal.
Não criar candidatura.
Rate limit obrigatório no POST público.
CSRF ativo.
```

## 11.2 Simulação autenticada

```text
Candidato autenticado acede ao simulador
→ Sistema carrega dados existentes do registo/agregado/rendimentos
→ Candidato confirma ou atualiza dados
→ Sistema calcula simulação
→ Sistema guarda snapshot da simulação se o candidato aceitar
→ Sistema identifica dados reutilizáveis
→ Sistema permite pré-preencher candidatura
→ Sistema regista auditoria, se aplicável
```

## 11.3 Pré-preenchimento da candidatura

```text
Candidato conclui simulação autenticada
→ Sistema apresenta resumo dos dados reutilizáveis
→ Candidato confirma reutilização
→ Sistema cria ou atualiza rascunho de candidatura
→ Sistema copia dados permitidos
→ Sistema mantém snapshot da origem
→ Candidato revê antes de submeter
```

Regras:

```text
Não submeter candidatura automaticamente.
Não sobrescrever dados existentes sem confirmação.
Não copiar dados expirados sem aviso.
Não copiar documentos privados sem validação.
Não alterar candidaturas já submetidas.
```

## 11.4 Renovação simplificada do registo

```text
Candidato com registo antigo acede à renovação
→ Sistema mostra dados existentes
→ Sistema identifica campos desatualizados
→ Candidato confirma ou altera dados
→ Sistema cria nova versão/snapshot
→ Sistema mantém histórico
→ Sistema atualiza estado do registo
→ Sistema permite nova simulação/candidatura
```

---

# 12. Estados e tipos obrigatórios

## SimulationSessionStatus

```text
draft
in_progress
completed
saved
converted_to_registration
converted_to_application_draft
expired
cancelled
```

## SimulationResultStatus

```text
likely_eligible
likely_ineligible
requires_review
insufficient_data
no_matching_contest
```

## SimulationScope

```text
anonymous
authenticated
registration_renewal
application_prefill
```

## ImpedimentSeverity

```text
info
warning
blocking
requires_review
```

## ImpedimentType

```text
missing_registration
missing_household_data
missing_income_data
missing_housing_situation
income_above_limit
income_below_required_threshold
household_not_matching_typology
contest_closed
contest_not_yet_open
missing_required_documents
existing_active_contract
existing_active_application
age_or_residency_condition
data_outdated
manual_review_required
```

## TypologyRecommendationStatus

```text
recommended
possible
not_recommended
requires_review
insufficient_data
```

## RentEstimateStatus

```text
estimated
requires_review
insufficient_income_data
no_rule_available
not_applicable
```

## RegistrationRenewalStatus

```text
not_required
required
in_progress
completed
expired
cancelled
```

---

# 13. Modelo de dados a implementar

## 13.1 SimulationSession

Criar entidade:

```text
SimulationSession
```

Tabela:

```text
simulation_sessions
```

Campos mínimos:

```text
id
uuid
user_id nullable
adhesion_registration_id nullable
application_id nullable

scope
status
result_status

started_at
completed_at
saved_at
expires_at
converted_to_registration_at
converted_to_application_draft_at

source
ip_hash nullable
user_agent_hash nullable

created_at
updated_at
deleted_at
```

Regras:

```text
Simulações anónimas devem usar uuid.
Não guardar IP puro salvo padrão RGPD existente.
Simulações autenticadas devem associar user_id.
```

## 13.2 SimulationInputSnapshot

Criar entidade:

```text
SimulationInputSnapshot
```

Tabela:

```text
simulation_input_snapshots
```

Campos:

```text
id
simulation_session_id

household_size
adults_count
children_count
dependents_count
disabled_members_count
elderly_members_count

monthly_income
annual_income
income_source_summary
current_housing_status
current_housing_burden
residency_area
preferred_parishes
preferred_typologies

input_data
data_completeness_score
contains_personal_data

created_at
updated_at
```

Regras:

```text
input_data deve ser JSON.
Evitar dados pessoais diretos em simulação anónima.
Marcar contains_personal_data quando aplicável.
```

## 13.3 SimulationResult

Criar entidade:

```text
SimulationResult
```

Tabela:

```text
simulation_results
```

Campos:

```text
id
simulation_session_id

result_status
eligibility_summary
eligibility_score nullable
eligibility_result_payload

recommended_typology
typology_result_payload

estimated_monthly_rent
estimated_min_rent
estimated_max_rent
rent_effort_rate
rent_estimate_payload

recommendations_payload
impediments_count
blocking_impediments_count
missing_data_count

created_at
updated_at
```

Regras:

```text
Valores de renda são indicativos.
Guardar payload explicativo suficiente para auditoria e UX.
```

## 13.4 SimulationImpediment

Criar entidade:

```text
SimulationImpediment
```

Tabela:

```text
simulation_impediments
```

Campos:

```text
id
simulation_session_id
simulation_result_id nullable

type
severity
code
title
message
recommendation
is_blocking
related_field nullable
related_model_type nullable
related_model_id nullable

created_at
updated_at
```

Exemplos:

```text
Rendimentos acima do limite configurado
Registo de adesão incompleto
Agregado familiar sem rendimentos
Concurso fechado
Documento obrigatório em falta
Tipologia pretendida não adequada ao agregado
Dados de registo desatualizados
```

## 13.5 SimulationRecommendedContest

Criar entidade:

```text
SimulationRecommendedContest
```

Tabela:

```text
simulation_recommended_contests
```

Campos:

```text
id
simulation_session_id
simulation_result_id

program_id nullable
contest_id
match_status
match_score
public_status
opens_at nullable
closes_at nullable
recommended_typologies
estimated_rent_min
estimated_rent_max
reasons
warnings
cta_url

created_at
updated_at
```

Regras:

```text
Apenas recomendar concursos publicados/visíveis.
Não recomendar concursos internos ou ocultos.
Não garantir admissão.
```

## 13.6 CandidateDataReuseProfile

Criar entidade:

```text
CandidateDataReuseProfile
```

Tabela:

```text
candidate_data_reuse_profiles
```

Campos:

```text
id
user_id
adhesion_registration_id nullable

profile_number
status

personal_data_snapshot
household_snapshot
income_snapshot
housing_situation_snapshot
documents_summary_snapshot

last_confirmed_at
expires_at
created_from_simulation_session_id nullable
created_from_application_id nullable

created_at
updated_at
deleted_at
```

Estados:

```text
draft
active
outdated
expired
superseded
cancelled
```

Regras:

```text
Não guardar documentos completos aqui.
Guardar apenas resumo/metadados.
Dados reutilizáveis devem ser confirmados pelo candidato.
```

## 13.7 ApplicationPrefill

Criar entidade:

```text
ApplicationPrefill
```

Tabela:

```text
application_prefills
```

Campos:

```text
id
user_id
application_id nullable
simulation_session_id nullable
candidate_data_reuse_profile_id nullable

status
prefill_payload
fields_included
fields_excluded
warnings
confirmed_by_user_at
applied_at
created_at
updated_at
deleted_at
```

Estados:

```text
draft
pending_confirmation
confirmed
applied
cancelled
expired
```

Regras:

```text
Pré-preenchimento exige confirmação.
Não sobrescrever candidatura submetida.
Não copiar dados expirados sem aviso.
```

## 13.8 RegistrationRenewal

Criar entidade:

```text
RegistrationRenewal
```

Tabela:

```text
registration_renewals
```

Campos:

```text
id
user_id
adhesion_registration_id

renewal_number
status
reason

previous_snapshot
updated_snapshot
changed_fields
missing_fields

started_at
submitted_at
completed_at
expires_at

created_at
updated_at
deleted_at
```

Estados:

```text
draft
in_progress
submitted
completed
cancelled
expired
```

Regras:

```text
Renovação deve manter histórico.
Renovação não apaga registo anterior.
Renovação deve permitir confirmação campo a campo.
```

---

# 14. Índices e performance

Adicionar índices seguros:

```text
simulation_sessions.uuid unique
simulation_sessions.user_id
simulation_sessions.status
simulation_sessions.result_status
simulation_sessions.created_at
simulation_input_snapshots.simulation_session_id
simulation_results.simulation_session_id
simulation_impediments.simulation_session_id
simulation_impediments.type
simulation_impediments.severity
simulation_recommended_contests.simulation_session_id
simulation_recommended_contests.contest_id
candidate_data_reuse_profiles.user_id
candidate_data_reuse_profiles.status
application_prefills.user_id
application_prefills.application_id
application_prefills.status
registration_renewals.user_id
registration_renewals.adhesion_registration_id
registration_renewals.status
```

Migrations devem ser reversíveis.

Não adicionar índices duplicados.

Não carregar coleções grandes sem paginação.

---

# 15. Services obrigatórios

Criar namespace:

```text
App\Services\Simulator
```

Services:

```text
AdvancedEligibilitySimulatorService
SimulationSessionService
SimulationInputBuilder
SimulationDataCompletenessService
SimulationResultService
SimulationImpedimentDetector
ContestRecommendationService
TypologyRecommendationService
RentEstimateService
ApplicationPrefillService
CandidateDataReuseService
RegistrationRenewalService
SimulationMessageService
SimulationAuditService
```

## 15.1 AdvancedEligibilitySimulatorService

Responsável por:

```text
Orquestrar a simulação
Validar dados mínimos
Chamar motor formal de elegibilidade quando existir
Chamar recomendação de tipologia
Chamar estimativa de renda
Chamar detetor de impedimentos
Chamar recomendador de concursos
Gerar resultado consolidado
```

## 15.2 SimulationSessionService

Responsável por:

```text
Criar sessão anónima
Criar sessão autenticada
Atualizar estado
Guardar snapshot
Expirar simulações antigas
Converter simulação em dados reutilizáveis
Converter simulação em prefill
```

## 15.3 SimulationInputBuilder

Responsável por:

```text
Construir input a partir de formulário anónimo
Construir input a partir do registo existente
Construir input a partir do agregado familiar existente
Construir input a partir de candidatura anterior
Normalizar rendimentos
Normalizar composição do agregado
Normalizar situação habitacional
```

## 15.4 SimulationDataCompletenessService

Responsável por:

```text
Calcular completude dos dados
Identificar campos obrigatórios em falta
Identificar dados expirados
Identificar inconsistências
Gerar warnings de dados incompletos
```

## 15.5 SimulationImpedimentDetector

Responsável por:

```text
Identificar impedimentos automáticos
Classificar severidade
Identificar bloqueios
Identificar revisão manual necessária
Criar mensagens claras
Relacionar impedimento com campo ou modelo
```

Impedimentos mínimos:

```text
Registo inexistente
Registo incompleto
Agregado sem composição válida
Rendimentos em falta
Rendimentos acima de limite
Situação habitacional não preenchida
Documentos obrigatórios em falta
Concurso fechado
Concurso não aberto
Candidatura ativa duplicada
Contrato ativo impeditivo
Dados desatualizados
Tipologia incompatível
```

## 15.6 TypologyRecommendationService

Responsável por:

```text
Determinar tipologia adequada com base no agregado
Usar regras configuráveis se existirem
Usar fallback conservador se não existirem regras
Explicar recomendação
Indicar alternativas possíveis
Indicar revisão manual quando necessário
```

Regras base configuráveis de fallback:

```text
1 pessoa → T0/T1
2 pessoas → T1/T2
3 pessoas → T2
4 pessoas → T2/T3
5 ou mais pessoas → T3/T4+
```

Estas regras são fallback e devem ser marcadas como configuráveis/sujeitas ao regulamento municipal.

## 15.7 RentEstimateService

Responsável por:

```text
Calcular estimativa de renda mensal
Usar RentRuleSet ativo se existir
Usar rendimento mensal do agregado
Aplicar taxa de esforço configurada
Aplicar mínimos/máximos se existirem
Gerar intervalo de estimativa quando necessário
Indicar ausência de regra
Indicar revisão manual
```

A estimativa deve mostrar:

```text
Rendimento mensal considerado
Taxa de esforço estimada
Renda estimada
Renda mínima/máxima quando aplicável
Aviso de natureza indicativa
```

## 15.8 ContestRecommendationService

Responsável por:

```text
Listar concursos publicados
Filtrar concursos abertos/futuros compatíveis
Validar tipologias disponíveis
Validar intervalo de renda
Validar estado público
Calcular match_score
Gerar razões de recomendação
Gerar warnings
Gerar CTA
```

Não recomendar concursos ocultos.

Não recomendar concursos sem publicação pública.

## 15.9 ApplicationPrefillService

Responsável por:

```text
Criar prefill a partir da simulação
Validar candidatura rascunho
Copiar apenas campos confirmados
Não sobrescrever dados sem confirmação
Criar warning para dados expirados
Aplicar prefill
Registar origem
```

## 15.10 CandidateDataReuseService

Responsável por:

```text
Criar perfil de reutilização
Atualizar snapshot
Marcar dados como expirados
Listar dados reutilizáveis
Comparar dados atuais com dados anteriores
Gerar warnings para campos antigos
```

## 15.11 RegistrationRenewalService

Responsável por:

```text
Criar renovação
Carregar dados existentes
Identificar campos alterados
Guardar snapshot anterior
Guardar snapshot atualizado
Submeter renovação
Completar renovação
Atualizar registo de adesão quando aplicável
```

## 15.12 SimulationAuditService

Responsável por:

```text
Auditar simulações autenticadas
Auditar criação de prefill
Auditar aplicação de prefill
Auditar renovação de registo
Não auditar simulações anónimas com dados identificáveis
Integrar com AuditEvent se existir
```

---

# 16. Controllers obrigatórios

Criar ou completar:

```text
App\Http\Controllers\Public\AdvancedSimulatorController
App\Http\Controllers\Candidate\SimulationController
App\Http\Controllers\Candidate\ApplicationPrefillController
App\Http\Controllers\Candidate\RegistrationRenewalController
App\Http\Controllers\Backoffice\SimulatorInsightController
App\Http\Controllers\Backoffice\SimulatorConfigurationController
```

## 16.1 Public\AdvancedSimulatorController

Métodos:

```text
show()
simulate()
result(string $uuid)
```

Responsável por:

```text
Simulação pública/anónima
Formulário público
Resultado indicativo
Recomendação de criação de conta
Não persistir dados pessoais identificáveis sem autenticação
```

## 16.2 Candidate\SimulationController

Métodos:

```text
index()
create()
store()
show(SimulationSession $simulationSession)
save(SimulationSession $simulationSession)
convertToPrefill(SimulationSession $simulationSession)
```

Responsável por:

```text
Simulações autenticadas
Histórico de simulações
Guardar simulação
Converter em dados reutilizáveis
Converter em pré-preenchimento
```

## 16.3 Candidate\ApplicationPrefillController

Métodos:

```text
show(ApplicationPrefill $applicationPrefill)
confirm(ApplicationPrefill $applicationPrefill)
apply(ApplicationPrefill $applicationPrefill)
cancel(ApplicationPrefill $applicationPrefill)
```

Responsável por:

```text
Mostrar dados a reutilizar
Confirmar reutilização
Aplicar à candidatura rascunho
Cancelar prefill
```

## 16.4 Candidate\RegistrationRenewalController

Métodos:

```text
index()
create()
store()
show(RegistrationRenewal $registrationRenewal)
update(RegistrationRenewal $registrationRenewal)
submit(RegistrationRenewal $registrationRenewal)
```

Responsável por:

```text
Renovação simplificada do registo
Confirmação de dados existentes
Atualização de campos alterados
Histórico de renovação
```

## 16.5 Backoffice\SimulatorInsightController

Métodos:

```text
index()
show(SimulationSession $simulationSession)
```

Responsável por:

```text
Consulta agregada
Impedimentos frequentes
Concursos recomendados
Taxas de conclusão
Sem expor dados pessoais desnecessários
```

---

# 17. Form Requests obrigatórios

Criar:

```text
StoreAnonymousSimulationRequest
StoreCandidateSimulationRequest
SaveSimulationRequest
ConvertSimulationToPrefillRequest
ConfirmApplicationPrefillRequest
ApplyApplicationPrefillRequest
StoreRegistrationRenewalRequest
UpdateRegistrationRenewalRequest
SubmitRegistrationRenewalRequest
UpdateSimulatorConfigurationRequest
```

## 17.1 StoreAnonymousSimulationRequest

Validações mínimas:

```php
'household_size' => ['required', 'integer', 'min:1', 'max:20'],
'adults_count' => ['required', 'integer', 'min:0', 'max:20'],
'children_count' => ['nullable', 'integer', 'min:0', 'max:20'],
'dependents_count' => ['nullable', 'integer', 'min:0', 'max:20'],
'disabled_members_count' => ['nullable', 'integer', 'min:0', 'max:20'],
'monthly_income' => ['required', 'numeric', 'min:0', 'max:100000'],
'current_housing_status' => ['required', 'string', 'max:100'],
'current_housing_burden' => ['nullable', 'numeric', 'min:0', 'max:100000'],
'preferred_parishes' => ['nullable', 'array'],
'preferred_typologies' => ['nullable', 'array'],
```

## 17.2 StoreCandidateSimulationRequest

Além dos campos anteriores:

```php
'use_existing_registration_data' => ['nullable', 'boolean'],
'use_existing_household_data' => ['nullable', 'boolean'],
'use_existing_income_data' => ['nullable', 'boolean'],
'save_result' => ['nullable', 'boolean'],
```

## 17.3 ConvertSimulationToPrefillRequest

```php
'simulation_session_id' => ['required', 'exists:simulation_sessions,id'],
'application_id' => ['nullable', 'exists:applications,id'],
'fields_to_include' => ['required', 'array', 'min:1'],
'fields_to_include.*' => ['string', 'max:100'],
'confirm_data_reuse' => ['accepted'],
```

## 17.4 SubmitRegistrationRenewalRequest

```php
'confirm_data_is_current' => ['accepted'],
'changed_fields' => ['nullable', 'array'],
'notes' => ['nullable', 'string', 'max:2000'],
```

---

# 18. Policies obrigatórias

Criar ou completar:

```text
SimulationSessionPolicy
SimulationResultPolicy
SimulationImpedimentPolicy
SimulationRecommendedContestPolicy
CandidateDataReuseProfilePolicy
ApplicationPrefillPolicy
RegistrationRenewalPolicy
SimulatorInsightPolicy
```

Regras:

```text
Guest só acede a simulações anónimas por uuid.
Guest não vê simulações autenticadas.
Candidato só vê as suas simulações.
Candidato só converte as suas simulações.
Candidato só aplica prefill às suas candidaturas em rascunho.
Candidato não aplica prefill a candidatura submetida.
Candidato só renova o seu registo.
Backoffice vê estatísticas agregadas.
Backoffice só vê detalhe com permissão.
Auditor pode consultar sem alterar.
Admin pode gerir configuração.
```

---

# 19. Rotas públicas obrigatórias

Adicionar:

```php
Route::get('/simulador', [AdvancedSimulatorController::class, 'show'])
    ->name('public.simulator.show');

Route::post('/simulador', [AdvancedSimulatorController::class, 'simulate'])
    ->name('public.simulator.simulate');

Route::get('/simulador/resultado/{uuid}', [AdvancedSimulatorController::class, 'result'])
    ->name('public.simulator.result');
```

Regras:

```text
Rate limit em POST público.
CSRF ativo.
Validação por Form Request.
Não persistir dados pessoais identificáveis para guest.
```

---

# 20. Rotas da área do candidato

Adicionar, adaptando à estrutura real:

```php
Route::middleware(['auth'])->prefix('area-candidato')->name('candidate.')->group(function (): void {
    Route::get('/simulacoes', [SimulationController::class, 'index'])->name('simulations.index');
    Route::get('/simulacoes/criar', [SimulationController::class, 'create'])->name('simulations.create');
    Route::post('/simulacoes', [SimulationController::class, 'store'])->name('simulations.store');
    Route::get('/simulacoes/{simulationSession}', [SimulationController::class, 'show'])->name('simulations.show');
    Route::post('/simulacoes/{simulationSession}/guardar', [SimulationController::class, 'save'])->name('simulations.save');
    Route::post('/simulacoes/{simulationSession}/pre-preencher', [SimulationController::class, 'convertToPrefill'])->name('simulations.convert-to-prefill');

    Route::get('/pre-preenchimentos/{applicationPrefill}', [ApplicationPrefillController::class, 'show'])->name('application-prefills.show');
    Route::post('/pre-preenchimentos/{applicationPrefill}/confirmar', [ApplicationPrefillController::class, 'confirm'])->name('application-prefills.confirm');
    Route::post('/pre-preenchimentos/{applicationPrefill}/aplicar', [ApplicationPrefillController::class, 'apply'])->name('application-prefills.apply');
    Route::post('/pre-preenchimentos/{applicationPrefill}/cancelar', [ApplicationPrefillController::class, 'cancel'])->name('application-prefills.cancel');

    Route::get('/renovacao-registo', [RegistrationRenewalController::class, 'index'])->name('registration-renewals.index');
    Route::get('/renovacao-registo/criar', [RegistrationRenewalController::class, 'create'])->name('registration-renewals.create');
    Route::post('/renovacao-registo', [RegistrationRenewalController::class, 'store'])->name('registration-renewals.store');
    Route::get('/renovacao-registo/{registrationRenewal}', [RegistrationRenewalController::class, 'show'])->name('registration-renewals.show');
    Route::put('/renovacao-registo/{registrationRenewal}', [RegistrationRenewalController::class, 'update'])->name('registration-renewals.update');
    Route::post('/renovacao-registo/{registrationRenewal}/submeter', [RegistrationRenewalController::class, 'submit'])->name('registration-renewals.submit');
});
```

---

# 21. Rotas de backoffice

Adicionar se aplicável:

```php
Route::middleware(['auth'])->prefix('backoffice/simulator')->name('backoffice.simulator.')->group(function (): void {
    Route::get('/insights', [SimulatorInsightController::class, 'index'])->name('insights.index');
    Route::get('/insights/{simulationSession}', [SimulatorInsightController::class, 'show'])->name('insights.show');

    Route::get('/configuracao', [SimulatorConfigurationController::class, 'edit'])->name('configuration.edit');
    Route::put('/configuracao', [SimulatorConfigurationController::class, 'update'])->name('configuration.update');
});
```

Backoffice deve respeitar policies.

---

# 22. Views / páginas obrigatórias

Se o projeto usa Blade, criar:

```text
resources/views/public/simulator/show.blade.php
resources/views/public/simulator/result.blade.php

resources/views/candidate/simulations/index.blade.php
resources/views/candidate/simulations/create.blade.php
resources/views/candidate/simulations/show.blade.php

resources/views/candidate/application-prefills/show.blade.php

resources/views/candidate/registration-renewals/index.blade.php
resources/views/candidate/registration-renewals/create.blade.php
resources/views/candidate/registration-renewals/show.blade.php

resources/views/backoffice/simulator/insights/index.blade.php
resources/views/backoffice/simulator/insights/show.blade.php
resources/views/backoffice/simulator/configuration/edit.blade.php
```

Se o projeto usa Inertia/React/Vue, criar equivalentes.

Não mudar stack frontend.

---

# 23. UX obrigatória

## 23.1 Simulador público

Deve apresentar:

```text
Introdução clara
Aviso de simulação indicativa
Formulário por etapas
Dados do agregado
Rendimentos
Situação habitacional
Preferências
Botão simular
Resultado claro
Impedimentos
Dados em falta
Tipologia sugerida
Estimativa de renda
Concursos recomendados
CTA para criar conta/registo
CTA para consultar oferta habitacional
```

## 23.2 Resultado da simulação

Mostrar:

```text
Estado geral: provável elegível / provável inelegível / requer análise / dados insuficientes
Resumo dos critérios
Tipologia recomendada
Renda estimada
Impedimentos bloqueantes
Alertas
Dados em falta
Concursos recomendados
Próximos passos
Aviso legal/administrativo
```

## 23.3 Área do candidato

Mostrar:

```text
Histórico de simulações
Resultado mais recente
Dados reutilizáveis
Ações de pré-preenchimento
Renovação de registo
Alertas de dados expirados
```

## 23.4 Renovação simplificada

Mostrar:

```text
Dados atuais
Campos a confirmar
Campos desatualizados
Campos obrigatórios em falta
Resumo antes de submeter
Histórico de renovação
```

---

# 24. Mensagens obrigatórias

## Simulação indicativa

```text
Esta simulação é indicativa e não substitui a análise formal dos serviços municipais. A decisão final depende da validação dos dados, documentos e regras aplicáveis ao concurso.
```

## Dados em falta

```text
Existem dados em falta que podem alterar o resultado da simulação. Complete a informação indicada antes de avançar para candidatura.
```

## Pré-preenchimento

```text
Os dados podem ser reutilizados para pré-preencher a candidatura, mas devem ser revistos e confirmados antes da submissão formal.
```

## Renovação

```text
Confirme se os dados do seu registo continuam atuais. Caso existam alterações, atualize a informação antes de avançar para nova candidatura.
```

---

# 25. Regras de cálculo e simulação

## Elegibilidade

```text
Usar motor de elegibilidade existente quando disponível.
Se não existir, usar avaliação indicativa baseada nos dados disponíveis.
Resultado deve ser explicável.
Critérios não avaliáveis devem aparecer como dados insuficientes.
Não ocultar impedimentos.
```

## Tipologia

```text
Usar regras configuráveis se existirem.
Usar composição do agregado.
Considerar dependentes, crianças, idosos e pessoas com deficiência quando existirem campos.
Devolver tipologia principal e alternativas.
Não garantir atribuição.
```

## Renda

```text
Usar regra de renda ativa se existir.
Calcular estimativa com base no rendimento mensal.
Apresentar intervalo quando houver incerteza.
Mostrar taxa de esforço.
Indicar ausência de regra quando aplicável.
Não criar valor contratual.
```

## Concursos recomendados

```text
Usar apenas concursos publicados.
Preferir concursos abertos.
Incluir concursos futuros como “a abrir”.
Excluir concursos cancelados/ocultos.
Cruzar tipologia, renda e estado.
Gerar score de compatibilidade.
Explicar razões.
```

---

# 26. Persistência e reutilização de dados

Regras:

```text
Simulações anónimas não guardam dados pessoais identificáveis.
Simulações autenticadas podem ser guardadas pelo candidato.
Dados reutilizáveis devem ter snapshot.
Dados reutilizáveis devem ter data de confirmação.
Dados antigos devem ser marcados como outdated/expired.
Pré-preenchimento exige confirmação.
Candidatura submetida não pode ser alterada por prefill.
```

---

# 27. RGPD e auditoria

Auditar, se existir auditoria:

```text
Simulação autenticada criada
Simulação guardada
Resultado de simulação consultado
Pré-preenchimento criado
Pré-preenchimento aplicado
Renovação de registo iniciada
Renovação de registo submetida
Dados reutilizáveis atualizados
```

Não auditar simulações anónimas com dados identificáveis.

Não guardar IP puro salvo padrão existente.

Se Sprint 18 existir, integrar com:

```text
AuditEvent
SensitiveDataAccessLog
ConsentPurpose
UserConsent
RetentionPolicy
```

---

# 28. Backoffice — insights do simulador

Implementar painel simples, se tecnicamente viável:

```text
Total de simulações
Simulações concluídas
Simulações anónimas
Simulações autenticadas
Resultado provável elegível
Resultado provável inelegível
Dados insuficientes
Impedimentos mais frequentes
Concursos mais recomendados
Taxa de conversão para registo
Taxa de conversão para candidatura rascunho
```

Dados agregados por defeito.

Não expor dados pessoais sem permissão.

---

# 29. Factories e seeders

Criar factories:

```text
SimulationSessionFactory
SimulationInputSnapshotFactory
SimulationResultFactory
SimulationImpedimentFactory
SimulationRecommendedContestFactory
CandidateDataReuseProfileFactory
ApplicationPrefillFactory
RegistrationRenewalFactory
```

Criar seeder opcional:

```text
Database\Seeders\SimulatorDemoSeeder
```

Dados fictícios:

```text
Candidato potencialmente elegível
Candidato com dados insuficientes
Candidato com impedimento por rendimento
Candidato com impedimento por registo incompleto
Concurso recomendado
Concurso fechado não recomendado
Simulação convertida em prefill
Renovação de registo concluída
```

Não usar dados reais.

---

# 30. Testes obrigatórios

Criar ou completar os testes seguintes.

## 30.1 Testes públicos

```text
tests/Feature/Public/AdvancedSimulatorTest.php
```

Cobrir:

```text
Guest acede ao simulador
Guest submete dados mínimos válidos
Guest recebe resultado indicativo
Guest com dados insuficientes recebe aviso
Guest não cria candidatura
Guest não persiste dados pessoais identificáveis
Rate limit aplica-se ao POST público
```

## 30.2 Testes autenticados

```text
tests/Feature/Candidate/CandidateSimulationTest.php
```

Cobrir:

```text
Candidato acede ao histórico de simulações
Candidato cria simulação com dados existentes
Candidato guarda simulação
Candidato vê apenas as suas simulações
Candidato não vê simulação de terceiro
Candidato converte simulação em prefill
```

## 30.3 Testes de tipologia

```text
tests/Unit/Simulator/TypologyRecommendationServiceTest.php
```

Cobrir:

```text
1 pessoa recomenda T0/T1
2 pessoas recomenda T1/T2
3 pessoas recomenda T2
4 pessoas recomenda T2/T3
5 ou mais pessoas recomenda T3/T4+
Dados insuficientes devolvem requires_review
```

## 30.4 Testes de renda

```text
tests/Unit/Simulator/RentEstimateServiceTest.php
```

Cobrir:

```text
Calcula renda estimada com regra ativa
Aplica mínimo
Aplica máximo
Calcula taxa de esforço
Sem rendimentos devolve insufficient_income_data
Sem regra devolve no_rule_available
```

## 30.5 Testes de impedimentos

```text
tests/Unit/Simulator/SimulationImpedimentDetectorTest.php
```

Cobrir:

```text
Registo em falta
Agregado incompleto
Rendimento em falta
Concurso fechado
Candidatura ativa duplicada
Contrato ativo impeditivo
Dados expirados
Documento obrigatório em falta
```

## 30.6 Testes de concursos recomendados

```text
tests/Unit/Simulator/ContestRecommendationServiceTest.php
```

Cobrir:

```text
Recomenda concurso aberto compatível
Não recomenda concurso oculto
Não recomenda concurso cancelado
Inclui concurso futuro como a abrir
Calcula match_score
Explica razões e warnings
```

## 30.7 Testes de pré-preenchimento

```text
tests/Feature/Candidate/ApplicationPrefillTest.php
```

Cobrir:

```text
Prefill exige confirmação
Prefill copia apenas campos permitidos
Prefill não sobrescreve dados sem confirmação
Prefill não altera candidatura submetida
Prefill cria snapshot de origem
```

## 30.8 Testes de renovação

```text
tests/Feature/Candidate/RegistrationRenewalTest.php
```

Cobrir:

```text
Candidato inicia renovação
Sistema carrega dados existentes
Campos alterados são registados
Renovação mantém snapshot anterior
Renovação atualiza registo após submissão
Candidato não renova registo de terceiro
```

## 30.9 Testes de segurança/RGPD

```text
tests/Feature/Security/SimulatorPrivacyTest.php
```

Cobrir:

```text
Simulação anónima não expõe dados pessoais
Simulação autenticada exige autorização
Resultado de terceiro é bloqueado
Prefill de terceiro é bloqueado
Backoffice vê dados agregados por defeito
Dados sensíveis não aparecem em logs públicos
```

---

# 31. PHPStan específico da Sprint 21

Após implementar testes e código:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=json > storage/phpstan/sprint21-after.json || true
php -d memory_limit=1G ./vendor/bin/phpstan analyse > storage/phpstan/sprint21-after.txt || true
```

Verificar especialmente ficheiros novos:

```text
app/Models/SimulationSession.php
app/Models/SimulationInputSnapshot.php
app/Models/SimulationResult.php
app/Models/SimulationImpediment.php
app/Models/SimulationRecommendedContest.php
app/Models/CandidateDataReuseProfile.php
app/Models/ApplicationPrefill.php
app/Models/RegistrationRenewal.php
app/Services/Simulator/*
app/Http/Controllers/Public/AdvancedSimulatorController.php
app/Http/Controllers/Candidate/SimulationController.php
app/Http/Controllers/Candidate/ApplicationPrefillController.php
app/Http/Controllers/Candidate/RegistrationRenewalController.php
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
/** @return BelongsTo<User, SimulationSession> */
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

Em arrays estruturados, usar PHPDoc:

```php
/** @return array{status: string, message: string, score?: int} */
```

Não adicionar `mixed` sem necessidade.

---

# 32. Comandos obrigatórios finais

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

# 33. Documentação obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-21-simulador-avancado-registo-inteligente.md
docs/simulator/overview.md
docs/simulator/rules-and-limitations.md
docs/simulator/data-reuse-and-prefill.md
docs/simulator/registration-renewal.md
docs/qa/test-coverage-matrix.md
docs/qa/sprint-21-quality-report.md
docs/backlog/roadmap.md
```

## docs/simulator/overview.md

Incluir:

```text
Objetivo do simulador
Fluxo anónimo
Fluxo autenticado
Resultado indicativo
Tipologia
Renda estimada
Concursos recomendados
Limitações
```

## docs/simulator/rules-and-limitations.md

Incluir:

```text
Regras usadas
Regras configuráveis
Fallbacks
Impedimentos
Mensagens obrigatórias
Limitações legais
```

## docs/simulator/data-reuse-and-prefill.md

Incluir:

```text
Dados reutilizáveis
Confirmação do candidato
Campos copiados
Campos excluídos
Snapshots
Regras de segurança
```

## docs/simulator/registration-renewal.md

Incluir:

```text
Objetivo da renovação
Fluxo
Campos confirmáveis
Histórico
Estados
Limitações
```

## docs/qa/sprint-21-quality-report.md

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

# 34. Critérios de aceitação

A Sprint 21 está concluída quando:

```text
Existe simulador de elegibilidade enriquecido.
Simulador público funciona para guest.
Simulador autenticado reutiliza dados existentes.
Resultado indica elegibilidade provável.
Resultado identifica dados insuficientes.
Resultado identifica impedimentos automáticos.
Resultado simula tipologia adequada.
Resultado estima renda mensal.
Resultado recomenda concursos elegíveis/publicados.
Resultado explica razões e warnings.
Candidato pode guardar simulação.
Candidato pode converter simulação em prefill.
Prefill exige confirmação.
Prefill não submete candidatura automaticamente.
Prefill não altera candidatura submetida.
Dados persistidos podem ser reutilizados.
Renovação simplificada do registo existe.
Renovação mantém histórico.
Candidato não vê simulações/prefills/renovações de terceiros.
Backoffice vê insights agregados quando autorizado.
Simulação mostra aviso de natureza indicativa.
Dados pessoais de simulações anónimas não são persistidos indevidamente.
PHPStan foi executado antes da implementação, se disponível.
PHPStan foi executado antes da publicação, se disponível.
Foram considerados os 2471 erros legados.
Sprint 21 não introduz erros PHPStan novos nos ficheiros alterados.
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

# 35. Resposta final obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Estado PHPStan inicial
4. Estado PHPStan antes de publicação
5. Erros PHPStan legados considerados: 2471
6. Novos erros PHPStan introduzidos pela Sprint 21: sim/não
7. Models criados ou alterados
8. Migrations criadas
9. Services criados ou alterados
10. Controllers criados ou alterados
11. Form Requests criados ou alterados
12. Policies criadas ou alteradas
13. Rotas públicas criadas ou alteradas
14. Rotas da área do candidato criadas ou alteradas
15. Rotas de backoffice criadas ou alteradas
16. Views/components criados ou alterados
17. Estado do simulador público
18. Estado do simulador autenticado
19. Estado da simulação de tipologia
20. Estado da estimativa de renda
21. Estado da identificação de impedimentos
22. Estado das recomendações de concursos
23. Estado do pré-preenchimento da candidatura
24. Estado da persistência/reutilização de dados
25. Estado da renovação simplificada do registo
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
38. Recomendação objetiva para avançar ou não para Sprint 22
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 36. Definition of Done

A Sprint 21 só está concluída quando existir um simulador avançado, seguro e explicável, capaz de avaliar elegibilidade indicativa, tipologia adequada, renda estimada, impedimentos, concursos recomendados, persistência de dados reutilizáveis, pré-preenchimento controlado da candidatura e renovação simplificada do registo, sem aumentar o passivo PHPStan legado de 2471 erros e sem introduzir novos erros nos ficheiros criados ou alterados.

---

# 37. Execução imediata

Executa agora apenas:

```text
Sprint 21 — Simulador Avançado e Registo Inteligente
```

Usa como referência principal:

```text
docs/backlog/sprint-21-simulador-avancado-registo-inteligente.md
```

Fim da master prompt da Sprint 21.
