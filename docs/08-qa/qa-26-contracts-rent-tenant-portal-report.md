# QA-26 — Contracts, Rent Management & Tenant Portal Validation

## 1. Sumário executivo

A sprint QA-26 validou o ciclo pós-atribuição da MV HAB: contratos, formalização documental, acesso do inquilino, rendas/faturas/pagamentos, manutenção, vistorias e guardrails RGPD.

Resultado: **validado sem alteração de lógica de negócio**. Foram adicionados testes determinísticos para reforçar ownership/IDOR, documentos privados, idempotência financeira, rejeição de valores inválidos, histórico de manutenção e visibilidade de vistorias. Não foram encontradas regressões ou bugs funcionais que exigissem correção de services.

Limitação documental: os relatórios `qa-22`, `qa-23`, `qa-24` e `qa-25` referidos como contexto não existem nesta branch no momento da validação.

## 2. Ficheiros analisados

Documentação:

- `docs/README.md`
- `docs/01-produto/tenant/tenant-portal.md`
- `docs/01-produto/tenant/contracts.md`
- `docs/01-produto/tenant/invoices-and-payments.md`
- `docs/01-produto/tenant/automatic-charges.md`
- `docs/01-produto/tenant/maintenance-requests.md`
- `docs/01-produto/tenant/inspections.md`
- `docs/01-produto/tenant/communications.md`
- `docs/01-produto/backoffice/tenant-transition.md`
- `docs/01-produto/backoffice/key-handover.md`
- `docs/01-produto/backoffice/maintenance-reports.md`
- `docs/01-produto/backoffice/landlord-dashboard.md`
- `docs/02-arquitetura/domain-boundaries.md`
- `docs/08-qa/enterprise-quality-gate.md`
- `docs/08-qa/critical-flows-test-map.md`
- `docs/09-seguranca-rgpd/security-rgpd-guardrails.md`

Código:

- `routes/web.php`
- `app/Http/Controllers/Tenant/*`
- `app/Http/Controllers/Backoffice/LeaseContractController.php`
- `app/Http/Controllers/Backoffice/TenantInvoiceController.php`
- `app/Http/Controllers/Backoffice/TenantPaymentController.php`
- `app/Http/Controllers/Backoffice/TenantTransitionController.php`
- `app/Http/Controllers/Backoffice/MaintenanceRequestController.php`
- `app/Http/Controllers/Backoffice/PropertyInspectionController.php`
- `app/Http/Controllers/Candidate/LeaseContractDocumentController.php`
- `app/Http/Controllers/Candidate/MaintenanceAttachmentController.php`
- `app/Http/Controllers/Candidate/PropertyInspectionController.php`
- `app/Services/Contracts/*`
- `app/Services/Finance/RentScheduleService.php`
- `app/Services/Finance/RentReviewService.php`
- `app/Services/Finance/TenantFinancialAccountService.php`
- `app/Services/TenantBilling/*`
- `app/Services/TenantPortal/*`
- `app/Services/TenantTransition/*`
- `app/Services/Maintenance/*`
- `app/Services/Inspections/*`
- `app/Policies/ContractPolicy.php`
- `app/Policies/LeaseContractDocumentPolicy.php`
- `app/Policies/TenantInvoicePolicy.php`
- `app/Policies/TenantPaymentPolicy.php`
- `app/Policies/MaintenanceRequestPolicy.php`
- `app/Policies/MaintenanceAttachmentPolicy.php`
- `app/Policies/PropertyInspectionPolicy.php`
- `app/Policies/PropertyInspectionReportPolicy.php`
- `app/Policies/PropertyInspectionAttachmentPolicy.php`
- `app/Models/Contract.php`
- `app/Models/TenantFinancialAccount.php`
- `app/Models/TenantInvoice.php`
- `app/Models/TenantPayment.php`
- `app/Models/RentSchedule.php`
- `app/Models/RentInstallment.php`
- `app/Models/MaintenanceRequest.php`
- `app/Models/MaintenanceAttachment.php`
- `app/Models/PropertyInspection.php`
- `app/Models/PropertyInspectionReport.php`
- `resources/views/tenant/*`
- `database/migrations/2026_06_12_020000_create_contractual_tables.php`
- `database/migrations/2026_06_20_020000_create_tenant_post_award_tables.php`
- `tests/Feature/Sprint13ContractsRentDepositTest.php`
- `tests/Feature/Sprint14FinanceTest.php`
- `tests/Feature/Sprint15MaintenanceInspectionTest.php`
- `tests/Feature/Sprint26TenantPostAwardTest.php`
- `tests/Unit/Contracts/RentCalculationDeterministicTest.php`

## 3. Inventário pós-atribuição

