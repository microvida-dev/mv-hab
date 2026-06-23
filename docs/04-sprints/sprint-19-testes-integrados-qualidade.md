# Sprint 19 — Testes Integrados e Qualidade

## Prioridade de desenvolvimento

Esta sprint pertence à fase de estabilização, controlo de qualidade e validação pré-produção da plataforma municipal de Arrendamento Acessível.

A Sprint 19 deve ser executada depois da implementação funcional dos principais módulos:

```text
Sprint 4 — Registo de Adesão e Área Pessoal
Sprint 5 — Agregado Familiar, Rendimentos e Situação Habitacional
Sprint 6 — Gestão Documental Avançada
Sprint 7 — Motor de Elegibilidade
Sprint 8 — Candidaturas e Submissão Formal
Sprint 9 — Workflow Administrativo e Aperfeiçoamento
Sprint 10 — Matriz de Classificação e Ranking
Sprint 11 — Listas Provisórias, Reclamações e Audiência
Sprint 12 — Atribuição de Habitações
Sprint 13 — Cálculo de Renda, Contratos e Caução
Sprint 14 — Pagamentos, Incumprimentos e Revisão de Renda
Sprint 15 — Manutenção, Vistorias e Gestão do Imóvel
Sprint 16 — Notificações, Comunicações e Modelos Documentais
Sprint 17 — Relatórios, Indicadores e Dashboard Executivo
Sprint 18 — RGPD, Segurança e Auditoria Avançada
Sprint 19 — Testes Integrados e Qualidade
```

Esta sprint não deve criar novas funcionalidades de negócio relevantes. O foco é validar, corrigir, endurecer e documentar a qualidade do sistema existente.

## Execução técnica em 16/06/2026

Implementado nesta sprint:

- `Database\Seeders\Testing\IntegratedWorkflowTestSeeder` com cenários fictícios de candidato elegível, inelegível, documento rejeitado, aperfeiçoamento, admitido, excluído, reclamação deferida, habitação atribuída, contrato ativo, renda em atraso e manutenção;
- factories para `TenantFinancialAccount`, `RentSchedule`, `RentInstallment`, `LeasePayment` e `Arrear`;
- suporte `HasFactory` nos models financeiros correspondentes;
- testes unitários determinísticos de elegibilidade, classificação, renda e auditoria;
- testes Feature de fluxo integrado, matriz de permissões, documentos/storage privado e smoke básico de query budget;
- documentação QA obrigatória da Sprint 19.

Ficheiros criados:

```text
database/seeders/Testing/IntegratedWorkflowTestSeeder.php
database/factories/TenantFinancialAccountFactory.php
database/factories/RentScheduleFactory.php
database/factories/RentInstallmentFactory.php
database/factories/LeasePaymentFactory.php
database/factories/ArrearFactory.php
tests/Unit/Audit/AuditEventFormatterTest.php
tests/Unit/Eligibility/EligibilityCalculationDeterministicTest.php
tests/Unit/Scoring/ScoringCalculationDeterministicTest.php
tests/Unit/Contracts/RentCalculationDeterministicTest.php
tests/Feature/Integrated/FullHousingProgramFlowTest.php
tests/Feature/Security/PermissionMatrixTest.php
tests/Feature/Documents/DocumentSecurityFlowTest.php
tests/Feature/Performance/BasicLoadSmokeTest.php
docs/qa/test-coverage-matrix.md
docs/qa/sprint-19-quality-report.md
docs/qa/regression-test-plan.md
docs/qa/quality-gates.md
docs/qa/bug-fix-report.md
docs/qa/performance-query-review.md
```

Ficheiros alterados:

```text
app/Models/TenantFinancialAccount.php
app/Models/RentSchedule.php
app/Models/RentInstallment.php
app/Models/LeasePayment.php
app/Models/Arrear.php
app/Policies/AccessLogPolicy.php
app/Policies/AnonymizationRequestPolicy.php
app/Policies/AuditEventPolicy.php
app/Policies/BackupReviewPolicy.php
app/Policies/ConsentPurposePolicy.php
app/Policies/DataExportPackagePolicy.php
app/Policies/DataSubjectRequestPolicy.php
app/Policies/EncryptedFieldRegistryPolicy.php
app/Policies/MfaDevicePolicy.php
app/Policies/PermissionReviewPolicy.php
app/Policies/RetentionExecutionPolicy.php
app/Policies/RetentionPolicyPolicy.php
app/Policies/SecurityAlertPolicy.php
app/Policies/SecurityAlertRulePolicy.php
app/Policies/SecurityChecklistPolicy.php
app/Policies/SensitiveDataAccessLogPolicy.php
app/Policies/UserConsentPolicy.php
app/Services/Documents/DocumentAccessService.php
app/Services/Reporting/ReportDownloadService.php
app/Services/Rgpd/ConsentPurposeService.php
app/Services/Security/PermissionReviewService.php
database/seeders/DemoAlcanenaAffordableRentSeeder.php
routes/web.php
docs/backlog/roadmap.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/architecture/data-model-overview.md
docs/product/functional-requirements.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
tests/Feature/DemoAlcanenaAffordableRentSeederTest.php
tests/Feature/Sprint18RgpdSecurityAuditTest.php
```

