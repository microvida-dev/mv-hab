# MASTER PROMPT — EXECUÇÃO DA SPRINT 19: TESTES INTEGRADOS E QUALIDADE

Atua como arquiteto sénior Laravel, QA lead, test engineer e tech lead responsável por estabilização pré-produção.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 19 — Testes Integrados e Qualidade
```

Esta sprint pertence à fase de estabilização, controlo de qualidade, validação funcional, validação de segurança e preparação técnica antes da entrada em produção da plataforma municipal de Arrendamento Acessível.

A Sprint 19 deve criar e consolidar a suite de testes, corrigir bugs críticos, validar permissões, validar workflows, validar documentos, validar cálculos principais, rever queries críticas, criar dados realistas de teste, produzir relatório de qualidade e criar plano de regressão.

---

# 1. Regra principal

Executa apenas a Sprint 19.

Não avances para Sprint 20, Sprint 21 ou qualquer sprint futura sem validação explícita.

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
docs/backlog/sprint-19-testes-integrados-qualidade.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 19.

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
docs/backlog/sprint-9-workflow-administrativo-aperfeicoamento.md
docs/backlog/sprint-10-matriz-classificacao-ranking.md
docs/backlog/sprint-11-listas-provisorias-reclamacoes-audiencia.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-13-calculo-renda-contratos-caucao.md
docs/backlog/sprint-14-pagamentos-incumprimentos-revisao-renda.md
docs/backlog/sprint-15-manutencao-vistorias-gestao-imovel.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-17-relatorios-indicadores-dashboard-executivo.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md

docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

---

# 4. Inspeção inicial obrigatória

Antes de implementar testes ou correções, inspeciona o repositório e identifica:

```text
Versão do Laravel
Versão do PHP
Framework de testes usado
Estrutura de tests/Feature
Estrutura de tests/Unit
Factories existentes
Seeders existentes
Helpers de teste existentes
Traits de teste existentes
Sistema de autenticação em testes
Sistema de roles/permissões em testes
Sistema de storage fake em testes
Sistema de queue fake em testes
Sistema de mail fake em testes
Sistema de notification fake em testes
Configuração phpunit.xml
Configuração Pest, se existir
Configuração PHPUnit, se existir
Configuração de base de dados testing
Configuração de coverage, se existir
Configuração de CI, se existir
Configuração de Pint
Configuração PHPStan, se existir
Configuração Psalm, se existir
Configuração Rector, se existir
Comandos disponíveis em composer.json
Comandos disponíveis em package.json
```

Inspeciona também os principais módulos e models existentes:

```text
User
Role
Permission
Municipality
Program
Contest
AdhesionRegistration
Household
HouseholdMember
IncomeRecord
CurrentHousingSituation
DocumentType
DocumentSubmission
DocumentVersion
EligibilityRuleSet
EligibilityCriterion
EligibilityCheck
Application
AdministrativeProcess
CorrectionRequest
ScoringRuleSet
ApplicationScore
RankingSnapshot
ProvisionalList
Complaint
DefinitiveList
HousingUnit
Allocation
LeaseContract ou Contract
RentCalculation
RentInstallment
LeasePayment
Arrear
MaintenanceRequest
PropertyInspection
OfficialNotification
CommunicationLog
ReportExport
AuditEvent
AccessLog
DataSubjectRequest
```

Não duplicar factories, seeders, helpers ou testes existentes.

Se já existir algo equivalente a:

```text
IntegratedWorkflowTestSeeder
FullHousingProgramFlowTest
PermissionMatrixTest
DocumentSecurityFlowTest
EligibilityCalculationDeterministicTest
ScoringCalculationDeterministicTest
RentCalculationDeterministicTest
SecurityRegressionTest
BasicLoadSmokeTest
RegressionTestPlan
QualityReport
BugFixReport
CoverageMatrix
```

reaproveitar ou adaptar com compatibilidade.

Não apagar testes existentes.

Não remover assertions para fazer a suite passar.

Não desativar middleware, policies ou validações apenas para contornar falhas.

Não alterar `.env`.

Não usar dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY, credenciais ou chaves externas.

Não executar comandos destrutivos fora do ambiente de teste.

---

# 5. Dependências obrigatórias

Esta sprint depende obrigatoriamente de:

```text
Sistema de autenticação
Sistema de utilizadores
Sistema de permissões
Framework de testes Laravel ativo
Pelo menos parte dos módulos funcionais implementados
```

Depende preferencialmente de:

```text
Factories dos principais models
Seeders dos principais domínios
Storage privado
Sistema documental
Motor de elegibilidade
Motor de classificação
Módulo de contratos e renda
Módulo financeiro
Módulo de manutenção
Módulo de notificações
Módulo de relatórios
Módulo RGPD/auditoria
Sistema de queues
Sistema de mail
Sistema de PDF
Sistema de exportação
```

A Sprint 19 deve ser tolerante a módulos incompletos.

Se um módulo ainda não existir, documentar como pendência e criar testes apenas para os módulos existentes.

Não inventar funcionalidades apenas para fazer os testes passar.

Não mascarar falhas críticas com skips injustificados.

Skips só são aceitáveis quando:

```text
A dependência funcional não existe
O módulo ainda não foi implementado
A infraestrutura externa não está configurada
O teste depende de biblioteca opcional ausente
A limitação fica documentada no relatório de qualidade
```

---

# 6. Validação jurídica, RGPD e segurança

Os testes devem respeitar regras de privacidade e segurança.

Regras obrigatórias:

```text
Usar apenas dados fictícios.
Não usar NIF, IBAN, moradas, emails ou nomes reais.
Não gravar ficheiros sensíveis em storage público durante testes.
Não deixar ficheiros temporários reais após testes.
Não expor dumps com dados pessoais.
Não guardar logs de teste com dados sensíveis.
Não validar permissões apenas por happy path.
Não ignorar falhas de autorização.
Não desativar middleware de segurança sem teste alternativo.
Não remover policies para passar testes.
Não reduzir segurança para aumentar cobertura.
```

Quando forem criados dados realistas de teste, devem ser plausíveis, mas fictícios.

Usar domínios como:

```text
example.test
municipio.test
candidato.test
```

Não usar contactos reais.

---

# 7. Objetivo da implementação

Garantir estabilidade antes da entrada em produção.

A plataforma deve ficar com:

```text
Suite de testes unitários
Suite de testes funcionais
Suite de testes integrados
Testes de permissões
Testes de workflows críticos
Testes de documentos e storage privado
Testes de cálculo de elegibilidade
Testes de cálculo de pontuação
Testes de cálculo de renda
Testes de submissão
Testes básicos de carga/performance
Testes de segurança
Testes RGPD/auditoria
Factories completas
Seeders de teste realistas
Relatório de qualidade
Matriz de cobertura
Lista de bugs resolvidos
Plano de regressão
Quality gates pré-produção
Revisão de performance de queries
```

O objetivo principal é confirmar que o fluxo completo funciona de ponta a ponta:

```text
Registo de adesão
→ Dados pessoais
→ Agregado familiar
→ Rendimentos
→ Situação habitacional
→ Documentos
→ Elegibilidade
→ Candidatura
→ Workflow administrativo
→ Aperfeiçoamento
→ Classificação
→ Listas
→ Reclamações
→ Atribuição
→ Contrato
→ Renda
→ Pagamentos
→ Incumprimentos
→ Manutenção
→ Notificações
→ Relatórios
→ Auditoria/RGPD
```

---

# 8. Âmbito incluído

Implementar:

```text
Testes unitários
Testes funcionais
Testes integrados ponta a ponta
Testes de permissões
Testes de workflow
Testes de documentos
Testes de elegibilidade
Testes de pontuação
Testes de renda
Testes de submissão
Testes de carga básicos
Testes de segurança
Testes RGPD
Testes de auditoria
Testes de notificações
Testes de relatórios/exportações
Revisão de performance de queries
Correção de bugs críticos
Correção de bugs high quando bloqueiem fluxo
Dados realistas de teste
Factories completas
Seeders de teste
Plano de regressão
Relatório de qualidade
Lista de bugs resolvidos
Critérios de quality gate
Documentação de execução de testes
```

---

# 9. Fora de âmbito

Não implementar nesta sprint:

```text
Novos módulos de negócio
Novas regras legais substantivas
Novo motor de elegibilidade
Novo motor de classificação
Novo motor de renda
Novo sistema documental
Novo sistema RGPD
Novo sistema de notificações
Novo BI externo
Testes de carga avançados com infraestrutura externa
Pentest externo
Scanner externo obrigatório
CI/CD completo se ainda não existir
Deploy produção
Migração de dados reais
Importação de dados reais
Alterações destrutivas de schema sem necessidade crítica
Refatorações extensas sem relação direta com qualidade/testabilidade
```

Bugs críticos podem e devem ser corrigidos.

Melhorias estruturais pequenas são permitidas apenas quando forem necessárias para:

```text
Testabilidade
Estabilidade
Correção de bug crítico
Performance crítica
Segurança
Privacidade
Redução de duplicação extrema em testes
```

---

# 10. Fluxo de trabalho obrigatório

Segue esta ordem:

```text
1. Inspecionar aplicação e stack de testes
2. Inventariar módulos testáveis
3. Criar matriz de cobertura
4. Criar ou completar factories
5. Criar ou completar seeders de teste
6. Criar testes unitários de services críticos
7. Criar testes funcionais de controllers/routes
8. Criar testes de autorização por role
9. Criar testes integrados de workflow ponta a ponta
10. Criar testes de documentos/storage privado
11. Criar testes de cálculos determinísticos
12. Criar testes de segurança/RGPD/auditoria
13. Criar testes básicos de performance
14. Executar suite
15. Corrigir bugs críticos
16. Rever queries críticas
17. Executar comandos de validação
18. Gerar relatório de qualidade
19. Criar plano de regressão
20. Criar quality gates
21. Responder com evidência técnica
```

---

# 11. Documentos QA obrigatórios

Criar ou atualizar:

```text
docs/qa/test-coverage-matrix.md
docs/qa/sprint-19-quality-report.md
docs/qa/regression-test-plan.md
docs/qa/quality-gates.md
docs/qa/bug-fix-report.md
docs/qa/performance-query-review.md
```

## 11.1 test-coverage-matrix.md

Deve mapear:

```text
Módulo
Funcionalidade crítica
Tipo de teste
Ficheiro de teste
Estado
Risco
Observações
```

Estados permitidos:

```text
covered
partially_covered
pending_dependency
not_applicable
blocked
```

Módulos mínimos:

```text
Autenticação
Roles e permissões
Registo de adesão
Área pessoal
Agregado familiar
Rendimentos
Situação habitacional
Documentos
Elegibilidade
Candidaturas
Workflow administrativo
Aperfeiçoamento
Classificação
Ranking
Listas provisórias
Reclamações
Listas definitivas
Atribuição
Contratos
Cálculo de renda
Pagamentos
Incumprimentos
Manutenção
Vistorias
Notificações
Comunicações
Relatórios
Exportações
RGPD
Auditoria
Segurança
```

## 11.2 sprint-19-quality-report.md

Deve incluir:

```text
Resumo executivo
Estado geral da plataforma
Módulos testados
Módulos não testados por dependência
Número de testes existentes antes
Número de testes criados
Número de testes totais depois
Resultado dos comandos
Falhas encontradas
Bugs críticos corrigidos
Bugs críticos ainda abertos
Riscos de produção
Riscos RGPD/segurança
Riscos de performance
Recomendações antes de produção
Go/no-go técnico preliminar
```

Estados permitidos:

```text
ready_for_staging_validation
ready_with_minor_risks
blocked_by_critical_bugs
blocked_by_security_risks
blocked_by_missing_dependencies
```

Não declarar “pronto para produção” se existirem falhas críticas.

## 11.3 regression-test-plan.md

Deve conter checklists manuais e automáticas para:

```text
Login e permissões
Registo de adesão
Agregado familiar
Documentos
Candidatura
Elegibilidade
Workflow administrativo
Aperfeiçoamento
Classificação
Listas
Reclamações
Atribuição
Contrato
Renda
Pagamentos
Incumprimentos
Manutenção
Notificações
Relatórios
RGPD
Auditoria
Exportações
Storage privado
```

Para cada item:

```text
Objetivo
Pré-condições
Passos
Resultado esperado
Teste automático associado, se existir
Risco
```

## 11.4 quality-gates.md

Quality gates mínimos:

```text
php artisan test sem falhas críticas
php artisan route:list sem erro
npm run build sem erro quando aplicável
./vendor/bin/pint sem alterações pendentes ou com resultado documentado
PHPStan/Psalm sem erros críticos quando configurado
Sem bugs Critical abertos
Sem falhas de autorização conhecidas
Sem exposição conhecida de documentos privados
Cálculos de elegibilidade com testes determinísticos
Cálculos de pontuação com testes determinísticos
Cálculos de renda com testes determinísticos
Fluxo integrado principal testado
Plano de regressão criado
Relatório de qualidade criado
Matriz de cobertura criada
```

Se algum gate falhar, documentar como bloqueador.

## 11.5 bug-fix-report.md

Deve incluir:

```text
ID
Severidade
Módulo
Descrição
Como reproduzir
Correção aplicada
Ficheiros alterados
Teste criado
Estado
```

Severidades:

```text
Critical
High
Medium
Low
```

## 11.6 performance-query-review.md

Deve incluir:

```text
Rotas analisadas
Queries críticas identificadas
Possíveis N+1
Indexes recomendados
Eager loading aplicado
Paginação verificada
Filtros pesados
Exportações pesadas
Riscos pendentes
```

Áreas obrigatórias:

```text
Listagem de candidaturas
Detalhe de candidatura
Checklist documental
Ranking
Listas provisórias/definitivas
Relatórios/exportações
Dashboard executivo
Pagamentos em atraso
Pedidos de manutenção
Logs de auditoria
```

---

# 12. Dados realistas de teste

Criar ou completar factories para os principais models existentes.

Priorizar:

```text
UserFactory
MunicipalityFactory
ProgramFactory
ContestFactory
AdhesionRegistrationFactory
HouseholdFactory
HouseholdMemberFactory
IncomeRecordFactory
CurrentHousingSituationFactory
DocumentTypeFactory
DocumentSubmissionFactory
DocumentVersionFactory
EligibilityRuleSetFactory
EligibilityCriterionFactory
EligibilityCheckFactory
ApplicationFactory
AdministrativeProcessFactory
CorrectionRequestFactory
ScoringRuleSetFactory
ApplicationScoreFactory
RankingSnapshotFactory
ProvisionalListFactory
ComplaintFactory
DefinitiveListFactory
HousingUnitFactory
AllocationFactory
LeaseContractFactory
RentCalculationFactory
RentInstallmentFactory
LeasePaymentFactory
ArrearFactory
MaintenanceRequestFactory
PropertyInspectionFactory
OfficialNotificationFactory
CommunicationLogFactory
ReportExportFactory
AuditEventFactory
DataSubjectRequestFactory
```

Criar seeder de teste:

```text
Database\Seeders\Testing\IntegratedWorkflowTestSeeder
```

Este seeder deve criar dados fictícios para cenários integrados:

```text
Candidato elegível
Candidato inelegível
Candidato com documento rejeitado
Candidato com aperfeiçoamento solicitado
Candidato admitido para classificação
Candidato excluído
Candidato com reclamação deferida
Candidato com habitação atribuída
Candidato com contrato ativo
Arrendatário com renda em atraso
Arrendatário com pedido de manutenção
Técnico municipal
Administrador
Auditor
Gestor financeiro
Gestor de manutenção
```

Regras:

```text
Usar emails example.test.
Usar nomes fictícios.
Usar NIFs fictícios claramente inválidos ou gerados apenas para teste.
Usar moradas fictícias.
Não usar dados reais.
Não usar ficheiros reais sensíveis.
Não depender de internet.
```

---

# 13. Estrutura de testes recomendada

Organizar preferencialmente assim:

```text
tests/Unit/Eligibility
tests/Unit/Scoring
tests/Unit/Contracts
tests/Unit/Payments
tests/Unit/Documents
tests/Unit/Rgpd
tests/Unit/Audit