| Área | Ficheiro/Rota/Model | Estado | Gap | Prioridade |
| --- | --- | --- | --- | --- |
| Contratos processuais | `Contract`, `LeaseContractService`, `backoffice.contracts.leases.*` | Implementado | Renovação existe como estado, mas não há service dedicado de criação de novo contrato renovado | P1 |
| Documentos contratuais | `LeaseContractDocument`, `LeaseContractDocumentService`, `candidate.contracts.documents.download` | Implementado | Área `tenant.*` não tem rota própria de download; usa área reservada/candidato | P2 |
| Ativação contratual | `ContractActivationService`, `LeaseContractStatusService` | Implementado | Ativação não gera plano de rendas automaticamente; financeiro é separado | P2 |
| Conta financeira | `TenantFinancialAccount`, `TenantFinancialAccountService` | Implementado | Sem gap bloqueante | - |
| Planos/prestações de renda | `RentScheduleService`, `RentInstallment` | Implementado | Sem gap bloqueante | - |
| Faturas operacionais | `TenantInvoiceService`, `tenant_invoices` | Implementado | Sem gateway bancário, fora de âmbito documentado | - |
| Pagamentos operacionais | `TenantPaymentService`, `tenant_payments` | Implementado | Sem reconciliação bancária externa, fora de âmbito documentado | - |
| Cobranças automáticas | `TenantChargeRunService`, `tenants:generate-charges` | Implementado | Itens de execução podem registar reprocessamento como `skipped_existing`; sem duplicar fatura | P2 |
| Área do inquilino | `/area-inquilino`, `TenantPortalAccessService` | Implementado | Acesso depende de contrato ativo/suspenso/renovado ou perfil ativo | P2 |
| Manutenção | `MaintenanceRequestService`, `MaintenanceStatusService`, `tenant.maintenance.*` | Implementado | Downloads de anexos continuam em rotas candidate/backoffice, não `tenant.*` | P2 |
| Vistorias | `PropertyInspectionService`, `tenant.inspections.*` | Implementado | Sem assinatura digital de auto, fora de âmbito documentado | - |
| Transição inquilino | `TenantTransitionService` | Implementado | Conta financeira só é provisionada para contrato ativo | - |
| Policies | `ContractPolicy`, `TenantInvoicePolicy`, `TenantPaymentPolicy`, `MaintenanceRequestPolicy`, `PropertyInspectionPolicy` | Implementado | Sem gap bloqueante | - |

## 4. Validação de contratos

Validado:

- Contrato é protegido por ownership na área do inquilino.
- Documento contratual fica em storage privado (`local`).
- Download de documento contratual passa por policy.
- Download autorizado gera auditoria `lease_contract_document_download`.
- Outro inquilino não consegue descarregar documento contratual.
- Testes existentes cobrem criação a partir de atribuição/cálculo aprovado, documento, validação, assinatura, caução e ativação.

Sem alteração de lógica.

## 5. Validação de rendas

Validado:

- Fatura operacional rejeita valores negativos ou zero via Form Request/service.
- Fatura por contrato/período/tipo é idempotente e não duplica registo.
- Pagamento rejeita valor zero.
- Pagamento confirmado atualiza fatura para `paid` e `amount_outstanding=0`.
- Plano de rendas rejeita renda negativa.
- Nova geração de plano fecha o plano ativo anterior e preserva histórico.
- Geração de plano regista auditoria `rent_schedule_generate`.

Sem alteração de lógica.

## 6. Validação da área do inquilino

Validado:

- Inquilino acede ao seu contrato, fatura, pagamento, pedido de manutenção e vistoria visível.
- Outro inquilino recebe 403 nos mesmos recursos.
- Listagens usam paginação.
- Queries principais usam filtros por `user_id`, contrato próprio ou `tenant_visible`.

Teste novo: `QA26ContractsRentTenantPortalTest::test_qa26_tenant_portal_enforces_ownership_for_contracts_invoices_payments_maintenance_and_inspections`.

## 7. Validação de documentos contratuais

Validado:

- Documentos contratuais são descarregados por controller autorizado.
- Documento sem ownership é 403.
- Documento com ownership é 200.
- Download autorizado fica auditado.
- Views `tenant.contracts.*` não expõem `storage_path`.

Teste novo: `QA26ContractsRentTenantPortalTest::test_qa26_contract_documents_are_private_authorized_and_audited_on_download`.

## 8. Validação de manutenção

Validado:

- Pedido criado por inquilino fica associado ao seu contrato ativo e habitação.
- Fecho prematuro é rejeitado.
- Workflow `new -> under_review -> in_progress -> resolved -> closed` preserva histórico.
- Mudança de estado é auditada.
- Anexos são guardados em storage privado e acessos passam por policy nos controllers existentes.

Teste novo: `QA26ContractsRentTenantPortalTest::test_qa26_maintenance_workflow_rejects_premature_close_and_preserves_history`.

## 9. Validação de vistorias

Validado:

- Vistoria não visível ao inquilino é 403.
- Vistoria visível continua protegida por ownership.
- Outro inquilino não acede a vistoria alheia.
- Anexos privados (`visible_to_tenant=false`) não aparecem na view do inquilino.
- Reports/anexos usam services com storage privado e auditoria nos downloads.