Resultado parcial já executado:

```text
php artisan test tests/Unit/Audit/AuditEventFormatterTest.php tests/Unit/Eligibility/EligibilityCalculationDeterministicTest.php tests/Unit/Scoring/ScoringCalculationDeterministicTest.php tests/Unit/Contracts/RentCalculationDeterministicTest.php tests/Feature/Integrated/FullHousingProgramFlowTest.php tests/Feature/Security/PermissionMatrixTest.php tests/Feature/Documents/DocumentSecurityFlowTest.php tests/Feature/Performance/BasicLoadSmokeTest.php
Resultado: 17 testes, 169 asserções, sem falhas.
```

Resultado final executado:

```text
php artisan route:list
Resultado: passou; 830 rotas listadas.

php artisan test
Resultado: passou; 174 testes, 1164 asserções.

npm run build
Resultado: passou; bundle Vite gerado.

./vendor/bin/pint --test
Resultado inicial: falhou por formatação pendente em ficheiros da Sprint 18, seeder demo, routes e novos testes.
Correção: executado ./vendor/bin/pint.
Resultado final: passou.

composer validate --no-check-publish
Resultado: passou; composer.json válido.

php artisan view:cache
Resultado: passou; templates Blade compilados.

php artisan view:clear
Resultado: passou; cache de views limpa após validação.
```

Pendências antes da Sprint 20:

- validar regras jurídicas finais e estratégia RGPD com responsável competente;
- configurar CI e decidir adoção de PHPStan/Psalm;
- preparar plano de migração/formação sem dados reais.

---

# 1. Objetivo da Sprint

Garantir estabilidade antes da entrada em produção.

A plataforma deve ficar com uma suite de testes abrangente, dados realistas de teste, plano de regressão, relatório de qualidade, validação de permissões, validação dos cálculos críticos e identificação/correção de bugs críticos.

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

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 19.

Não avances para Sprint 20, Sprint 21 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper execução por causa da branch atual.

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

Antes de implementar, identifica:

```text
Versão do Laravel
Versão do PHP
Framework de testes usado
Estrutura de tests/Feature
Estrutura de tests/Unit
Factories existentes
Seeders existentes
Helpers de teste existentes
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
Configuração de cobertura, se existir
Configuração de CI, se existir
Configuração de Pint
Configuração PHPStan/Psalm, se existir
```

Não alterar `.env`.

Não introduzir dados pessoais reais.

Não introduzir passwords reais, tokens, APP_KEY, chaves SMTP, chaves SMS ou credenciais.

Não executar comandos destrutivos fora do ambiente de teste.

Não usar dados reais de candidatos, agregados, rendimentos, contratos ou documentos.

---

# 3. Dependências funcionais

Esta sprint depende obrigatoriamente de:

```text
Sistema de autenticação
Sistema de utilizadores
Sistema de permissões
Pelo menos parte dos módulos funcionais implementados
Framework de testes Laravel ativo
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
Módulo RGPD/auditoria
Sistema de notificações
```

A Sprint 19 deve ser tolerante a módulos incompletos.

Se um módulo ainda não existir, documentar como pendência e criar testes apenas para os módulos existentes.

Não inventar funcionalidades apenas para fazer os testes passar.

Não mascarar falhas críticas com skips injustificados.

---

# 4. Validação jurídica, RGPD e segurança

Os testes devem respeitar regras de privacidade.

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
```

Quando forem criados dados realistas de teste, devem ser plausíveis, mas fictícios.

---

# 5. Âmbito incluído

Implementar:

```text
Testes unitários
Testes funcionais
Testes de permissões
Testes de workflow
Testes de documentos
Testes de elegibilidade
Testes de pontuação
Testes de renda
Testes de submissão
Testes integrados ponta a ponta
Testes de carga básicos
Testes de segurança
Testes de RGPD
Testes de auditoria
Testes de notificações
Testes de relatórios/exportações
Revisão de performance de queries
Correção de bugs críticos
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

# 6. Fora de âmbito

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
```

Bugs críticos podem ser corrigidos.

Melhorias estruturais pequenas são permitidas se forem necessárias para testabilidade, estabilidade ou performance.

---

# 7. Conceito funcional

O fluxo de qualidade deve ser:

```text
Inspecionar estado atual da aplicação
→ Inventariar módulos testáveis
→ Criar matriz de cobertura por módulo
→ Criar dados realistas de teste
→ Criar factories e seeders necessários
→ Criar testes unitários de services críticos
→ Criar testes funcionais de controllers/routes
→ Criar testes de autorização por role
→ Criar testes integrados de workflow ponta a ponta
→ Criar testes de documentos/storage privado
→ Criar testes de cálculos críticos
→ Criar testes de segurança/RGPD/auditoria
→ Executar suite
→ Corrigir bugs críticos
→ Rever queries críticas
→ Gerar relatório de qualidade
→ Criar plano de regressão
```

---

# 8. Mapa de cobertura obrigatório

Criar documento de cobertura:

```text
docs/qa/test-coverage-matrix.md
```

O documento deve mapear:

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

Módulos mínimos a mapear:

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

---

# 9. Dados realistas de teste

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
Usar NIFs fictícios claramente inválidos ou gerados para teste.
Usar moradas fictícias.
Não usar dados reais.
Não usar ficheiros reais sensíveis.
```