tests/Feature/Auth
tests/Feature/Backoffice
tests/Feature/Candidate
tests/Feature/Applications
tests/Feature/Documents
tests/Feature/Eligibility
tests/Feature/Workflow
tests/Feature/Scoring
tests/Feature/Lists
tests/Feature/Allocation
tests/Feature/Contracts
tests/Feature/Payments
tests/Feature/Maintenance
tests/Feature/Notifications
tests/Feature/Reports
tests/Feature/Rgpd
tests/Feature/Security
tests/Feature/Performance
tests/Feature/Integrated
```

Não reorganizar testes existentes de forma destrutiva.

Se já houver convenção diferente, respeitar a convenção existente.

---

# 14. Helpers e traits de teste

Criar helpers apenas se reduzirem duplicação e melhorarem legibilidade.

Possíveis helpers:

```text
CreatesUsersWithRoles
CreatesHousingProgramScenario
CreatesCandidateApplicationScenario
CreatesDocumentScenario
CreatesEligibilityScenario
CreatesScoringScenario
CreatesAllocationScenario
CreatesContractScenario
CreatesPaymentScenario
CreatesMaintenanceScenario
AssertsAuthorizationMatrix
AssertsPrivateFileStorage
```

Regras:

```text
Helpers não devem esconder lógica de negócio importante.
Factories devem criar dados válidos por defeito.
Factories devem permitir estados específicos.
Factories não devem depender de dados reais.
Traits devem ser pequenos e explícitos.
```

---

# 15. Testes unitários obrigatórios

Criar ou completar testes unitários para services críticos existentes.

## 15.1 Elegibilidade

Criar ou completar:

```text
EligibilityEngineTest
EligibilityCriteriaEvaluatorTest
EligibilityResultAggregatorTest
EligibilityDataProviderTest
```

Cobrir:

```text
Candidato elegível
Candidato inelegível
Dados insuficientes
Critério não aplicável
Critério com revisão manual
Agregado com rendimentos válidos
Agregado com rendimentos em falta
Limites por programa/concurso
Snapshot de elegibilidade
```

## 15.2 Classificação e ranking

Criar ou completar:

```text
ScoringEngineTest
ScoringCriterionEvaluatorTest
RankingServiceTest
TieBreakerServiceTest
```

Cobrir:

```text
Pontuação por critério
Critério não aplicável
Revisão manual
Empate
Desempate por regra configurada
Ranking final
Exclusão de candidatura não admitida
Snapshot de ranking
```

## 15.3 Renda e contratos

Criar ou completar:

```text
RentCalculationServiceTest
RentEffortRateServiceTest
ContractTemplateResolverTest
LeaseContractServiceTest
ContractDepositServiceTest
```

Cobrir:

```text
Cálculo de renda base
Renda mínima
Renda máxima
Taxa de esforço
Rendimento zero
Revisão manual
Caução
Contrato criado apenas a partir de atribuição aceite
Contrato não criado sem renda aprovada
```

## 15.4 Pagamentos e incumprimentos

Criar ou completar:

```text
RentScheduleServiceTest
RentChargeServiceTest
PaymentRegistrationServiceTest
ArrearsServiceTest
RegularizationAgreementServiceTest
RentReviewServiceTest
```

Cobrir:

```text
Geração de plano mensal
Registo de pagamento
Pagamento parcial
Renda em atraso
Dias de atraso
Aviso de incumprimento
Acordo de regularização
Revisão de renda
```

## 15.5 Documentos

Criar ou completar:

```text
DocumentChecklistServiceTest
DocumentUploadServiceTest
DocumentReviewServiceTest
DocumentAccessServiceTest
RequiredDocumentEvaluatorTest
```

Cobrir:

```text
Documento obrigatório em falta
Upload válido
Upload inválido
Rejeição com motivo
Validação
Documento expirado
Download autorizado
Download não autorizado
Storage privado
```

## 15.6 Auditoria e RGPD

Criar ou completar:

```text
AuditTrailServiceTest
SensitiveDataAccessServiceTest
DataSubjectRequestServiceTest
DataExportServiceTest
RetentionExecutionServiceTest
AnonymizationServiceTest
```

Cobrir:

```text
Evento crítico auditado
Dados sensíveis mascarados
Pedido RGPD criado
Exportação de dados gerada
Retenção em modo simulação
Anonimização exige aprovação
Download de exportação registado
```

---

# 16. Testes funcionais obrigatórios

Criar ou completar testes `Feature`.

## 16.1 Autenticação e backoffice

Criar ou completar:

```text
AuthenticationFlowTest
BackofficeAccessTest
RolePermissionAccessTest
```

Cobrir:

```text
Guest não acede ao backoffice
Candidato não acede ao backoffice
Técnico acede apenas aos módulos autorizados
Admin acede à gestão
Auditor consulta sem alterar
Perfis financeiros acedem a financeiro
Perfis de manutenção acedem a manutenção
MFA exigido para backoffice sensível se Sprint 18 existir
```

## 16.2 Área do candidato

Criar ou completar:

```text
CandidateAreaFlowTest
```

Cobrir:

```text
Candidato acede à área pessoal
Candidato vê apenas os seus dados
Candidato não vê candidatura de terceiro
Candidato atualiza dados permitidos
Candidato consulta notificações próprias
Candidato consulta situação financeira própria
Candidato consulta pedidos de manutenção próprios
```

## 16.3 Candidatura

Criar ou completar:

```text
ApplicationSubmissionFlowTest
ApplicationSubmissionValidationTest
```

Cobrir:

```text
Candidato inicia candidatura
Candidato preenche agregado
Candidato regista rendimentos
Candidato submete documentos
Candidato submete candidatura
Candidatura gera número
Candidatura fica bloqueada após submissão
Candidato recebe comprovativo/notificação se módulo existir
Candidatura não submete sem registo completo
Candidatura não submete sem agregado
Candidatura não submete sem rendimentos obrigatórios
Candidatura não submete sem documentos obrigatórios
Submissão cria snapshot
Submissão cria histórico
```

## 16.4 Workflow administrativo

Criar ou completar:

```text
AdministrativeWorkflowFlowTest
AdministrativeWorkflowRegressionTest
```

Cobrir:

```text
Backoffice recebe candidatura
Técnico analisa candidatura
Técnico solicita aperfeiçoamento
Candidato responde aperfeiçoamento
Técnico reanalisa
Técnico admite para classificação
Técnico não admite com motivo
Histórico de estados é criado
Transições inválidas são bloqueadas
Prazos são calculados quando aplicável
Motivo é obrigatório em não admissão
Candidato só responde a pedidos próprios
```

Transições mínimas:

```text
submitted → received
received → assigned
assigned → preliminary_review
preliminary_review → document_review
document_review → requires_correction
requires_correction → awaiting_candidate_response
awaiting_candidate_response → correction_submitted
correction_submitted → correction_under_review
correction_under_review → admitted_for_scoring
correction_under_review → not_admitted
```

## 16.5 Listas e reclamações

Criar ou completar:

```text
ListsAndComplaintsFlowTest
```

Cobrir:

```text
Lista provisória é criada a partir do ranking
Lista provisória é publicada
Candidato submete reclamação
Técnico analisa reclamação
Reclamação é deferida ou indeferida
Lista definitiva é criada
Lista definitiva é publicada
```

## 16.6 Atribuição e contrato

Criar ou completar:

```text
AllocationContractFlowTest
```

Cobrir:

```text
Habitação disponível é associada ao concurso
Candidato é atribuído por ranking
Oferta de atribuição é emitida
Candidato aceita
Contrato é preparado
Renda é calculada
Contrato é emitido
Contrato fica ativo quando validado
```

## 16.7 Pagamentos

Criar ou completar:

```text
PaymentsAndArrearsFlowTest
```

Cobrir:

```text
Plano mensal de rendas é criado
Renda é emitida
Pagamento é registado
Pagamento parcial é tratado
Atraso é detetado
Aviso de incumprimento é gerado
Acordo de regularização é criado
Revisão de renda é registada
```

## 16.8 Manutenção

Criar ou completar:

```text
MaintenanceAndInspectionFlowTest
```

Cobrir:

```text
Arrendatário cria pedido de manutenção
Município analisa
Município classifica urgência
Município atribui técnico/fornecedor
Intervenção é agendada
Intervenção é concluída
Custo é registado
Vistoria é criada
Auto de vistoria é gerado ou fallback documentado
Histórico técnico do imóvel é atualizado
```

---

# 17. Teste integrado ponta a ponta

Criar:

```text
tests/Feature/Integrated/FullHousingProgramFlowTest.php
```

Este teste deve validar o fluxo completo possível com os módulos existentes.

Cenário principal:

```text
Admin cria programa
Admin cria concurso
Admin publica concurso
Candidato cria registo de adesão
Candidato cria agregado
Candidato regista rendimentos
Candidato declara situação habitacional
Candidato submete documentos
Candidato submete candidatura
Sistema calcula elegibilidade
Técnico analisa candidatura
Técnico admite para classificação
Sistema calcula pontuação
Sistema gera ranking
Sistema gera lista provisória
Candidato apresenta reclamação
Técnico decide reclamação
Sistema gera lista definitiva
Sistema atribui habitação
Candidato aceita atribuição
Sistema calcula renda
Sistema emite contrato
Sistema cria plano de rendas
Sistema regista pagamento
Sistema permite pedido de manutenção
Sistema gera notificação crítica
Sistema cria evento de auditoria
Sistema mostra indicadores no dashboard
```

Regras:

```text
Se algum módulo não existir, dividir o teste em cenários parciais.
Não criar skips genéricos sem justificação.
Documentar dependências em falta.
O teste integrado deve usar dados fictícios.
O teste integrado não deve depender de serviços externos.
```

---

# 18. Testes de permissões

Criar:

```text
tests/Feature/Security/PermissionMatrixTest.php
```

Roles mínimos:

```text
admin
municipal_technician
jury_member
finance_manager
maintenance_manager
candidate
auditor
```

Validar acesso a:

```text
Backoffice dashboard
Programas
Concursos
Candidaturas
Documentos
Elegibilidade
Classificação
Listas
Reclamações
Atribuição
Contratos
Pagamentos
Manutenção
Notificações
Relatórios
RGPD
Auditoria
Configurações
```

Regras:

```text
Candidato não acede ao backoffice.
Auditor não executa ações mutáveis.
Gestor financeiro não gere classificação.
Gestor de manutenção não consulta rendimentos sem permissão.
Júri só acede a classificação/listas se autorizado.
Técnico não altera permissões.
Admin tem acesso conforme policy.
```

---

# 19. Testes de documentos e storage

Criar:

```text
tests/Feature/Documents/DocumentSecurityFlowTest.php
```

Cobrir:

```text
Upload de documento válido
Upload de ficheiro inválido
Validação de mime type
Validação de tamanho
Storage privado
Download por candidato dono
Bloqueio de download por terceiro
Download por técnico autorizado
Bloqueio de URL público
Rejeição com motivo
Validação por técnico
Histórico de versões
Log de acesso ao documento
AuditEvent em download, se existir
```

Usar:

```php
Storage::fake();
```

Não usar ficheiros reais sensíveis.

---

# 20. Testes de cálculos determinísticos

Criar testes com inputs explícitos e outputs esperados.

## 20.1 Elegibilidade

Criar:

```text
tests/Unit/Eligibility/EligibilityCalculationDeterministicTest.php
```

Cenários:

```text
Agregado dentro do limite
Agregado acima do limite
Dados incompletos
Documento obrigatório em falta
Situação habitacional elegível
Situação habitacional inelegível
```

## 20.2 Pontuação

Criar:

```text
tests/Unit/Scoring/ScoringCalculationDeterministicTest.php
```

Cenários:

```text
Pontuação por rendimento
Pontuação por composição do agregado
Pontuação por condição habitacional
Pontuação por antiguidade de candidatura
Empate resolvido por regra
Empate sem regra exige revisão
```

## 20.3 Renda

Criar:

```text
tests/Unit/Contracts/RentCalculationDeterministicTest.php
```

Cenários:

```text
Renda calculada por taxa de esforço
Renda mínima aplicada
Renda máxima aplicada
Caução calculada
Rendimento mensal zero exige revisão
Alteração de rendimento recalcula renda
```

---

# 21. Testes de segurança

Criar:

```text
tests/Feature/Security/SecurityRegressionTest.php
```

Cobrir:

```text
CSRF em formulários mutáveis
Mass assignment de status bloqueado
Mass assignment de role bloqueado
Mass assignment de valores financeiros bloqueado
Download sem autorização bloqueado
Exportação sensível sem permissão bloqueada
Acesso a pedido RGPD de terceiro bloqueado
Candidato não vê dados de terceiro
Backoffice exige autenticação
Rotas sensíveis exigem middleware
MFA exigido quando aplicável
Logs não guardam password/token
```

---

# 22. Testes RGPD e auditoria

Criar:

```text
tests/Feature/Rgpd/DataSubjectRequestRegressionTest.php
tests/Feature/Audit/AuditTrailRegressionTest.php
```

Cobrir:

```text
Candidato cria pedido RGPD
Candidato vê apenas pedidos próprios
Backoffice gere pedido
Exportação de dados fica privada
Download de exportação é registado
Ação crítica cria AuditEvent
Acesso a dados sensíveis cria SensitiveDataAccessLog
Auditor vê logs mas não altera
```

---

# 23. Testes de relatórios e exportações

Criar:

```text
tests/Feature/Reports/ReportExportRegressionTest.php
```

Cobrir:

```text
Dashboard operacional carrega
Dashboard executivo carrega
Filtros por período funcionam
Filtros por programa funcionam
Filtros por concurso funcionam
CSV exporta com cabeçalhos
Exportação sensível exige permissão
Download de exportação fica registado
Ficheiro exportado fica em storage privado
Nome de ficheiro não contém dados pessoais
```

---

# 24. Testes básicos de carga/performance

Criar:

```text
tests/Feature/Performance/BasicLoadSmokeTest.php
```

Objetivo:

```text
Garantir que páginas críticas respondem com volume moderado de dados.
```

Cenários mínimos:

```text
Listagem de candidaturas com 100 registos
Listagem de documentos com 200 registos
Dashboard executivo com dados agregados
Relatório CSV com 500 linhas
Ranking com 100 candidaturas
Lista de pagamentos com 200 prestações
Manutenção com 100 pedidos
```

Regras:

```text
Não fazer benchmark absoluto frágil.
Validar número de queries quando possível.
Validar ausência de N+1 evidente.
Validar resposta HTTP 200.
Validar tempo razoável apenas com limite conservador.
```

Se o projeto tiver ferramenta de query counting, usar.

Se não tiver, documentar limitação.

---

# 25. Revisão de performance de queries

Durante esta sprint, rever queries críticas.

Áreas obrigatórias:

```text
Listagem de candidaturas
Detalhe de candidatura
Checklist documental
Ranking
Listas provisórias/definitivas
Relatórios/exportações
Dashboard executivo
Pagamentos em atraso
Pedidos de manutenção
Logs de auditoria
```

Correções permitidas:

```text
Adicionar eager loading
Adicionar indexes seguros via migrations
Adicionar paginação
Evitar loops com queries
Otimizar agregações
Limitar exportações sem filtros
```

Não alterar regras de negócio para melhorar performance.

---

# 26. Classificação de bugs

Durante a Sprint 19, corrigir apenas bugs críticos ou bloqueadores encontrados pelos testes.

## Critical

```text
Impede submissão de candidatura
Permite acesso indevido a dados de terceiros
Permite alteração sem autorização
Calcula elegibilidade errada
Calcula pontuação errada
Calcula renda errada
Expõe documento privado
Quebra contrato/atribuição
Quebra pagamentos
Quebra login/backoffice
```

## High

```text
Workflow fica preso
Notificação crítica não é criada
Documento não pode ser validado
Ranking não é reproduzível
Relatório sensível exporta dados indevidos
Auditoria não regista ação crítica
```

## Medium

```text
Erro visual sem impacto crítico
Validação incompleta sem exposição de dados
Mensagem pouco clara
Filtro não funciona corretamente
```

## Low

```text
Melhoria cosmética
Texto a ajustar
Pequena inconsistência de UX sem impacto funcional
```

Bugs Critical devem ter teste automático associado sempre que possível.

---

# 27. Regras para correção de bugs

Ao corrigir bugs:

```text
Criar ou atualizar teste que reproduz o bug.
Corrigir a menor superfície possível.
Não alterar regra de negócio sem confirmar documentação.
Não relaxar policy para passar teste.
Não tornar dados sensíveis públicos.
Não remover validação obrigatória.
Não apagar histórico.
Não esconder exceções.
Documentar causa e correção em bug-fix-report.md.
```

---

# 28. Comandos obrigatórios

No final, executar:

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
./vendor/bin/phpstan analyse
```

