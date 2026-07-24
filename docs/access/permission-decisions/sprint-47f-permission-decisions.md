# Decisões de Permissions — Sprint 47F

## Finanças, rendas, pagamentos, recibos e importações

**Projeto:** MV-HAB  
**Sprint:** 47F  
**Branch:** `sprint-47f-finance-payments-permissions`  
**Base:** Sprint 47E — commit `432b8148b2394fac2e20dffd1f695e7262847e9b`  
**Data de decisão:** 24 de julho de 2026  
**Estado:** implementação e testes concluídos; publicação Git pendente

---

## 1. Objetivo

Migrar para permission-first as 99 rotas financeiras identificadas no manifesto da Sprint 47F, eliminando a dependência operacional de roles fixas e garantindo simultaneamente:

- entitlement funcional;
- permission granular;
- Policy por operação;
- isolamento municipal;
- MFA;
- auditoria;
- integridade monetária;
- idempotência das operações críticas;
- compatibilidade com os fluxos financeiros existentes.

A regra de autorização adotada é:

```text
municipalityHasFeature
&& userHasPermission
&& policyAllowsRecordScope

Nenhuma destas três dimensões substitui as restantes.

2. Fonte de verdade do âmbito

O âmbito fechado da Sprint 47F é definido por:

docs/access/manifests/sprint-47f-route-manifest.json

O manifesto contém exatamente:

99 rotas únicas

A comparação final entre o manifesto e o inventário residual de rotas com middleware de role fixa devolveu zero interseções.

Consequentemente:

0 das 99 rotas da Sprint 47F permanecem dependentes de role fixa
3. Domínios funcionais abrangidos

A Sprint 47F abrange operações de:

contas financeiras dos inquilinos;
planos e prestações de renda;
cálculo e revisão de renda;
conjuntos e regras de renda;
cauções contratuais;
pagamentos;
alocações de pagamentos;
reversões;
recibos;
faturas do inquilino;
avisos de incumprimento;
situações de mora;
acordos de regularização;
alterações de rendimentos;
importações CSV de pagamentos;
operações financeiras legacy ainda suportadas.
4. Entitlements

As operações continuam condicionadas pelo entitlement funcional aplicável.

Foram mantidos dois contextos principais:

finance
payments
finance

Abrange, entre outros:

contas financeiras;
cálculo de renda;
revisões de renda;
regras e conjuntos de regras;
planos de renda;
prestações;
cauções;
mora;
avisos de incumprimento;
acordos de regularização;
alterações de rendimentos.
payments

Abrange, entre outros:

registo de pagamentos;
confirmação;
atualização;
eliminação controlada;
reversão;
alocação;
recibos;
faturas;
pagamentos do inquilino;
importações de pagamentos.

A existência de entitlement não concede, por si só, autorização sobre qualquer operação ou registo.

5. Permissions granulares

As rotas da Sprint 47F utilizam permissions explícitas por operação.

Exemplos:

finance.deposits.view
finance.deposits.request
finance.deposits.mark_paid
finance.deposits.waive
finance.deposits.cancel

finance.rent_calculations.view
finance.rent_calculations.calculate
finance.rent_calculations.recalculate
finance.rent_calculations.approve
finance.rent_calculations.reject

finance.rent_manual_reviews.view
finance.rent_manual_reviews.create
finance.rent_manual_reviews.approve
finance.rent_manual_reviews.reject

finance.rent_rule_sets.view
finance.rent_rule_sets.create
finance.rent_rule_sets.update
finance.rent_rule_sets.activate

finance.rent_rules.view
finance.rent_rules.create
finance.rent_rules.update
finance.rent_rules.delete

payments.view
payments.create
payments.update
payments.delete

payments.imports.view
payments.imports.create
payments.imports.process

payments.receipts.view
payments.receipts.issue
payments.receipts.cancel

payments.tenant.view
payments.tenant.create
payments.tenant.confirm

payments.invoices.view
payments.invoices.generate

A correspondência exata rota → permission permanece registada no manifesto e em routes/web.php.

6. Middleware obrigatório

As 99 rotas da Sprint 47F exigem o conjunto operacional:

auth
active.backoffice
mfa.backoffice
log.backoffice
permission:<permission-exata>

O middleware legacy:

role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor

é removido da execução das rotas incluídas no manifesto através de withoutMiddleware().

Não foram introduzidos:

novos bypasses por role;
permissions diretas atribuídas a utilizadores;
permissions wildcard de conveniência;
exceções globais para administradores;
bypasses de MFA;
bypasses de scope municipal.
7. Roles

As roles continuam a funcionar como conjuntos administráveis de permissions.

Candidate

O perfil candidate:

não acede ao backoffice financeiro;
não recebe permissions internas;
apenas acede aos próprios dados através das rotas específicas do portal;
permanece sujeito a ownership e Policies.
Auditor

O perfil auditor:

pode receber permissions de leitura;
não pode executar mutações financeiras;
não pode confirmar, reverter, alocar, importar ou emitir operações;
mantém acesso exclusivamente read-only.
Financial manager

O perfil financial_manager:

necessita das permissions exatas;
necessita de entitlement ativo;
necessita de Município válido;
necessita de MFA quando aplicável;
continua sujeito às Policies e ao scope de cada registo.
Administrator

A role administrator não constitui um bypass.

Um administrador apenas executa uma operação quando:

tem entitlement
&& tem permission
&& passa a Policy
&& passa o scope
&& tem MFA válido
8. Isolamento municipal

O isolamento é assegurado por:

App\Services\Municipalities\MunicipalRecordScopeService

Foram adicionados ou reforçados scopes para:

TenantFinancialAccount;
ContractDeposit;
RentCalculation;
RentManualReview;
RentRuleSet;
RentRule;
RentSchedule;
RentInstallment;
LeasePayment;
Payment;
PaymentReceipt;
PaymentImportBatch;
Arrear;
DefaultNotice;
RegularizationAgreement;
IncomeChangeDeclaration;
TenantInvoice;
TenantPayment;
CommunicationReceipt.

As queries de listagem são filtradas antes da paginação.

As Policies de registo validam ownership municipal através dos mesmos scopes.

9. Município nulo

A política adotada é fail-closed.

Para utilizadores municipais:

municipality_id = null
→ zero registos acessíveis
→ zero mutações permitidas

Para PaymentImportBatch histórico:

municipality_id = null
→ não pode ser visualizado nem processado por operador municipal

Não é permitido inferir Município através de:

created_by;
primeira linha do ficheiro;
primeira conta encontrada;
primeiro contrato;
primeiro Município disponível;
ausência de municipality_id.
10. Operadores de plataforma

Scope global nunca é inferido por:

user.municipality_id === null

Um operador de plataforma apenas obtém scope global quando existe assignment explícito e ativo, conforme a infraestrutura de operadores da plataforma criada nas sprints anteriores.

A Sprint 47F não introduz qualquer novo bypass global.

A criação de um lote de importação exige Município explícito. Um operador global sem Município não pode criar lotes em nome de um Município de forma implícita.

Uma futura operação cross-municipality deverá exigir seleção municipal explícita, validada no servidor e auditada.

11. Form Requests

Os Form Requests financeiros passaram a executar autorização real.

Não existem métodos:

public function authorize(): bool
{
    return true;
}

nas operações críticas abrangidas.

Os Requests utilizam:

utilizador autenticado;
permission exata;
Policy;
route model binding;
scope municipal;
validação de estados;
validação de montantes;
validação de transições.

O browser nunca é fonte de verdade para:

municipality_id;
ator;
estado de aprovação;
estado de confirmação;
saldo;
valor alocado;
valor em dívida;
número de recibo;
identidade do operador.
12. Policies

Foram reforçadas Policies específicas para os recursos financeiros.

As abilities backoffice distinguem leitura e mutação, incluindo padrões como:

viewAnyBackoffice
viewBackoffice
createBackoffice
updateBackoffice
processBackoffice
confirmBackoffice
reverseBackoffice
issueBackoffice
cancelBackoffice
approveBackoffice
rejectBackoffice

A Policy exige cumulativamente:

permission
entitlement
scope municipal
estado do ator
estado do registo
13. Importações de pagamentos

Foi adicionada a coluna:

payment_import_batches.municipality_id

Características:

nullable para preservar histórico;
foreign key para municipalities;
restrictOnDelete();
reversível;
sem backfill inferido;
protegida contra mass assignment;
atribuída no servidor;
indexada através da foreign key.

Novos lotes exigem Município explícito do ator municipal.

O ficheiro é armazenado no disco privado local.

A importação utiliza:

transação de base de dados;
lockForUpdate();
processamento idempotente;
pesquisa de prestações limitada ao Município;
deteção de ficheiro duplicado;
reutilização segura de pagamento já existente;
remoção do ficheiro quando a criação falha;
auditoria.
14. Integridade monetária

Foi introduzido:

App\Support\DecimalMoney

As operações financeiras deixam de depender de aritmética binária em float.

Foi adotada representação decimal por string e BCMath para:

normalização;
soma;
subtração;
multiplicação;
divisão;
percentagens;
comparação;
mínimo;
máximo;
verificação de zero e valores positivos.

O requisito:

ext-bcmath

foi declarado no composer.json e validado pelo Composer.

15. Idempotência e concorrência

As operações críticas utilizam, quando aplicável:

DB::transaction()
lockForUpdate()
validação do estado após lock
reutilização de registos existentes
efeitos únicos observáveis

Foram validados:

confirmação repetida;
reversão repetida;
alocação repetida;
emissão repetida de recibo;
processamento repetido de batch;
reimportação de conteúdo;
sobrepagamento;
prestação liquidada;
saldo e valor não alocado;
isolamento municipal durante importação.

A suite SQLite prova idempotência sequencial e efeitos únicos.

A concorrência real MySQL/MariaDB continua a depender dos locks e deverá ser validada no ambiente de staging.

16. Auditoria

As mutações críticas continuam a registar eventos através de:

AuditLogger
AuditEvents

São auditadas, entre outras:

criação de lote;
processamento de lote;
confirmação de pagamento;
reversão;
emissão de recibo;
alterações de renda;
decisões financeiras;
alterações de caução.

Os registos de auditoria não devem armazenar:

conteúdo integral de ficheiros;
dados bancários livres;
documentos;
payloads pessoais não minimizados;
segredos;
paths privados expostos ao utilizador.
17. Decisões rejeitadas

Foram rejeitadas as seguintes alternativas:

Autorizar apenas por role

Rejeitada porque impede permissions granulares e módulos contratáveis.

Autorizar apenas por permission

Rejeitada porque não garante entitlement nem isolamento de registo.

Inferir scope global por Município nulo

Rejeitada por permitir escalada indevida de privilégios.

Usar float para valores monetários

Rejeitada por introduzir erros binários e arredondamentos não determinísticos.

Aceitar municipality_id enviado pelo browser

Rejeitada por permitir adulteração de tenant.

Resolver importação cross-municipality pela referência

Rejeitada porque uma referência não constitui prova de ownership municipal.

Tornar o auditor mutável

Rejeitada para preservar segregação de funções.

Adicionar wildcard permissions

Rejeitada para manter revisão granular e auditação previsível.

18. Evidência de validação
Manifesto 47F:                         99 rotas
Rotas do manifesto ainda com role:      0
Rotas globais com role fixa:           439
Rotas backoffice com role fixa:        219
Rotas candidate com role fixa:         220
Rotas globais com permission:          687
Rotas backoffice sem guards:           178

Suite completa:                      1.169 testes
Asserções completas:                15.532
Suite UX:                              127 testes
Asserções UX:                          632

FinanceTransactionIntegrityTest:         6 testes
Asserções de integridade:                73

PHPStan:                                  0 erros
Composer audit:                           0 advisories
Migration apply/rollback:                 PASS
Pint global:                              PASS
19. Deployment gates

A classificação de repositório não equivale a deployment.

Antes da promoção para produção devem ser confirmados:

PHP 8.4 compatível;
ext-bcmath instalada;
composer check-platform-reqs;
migration executada;
caches reconstruídas;
workers reiniciados;
permissões e entitlements seedados;
validação do scope municipal em MariaDB/MySQL;
validação dos locks concorrentes;
smoke test de pagamentos;
smoke test de reversão;
smoke test de recibos;
smoke test de importação;
auditoria ativa;
rollback operacional preparado.