---

# 10. Testes unitários obrigatórios

Criar ou completar testes unitários para services críticos existentes.

## Elegibilidade

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

## Classificação e ranking

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

## Renda e contratos

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

## Pagamentos e incumprimentos

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

## Documentos

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

## Auditoria e RGPD

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

# 11. Testes funcionais obrigatórios

Criar ou completar testes `Feature`.

## Autenticação e backoffice

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

## Área do candidato

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

## Candidatura

```text
ApplicationSubmissionFlowTest
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
```

## Workflow administrativo

```text
AdministrativeWorkflowFlowTest
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
```

## Listas e reclamações

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

## Atribuição e contrato

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

## Pagamentos

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

## Manutenção

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

# 12. Testes integrados ponta a ponta

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
```

---

# 13. Testes de permissões

Criar matriz automatizada de permissões.

Ficheiro recomendado:

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
Admin tem acesso conforme política.
```

---

# 14. Testes de documentos e storage

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
Storage::fake()
```

Não usar ficheiros reais sensíveis.

---

# 15. Testes de cálculos principais

Criar testes determinísticos com inputs e outputs esperados.

## Elegibilidade

Ficheiro:

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

## Pontuação

Ficheiro:

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

## Renda

Ficheiro:

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

Os testes devem ter valores explícitos e esperados.

---

# 16. Testes de segurança

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

# 17. Testes de carga básicos

Criar testes simples, sem infraestrutura externa.

Ficheiro recomendado:

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

# 18. Revisão de performance de queries

Criar documento:

```text
docs/qa/performance-query-review.md
```

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

Áreas obrigatórias de revisão:

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

# 19. Bugs críticos

Durante a Sprint 19, corrigir apenas bugs críticos ou bloqueadores encontrados pelos testes.

Classificação:

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

Criar documento:

```text
docs/qa/bug-fix-report.md
```

Com:

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

---

# 20. Relatório de qualidade

Criar:

```text
docs/qa/sprint-19-quality-report.md
```

O relatório deve incluir:

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

Não declarar “pronto para produção” se existirem falhas críticas.

Usar estados:

```text
ready_for_staging_validation
ready_with_minor_risks
blocked_by_critical_bugs
blocked_by_security_risks
blocked_by_missing_dependencies
```

---

# 21. Plano de regressão

Criar:

```text
docs/qa/regression-test-plan.md
```

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

---

# 22. Quality gates

Criar documento:

```text
docs/qa/quality-gates.md
```

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
```

Se algum gate falhar, documentar como bloqueador.

---

# 23. Estrutura de testes recomendada

Organizar testes preferencialmente assim:

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

# 24. Factories e helpers de teste

Criar helpers apenas se reduzirem duplicação.

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
```

---

# 25. Testes de submissão

Criar:

```text
tests/Feature/Applications/ApplicationSubmissionValidationTest.php
```

Cobrir:

```text
Candidatura não submete sem registo de adesão completo
Candidatura não submete sem agregado
Candidatura não submete sem rendimentos obrigatórios
Candidatura não submete sem documentos obrigatórios
Candidatura submete quando requisitos mínimos estão completos
Submissão cria snapshot
Submissão gera número
Submissão bloqueia edição crítica
Submissão cria histórico
Submissão gera notificação/comprovativo se módulo existir
```

---

# 26. Testes de workflow

Criar:

```text
tests/Feature/Workflow/AdministrativeWorkflowRegressionTest.php
```

Cobrir transições:

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

Validar:

```text
Transições inválidas são bloqueadas
Histórico é criado
Prazos são calculados quando aplicável
Motivo é obrigatório em não admissão
Candidato só responde a pedidos próprios
```

---

# 27. Testes de relatórios e exportações

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

# 28. Testes RGPD e auditoria

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

# 29. Execução e comandos obrigatórios

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

---

# 30. Atualização documental obrigatória

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

# 31. Critérios de aceitação

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

# 32. Resposta final esperada do Codex

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

# 33. Definition of Done

A Sprint 19 só está concluída quando a plataforma tiver uma base sólida de testes unitários, funcionais, integrados, de permissões, de cálculos críticos, de documentos, de segurança, de RGPD, de relatórios e de performance básica, acompanhada por relatório de qualidade, matriz de cobertura, plano de regressão e lista de bugs críticos resolvidos.

O resultado deve permitir decidir, com evidência técnica, se a plataforma pode avançar para preparação final de produção, testes de aceitação municipal e go-live controlado.

Fim da Sprint 19.