Teste novo: `QA26ContractsRentTenantPortalTest::test_qa26_inspections_are_hidden_until_visible_and_private_attachments_do_not_leak`.

## 10. Validação RGPD

Validado:

- Inquilino não acede a recursos de outro inquilino.
- Documentos contratuais privados exigem policy.
- Faturas, pagamentos, manutenção e vistorias ficam em rotas autenticadas.
- Downloads sensíveis auditados quando aplicável.
- Views analisadas não expõem `storage_path`.
- Não foram alterados logs nem payloads de auditoria.

## 11. Validação performance

Validado por leitura e testes:

- Listagens tenant usam paginação de 15.
- Backoffice operacional usa paginação em listagens analisadas.
- Dashboard do inquilino usa contagens e somatórios filtrados por utilizador/contrato.
- Relações principais são eager loaded nas views de listagem/detalhe.

Risco residual:

- Dashboard do inquilino usa várias queries agregadas; aceitável nesta fase, mas recomenda-se medição com volume municipal real antes de produção.

## 12. Testes criados/alterados

Criado:

- `tests/Feature/QA26ContractsRentTenantPortalTest.php`

Cenários:

- Ownership/IDOR no portal do inquilino para contrato, fatura, pagamento, manutenção e vistoria.
- Documento contratual privado com download autorizado/auditado.
- Fatura/pagamento rejeitam valores inválidos e fatura não duplica por período.
- Plano de rendas rejeita renda negativa e preserva histórico após revisão.
- Manutenção rejeita fecho prematuro e preserva histórico.
- Vistorias ficam ocultas até `tenant_visible=true` e não expõem anexos privados.

## 13. Bugs/gaps encontrados

Bugs funcionais encontrados: nenhum.

Gaps/riscos residuais:

- Não há rota `tenant.*` própria para download de documentos/anexos; a plataforma reutiliza rotas autorizadas existentes na área reservada/candidato/backoffice.
- Renovação contratual está modelada como estado, mas não há fluxo dedicado de criação de novo período contratual renovado.
- A ativação contratual e a geração de plano de rendas são fluxos separados; isto está consistente com a arquitetura atual, mas deve ser claro para operação municipal.
- Sem assinatura digital de contratos/autos de vistoria; fora de âmbito e sugerido para QA-27.
- Sem gateway bancário/reconciliação externa; fora de âmbito e sugerido para QA-28.

## 14. Correções implementadas

Não foram necessárias correções de lógica.

Alteração implementada:

- Reforço de cobertura automatizada QA-26 em `tests/Feature/QA26ContractsRentTenantPortalTest.php`.

Não houve migrations, alterações de schema, alterações de elegibilidade/scoring/listas/documentos públicos ou alterações de regras financeiras.

## 15. Riscos residuais

- Fluxos de renovação/encerramento contratual devem ser aprofundados numa sprint funcional dedicada.
- Downloads no portal do inquilino podem ser unificados em rotas `tenant.*` para UX e auditoria mais explícitas.
- Performance de dashboards deve ser medida com dados volumosos e, se necessário, usar snapshots/cache.

## 16. Comandos executados

Contexto obrigatório:

- `pwd`
- `git status --short --branch`
- `git remote -v`
- `git branch --show-current`
- `cat AGENTS.md`

Validação:

- `composer validate` — passou.
- `php artisan optimize:clear` — passou.
- `./vendor/bin/pint --test` — passou.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA26` — passou, 6 testes, 58 asserções.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract` — passou, 17 testes, 142 asserções.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Rent` — passou, 22 testes, 225 asserções.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Tenant` — passou, 13 testes, 113 asserções.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Maintenance` — passou, 7 testes, 74 asserções.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Inspection` — passou, 7 testes, 67 asserções.
- `php artisan route:list --except-vendor` — passou, 1083 rotas.
- `./vendor/bin/phpstan analyse --memory-limit=1G -v` — passou, 0 erros.
- `npm run build` — passou.
- `git diff --check` — passou.

Evidências locais:

- `storage/qa/qa-26-contract-tests.txt`
- `storage/qa/qa-26-rent-tests.txt`
- `storage/qa/qa-26-tenant-tests.txt`
- `storage/qa/qa-26-maintenance-tests.txt`
- `storage/qa/qa-26-inspections-tests.txt`
- `storage/qa/qa-26-phpstan.txt`
- `storage/qa/qa-26-route-list.txt`
- `storage/qa/qa-26-build.txt`

## 17. Resultado final

O fluxo pós-atribuição fica validado para:

Atribuição → Contrato → Ativação → Renda → Cobranças → Área do Inquilino → Manutenção → Vistorias → Renovação/Encerramento.

O núcleo operacional está privado, auditável nos pontos sensíveis, com ownership protegido, PHPStan a 0 erros e sem regressões nos filtros P1/P2 relevantes.
