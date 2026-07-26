# Relatório de Fecho Técnico — Sprint 47F

## Finanças e Pagamentos Permission-First

### Projeto

**Plataforma:** MV-HAB
**Sprint:** 47F
**Branch:** `sprint-47f-finance-payments-permissions`
**Base da branch:** `432b8148b2394fac2e20dffd1f695e7262847e9b`
**Data:** 24 de julho de 2026
**Estado:** `REPOSITORY_PASS_DEPLOYMENT_GATED`

---

# 1. Objetivo

A Sprint 47F teve como objetivo migrar para permission-first o domínio financeiro e de pagamentos da plataforma MV-HAB.

O trabalho incidiu sobre 99 rotas previamente fixadas no manifesto da sprint e incluiu:

- substituição da autorização operacional baseada em roles fixas;
- permissions granulares por operação;
- Policies específicas;
- scope municipal;
- entitlement funcional;
- MFA;
- logging;
- auditoria;
- precisão monetária;
- idempotência;
- transações e locks;
- testes de regressão e segurança.

A implementação preservou os fluxos existentes e reforçou o modelo multi-Município.

---

# 2. Base recebida da Sprint 47E

A Sprint 47F foi criada a partir do estado final publicado da Sprint 47E:

```text
Commit base:
432b8148b2394fac2e20dffd1f695e7262847e9b

Estado inicial:

Rotas globais com role fixa:             538
Rotas backoffice com role fixa:          318
Rotas candidate com role fixa:           220
Rotas com permission middleware:         588
Rotas backoffice sem guards completos:   277
3. Manifesto

O âmbito foi fixado em:

docs/access/manifests/sprint-47f-route-manifest.json

Contagem:

99 rotas únicas

A reconciliação final entre:

manifesto da Sprint 47F;
inventário das rotas ainda protegidas por roles fixas;

devolveu zero interseções.

Resultado:

0 das 99 rotas permanecem com role fixa operacional
4. Domínios abrangidos

Foram abrangidos:

contas financeiras;
rendas;
cálculos de renda;
revisões de renda;
regras de renda;
conjuntos de regras;
planos de pagamento;
prestações;
cauções;
pagamentos;
alocações;
reversões;
recibos;
faturas do inquilino;
importações CSV;
mora;
avisos de incumprimento;
acordos de regularização;
declarações de alteração de rendimentos;
operações financeiras legacy.
5. Arquitetura de autorização

A regra aplicada é:

entitlement
&& permission
&& Policy
&& scope municipal
&& estado válido

As rotas abrangidas exigem:

auth
active.backoffice
mfa.backoffice
log.backoffice
permission:<permission-exata>

O middleware de role fixa é removido da execução destas rotas.

Não foram introduzidos:

bypasses por administrador;
permissions diretas a utilizadores;
wildcard permissions;
bypasses de MFA;
bypasses de scope;
inferência global por Município nulo.
6. Policies e Form Requests

Foram reforçadas Policies para os recursos financeiros e separadas abilities de leitura e mutação.

Os Form Requests deixaram de confiar em autorização genérica e passaram a validar:

permission;
entitlement;
Policy;
ownership;
Município;
estado do recurso;
estado do ator;
valor e transição solicitados.

O browser não é fonte de verdade para dados críticos.

7. Scope municipal

O MunicipalRecordScopeService foi expandido para cobrir os recursos financeiros relevantes.

As listagens são limitadas ao Município antes da paginação.

As mutações exigem que o recurso pertença ao mesmo scope.

Casos com:

municipality_id = null

falham fechados para operadores municipais.

Operadores de plataforma dependem de assignment global explícito.

8. Importações de pagamentos

Foi adicionada a migration:

database/migrations/
2026_07_24_000001_add_municipality_id_to_payment_import_batches.php

A migration:

adiciona municipality_id;
permite null para histórico;
cria foreign key;
usa restrictOnDelete();
é reversível;
não executa backfill inferido.

O modelo protege o campo contra mass assignment.

O serviço de importação:

exige Município explícito;
usa storage privado;
utiliza transação;
remove ficheiro em caso de falha;
bloqueia lote e linhas;
limita referências ao Município;
evita reprocessamento;
evita duplicação financeira;
audita criação e processamento.

A migration foi:

aplicada:   PASS
revertida:  PASS
reaplicada: PASS
9. Precisão monetária

Foi introduzido:

app/Support/DecimalMoney.php

As operações monetárias passam a usar strings decimais e BCMath.

Foi declarado:

ext-bcmath

no composer.json.

Validações:

BCMath disponível:               PASS
Composer platform requirements:  PASS
Composer validate:               PASS

Os Services foram ajustados para evitar float em operações financeiras críticas.

10. Integridade e idempotência

Foram reforçados:

DB::transaction();
lockForUpdate();
validação do estado após lock;
prevenção de efeitos repetidos;
reutilização segura de pagamentos existentes;
limites de alocação;
saldo não negativo;
reversões controladas;
recibos únicos;
processamento idempotente de batches.

Foi criado:

tests/Feature/Security/FinanceTransactionIntegrityTest.php

Cobertura:

precisão decimal;
confirmação idempotente;
reversão idempotente;
sobrepagamento;
alocação limitada;
recibo idempotente;
processamento repetido;
ficheiro duplicado;
reimportação renomeada;
isolamento municipal;
Município nulo fail-closed.

Resultado:

6 testes
73 asserções
PASS
11. Dependências de segurança

O Composer identificou advisories em versões anteriores de:

guzzlehttp/guzzle
guzzlehttp/psr7

Foram atualizados:

guzzlehttp/guzzle:               7.10.6 → 7.15.1
guzzlehttp/promises:             2.4.1  → 2.5.1
guzzlehttp/psr7:                 2.10.4 → 2.13.0
symfony/deprecation-contracts:   3.7.0  → 3.7.1

Resultado:

Composer audit:
No security vulnerability advisories found.
12. Ambiente de testes

A suite global ultrapassava o limite CLI de 128 MB durante o carregamento repetido da coleção de rotas.

Foi adicionada ao phpunit.xml a configuração:

<ini name="memory_limit" value="1G"/>

A alteração aplica-se apenas ao ambiente PHPUnit.

Não altera o limite PHP da aplicação em produção.

Depois da configuração, a suite global concluiu com exit code zero.

13. Testes dirigidos
Segurança e regressão financeira
25 testes
1.543 asserções
PASS

Incluídos:

manifesto permission-first;
fronteira municipal;
integridade financeira;
Permission Matrix;
Sprint 14 Finance;
QA26 Contracts/Rent/Tenant Portal.
Auditoria e inventário
8 testes
509 asserções
PASS
Filtro Rent
33 testes
711 asserções
PASS
FinanceTransactionIntegrityTest
6 testes
73 asserções
PASS
14. Suite completa

Resultado final:

1.169 testes
15.532 asserções
PASS
Exit code: 0
Duração observada: 351,20 segundos
15. Suite UX

Resultado final:

127 testes
632 asserções
PASS
Exit code: 0
Duração observada: 45,65 segundos

A Sprint 47F não alterou ficheiros em:

tests/Feature/UX

A diferença relativamente a contagens históricas não resulta de alterações UX da Sprint 47F.

16. Análise estática e qualidade
Sintaxe PHP:                    PASS
PHPStan:                        PASS — 0 erros
Pint global:                    PASS — 2.893 ficheiros
Composer validate:             PASS
Composer platform requirements: PASS
Composer audit:                PASS — 0 advisories
git diff --check:              PASS
17. Inventário final
Auditoria global
Coleção total:                           1.170
Rotas globais com role fixa:               439
Rotas backoffice com role fixa:            219
Rotas candidate com role fixa:             220
Rotas globais com permission middleware:   687
Sem active.backoffice:                     178
Sem mfa.backoffice:                        178
Sem log.backoffice:                        178
Inventário backoffice
Rotas inventariadas:                  905
Rotas com role fixa:                  219
Rotas com permission middleware:      686
Rotas sem permission detetada:        616
Rotas sem Policy detetada:             10
Rotas sem scope detetado:             278
Mutações sem auditoria detetada:       84
Rotas residuais:                       16
Rotas sem testes detetados:           441
Rotas de contexto misto:               38
Rotas de plataforma:                   10
Decisão de feature pendente:           35

A diferença entre 687 e 686 resulta de os dois comandos analisarem universos diferentes:

auditoria global: 1.170 rotas;
inventário backoffice: 905 rotas.
18. Evolução quantitativa
Após 47E → Após 47F

Rotas globais com role:
538 → 439
redução: 99

Rotas backoffice com role:
318 → 219
redução: 99

Rotas candidate com role:
220 → 220
alteração: 0

Rotas com permission:
588 → 687
aumento: 99

Rotas sem guards completos:
277 → 178
redução: 99

A variação coincide exatamente com o manifesto da Sprint 47F.

19. Ficheiros estruturantes

Entre os componentes alterados ou criados encontram-se:

app/Support/DecimalMoney.php

app/Services/Finance/LegacyPaymentService.php
app/Services/Finance/RentInstallmentService.php
app/Services/Finance/PaymentImportService.php
app/Services/Finance/LeasePaymentService.php
app/Services/Finance/PaymentAllocationService.php
app/Services/Finance/PaymentReceiptService.php
app/Services/Finance/FinancialTransactionService.php

app/Services/Municipalities/MunicipalRecordScopeService.php

database/migrations/
2026_07_24_000001_add_municipality_id_to_payment_import_batches.php

tests/Feature/Security/
FinancePaymentsPermissionRoutesTest.php

tests/Feature/Security/
FinancePaymentsMunicipalBoundaryTest.php

tests/Feature/Security/
FinanceTransactionIntegrityTest.php
20. Riscos residuais

Permanecem como riscos de deployment:

ausência de ext-bcmath no servidor;
migration não executada no ambiente;
workers com código antigo;
caches desatualizadas;
diferenças entre locks SQLite e MariaDB/MySQL;
entitlements ou permissions não seedados;
assignments globais incorretos;
comportamento de integrações externas;
dados financeiros históricos sem Município;
necessidade de smoke tests com dados controlados.
21. Deployment gates

Antes de produção:

1. confirmar backup;
2. confirmar working tree e commit aprovado;
3. validar ext-bcmath;
4. composer install --no-dev;
5. composer check-platform-reqs;
6. executar migration;
7. executar seeders de permissions/entitlements aplicáveis;
8. limpar e reconstruir caches;
9. reiniciar workers;
10. validar scope municipal A/B;
11. validar confirmação de pagamento;
12. validar reversão;
13. validar emissão de recibo;
14. validar importação;
15. confirmar auditoria;
16. monitorizar logs;
17. preparar rollback.
22. Classificação

Estado no momento de geração deste relatório:

REPOSITORY_PASS_DEPLOYMENT_GATED

Após:

commits funcionais;
commit documental;
working tree limpa;
push;
igualdade entre HEAD local e remoto;

a classificação poderá ser atualizada para:

REPOSITORY_PASS_DEPLOYMENT_GATED

Não classificar como DEPLOYED sem validação efetiva do ambiente.