Se existir Psalm:

```bash
./vendor/bin/psalm
```

Se existir comando de coverage configurado, executar e documentar:

```bash
php artisan test --coverage
```

ou o comando existente no projeto.

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
Bloqueia produção: sim/não
```

Não afirmar que comandos passaram se não foram executados.

Não ocultar erros.

---

# 29. Atualização documental obrigatória

Atualizar ou criar, se aplicável:

```text
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/qa/test-coverage-matrix.md
docs/qa/testing-strategy.md
docs/qa/acceptance-criteria.md
docs/qa/sprint-19-quality-report.md
docs/qa/regression-test-plan.md
docs/qa/quality-gates.md
docs/qa/bug-fix-report.md
docs/qa/performance-query-review.md
docs/backlog/roadmap.md
```

Documentar:

```text
O que foi testado
O que não foi testado
Por que razão não foi testado
Testes criados
Testes atualizados
Factories criadas
Seeders criados
Bugs encontrados
Bugs corrigidos
Bugs pendentes
Riscos críticos
Riscos altos
Riscos médios
Resultado dos comandos
Estado dos quality gates
Recomendação objetiva para Sprint 20
```

---

# 30. Critérios de aceitação

A Sprint 19 está concluída quando:

```text
Existe suite de testes unitários
Existe suite de testes funcionais
Existem testes de permissões
Existem testes de workflow
Existem testes de documentos
Existem testes de cálculo de elegibilidade
Existem testes de pontuação
Existem testes de renda
Existem testes de submissão
Existem testes integrados ponta a ponta ou parciais justificados
Existem testes básicos de carga/performance
Existem testes de segurança
Existem testes RGPD/auditoria se Sprint 18 existir
Fluxo completo funciona de ponta a ponta ou dependências em falta estão documentadas
Não existem bugs Critical abertos
Permissões principais estão validadas
Cálculos principais têm testes automáticos determinísticos
Documentos privados não ficam acessíveis publicamente
Exportações sensíveis exigem permissão
Dados de teste são fictícios
Factories principais existem
Seeders de teste realistas existem
Relatório de qualidade foi criado
Plano de regressão foi criado
Matriz de cobertura foi criada
Performance de queries críticas foi revista
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Pint/PHPStan/Psalm foram executados se existirem ou pendência foi documentada
Não foram introduzidas credenciais
Não foram usados dados reais
Não foram implementadas funcionalidades fora de âmbito
```

---

# 31. Resposta final obrigatória

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Módulos testados
4. Módulos não testados e motivo
5. Testes unitários criados ou atualizados
6. Testes funcionais criados ou atualizados
7. Testes integrados criados ou atualizados
8. Testes de permissões criados ou atualizados
9. Testes de segurança criados ou atualizados
10. Testes de performance/carga criados ou atualizados
11. Factories criadas ou alteradas
12. Seeders de teste criados ou alterados
13. Bugs críticos encontrados
14. Bugs críticos corrigidos
15. Bugs pendentes
16. Ficheiros criados
17. Ficheiros alterados
18. Migrations criadas, se aplicável
19. Melhorias de performance aplicadas
20. Resultado dos comandos executados
21. Estado dos quality gates
22. Estado da matriz de cobertura
23. Estado do relatório de qualidade
24. Estado do plano de regressão
25. Riscos ainda existentes
26. Confirmação de que não foram usados dados reais
27. Confirmação de que não foram implementadas funcionalidades fora de âmbito
28. Recomendação objetiva para avançar ou não para Sprint 20
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 32. Execução imediata

Executa agora apenas:

```text
Sprint 19 — Testes Integrados e Qualidade
```

Usa como referência principal:

```text
docs/backlog/sprint-19-testes-integrados-qualidade.md
```

Fim da master prompt da Sprint 19.
