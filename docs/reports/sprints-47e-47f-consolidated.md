# Relatório Consolidado — Sprints 47E e 47F

## Contratos, Inquilinos, Finanças e Pagamentos Permission-First

### Projeto

**Plataforma:** MV-HAB
**Programa:** Migração permission-first
**Sprints:** 47E e 47F
**Data:** 24 de julho de 2026
**Estado:** `REPOSITORY_PASS_DEPLOYMENT_GATED`

---

# 1. Objetivo consolidado

As Sprints 47E e 47F migraram para permission-first os domínios de:

- contratos;
- operações contratuais;
- área do inquilino;
- rendas;
- cauções;
- contas financeiras;
- pagamentos;
- alocações;
- recibos;
- faturas;
- importações;
- mora;
- regularizações;
- revisões financeiras.

O programa substitui progressivamente autorização por roles fixas por um modelo composto por:

```text
entitlement
&& permission
&& Policy
&& scope municipal
&& MFA
&& auditoria
2. Sprint 47E
Âmbito

A Sprint 47E migrou:

58 rotas

Domínios principais:

contratos;
documentos contratuais;
validação;
assinatura;
ativação;
operações do inquilino;
transições pós-atribuição;
contexto contratual.
Commit final publicado
432b8148b2394fac2e20dffd1f695e7262847e9b
Resultado técnico
Suite completa:
1.156 testes
14.129 asserções
PASS

Suite UX:
130 testes
645 asserções
PASS

PHPStan:
0 erros
Inventário após 47E
Rotas globais com role fixa:             538
Rotas backoffice com role fixa:          318
Rotas candidate com role fixa:           220
Rotas com permission middleware:         588
Rotas sem guards completos:              277
Classificação
REPOSITORY_PASS_DEPLOYMENT_GATED
3. Sprint 47F
Âmbito

A Sprint 47F migrou:

99 rotas

Domínios principais:

contas financeiras;
cálculo e revisão de renda;
regras e conjuntos de regras;
planos e prestações;
cauções;
pagamentos;
alocações;
reversões;
recibos;
faturas;
importações;
mora;
avisos de incumprimento;
regularizações;
alterações de rendimento.
Resultado técnico
Suite completa:
1.169 testes
15.532 asserções
PASS

Suite UX:
127 testes
632 asserções
PASS

PHPStan:
0 erros

Composer audit:
0 advisories
Inventário após 47F
Coleção total:                           1.170
Rotas globais com role fixa:               439
Rotas backoffice com role fixa:            219
Rotas candidate com role fixa:             220
Rotas globais com permission middleware:   687
Rotas sem guards completos:                178
Estado
REPOSITORY_PASS_DEPLOYMENT_GATED

Após publicação Git:

REPOSITORY_PASS_DEPLOYMENT_GATED
4. Resultado combinado

As duas sprints migraram:

58 + 99 = 157 rotas
Evolução desde o final da Sprint 47D
Após 47D → Após 47F
Métrica	Após 47D	Após 47F	Variação
Rotas globais com role fixa	596	439	-157
Rotas backoffice com role fixa	376	219	-157
Rotas candidate com role fixa	220	220	0
Rotas com permission middleware	530	687	+157
Rotas sem guards completos	335	178	-157

A variação coincide exatamente com:

58 rotas da 47E
+
99 rotas da 47F
=
157 rotas
5. Arquitetura comum
5.1. Authorization formula
municipalityHasFeature
&& userHasPermission
&& policyAllowsRecordScope
5.2. Middleware
auth
active.backoffice
mfa.backoffice
log.backoffice
permission:<permission-exata>
5.3. Roles

As roles funcionam como grupos administráveis de permissions.

Não são bypasses.

Não foram introduzidas permissions diretas a utilizadores.

5.4. Scope municipal

Todas as operações de registo exigem:

Município correspondente;
ou assignment global explícito;
nunca inferência por Município nulo.
5.5. Candidate

O candidate permanece fora do backoffice.

O acesso a contratos e dados financeiros é feito através de rotas próprias, ownership e Policies.

5.6. Auditor

O auditor permanece read-only.

5.7. Plataforma

Operadores globais exigem assignment explícito e ativo.

6. Segurança contratual

A Sprint 47E reforçou:

criação de contratos;
emissão;
validação;
assinatura;
ativação;
suspensão;
cessação;
documentos privados;
ownership;
operações pós-atribuição;
auditoria do ciclo contratual.
7. Segurança financeira

A Sprint 47F reforçou:

precisão decimal;
BCMath;
transações;
locks;
idempotência;
confirmação;
reversão;
alocação;
recibos;
importação;
isolamento municipal;
proteção de batches;
prevenção de duplicação;
auditoria financeira.
8. PaymentImportBatch municipal

Foi acrescentado:

payment_import_batches.municipality_id

A coluna:

é nullable para histórico;
tem foreign key;
usa restrictOnDelete();
é reversível;
é atribuída no servidor;
não pode ser mass assigned;
não é inferida.

Histórico sem Município falha fechado.

9. Integridade monetária

Foi introduzido:

App\Support\DecimalMoney

Foi declarado:

ext-bcmath

As operações monetárias críticas utilizam strings decimais.

Foram validados:

soma;
subtração;
multiplicação;
percentagens;
comparação;
arredondamento;
pagamentos parciais;
sobrepagamentos;
valor não alocado;
saldos;
confirmações;
reversões;
recibos;
imports.
10. Evidência de testes
Sprint 47E
1.156 testes
14.129 asserções
Sprint 47F
1.169 testes
15.532 asserções
Integridade financeira dedicada
6 testes
73 asserções
Testes dirigidos 47F
25 testes
1.543 asserções
Auditoria e inventário
8 testes
509 asserções
UX observado na 47F
127 testes
632 asserções
11. Qualidade
PHPStan:                        0 erros
Pint:                           PASS
Composer validate:              PASS
Composer platform requirements: PASS
Composer audit:                 0 advisories
Migration apply:                PASS
Migration rollback:             PASS
Suite completa:                 PASS
Suite UX:                       PASS
12. Dependências atualizadas na 47F
ext-bcmath:                     declarada
guzzlehttp/guzzle:              7.15.1
guzzlehttp/promises:            2.5.1
guzzlehttp/psr7:                2.13.0
symfony/deprecation-contracts:  3.7.1
13. Memória da suite

Foi configurado no phpunit.xml:

<ini name="memory_limit" value="1G"/>

Esta configuração:

afeta apenas PHPUnit;
permite carregar a coleção de rotas durante a suite;
não altera produção;
eliminou a interrupção prematura por 128 MB.
14. Estado residual

Após a Sprint 47F permanecem:

219 rotas backoffice com role fixa
220 rotas candidate com role fixa
178 rotas backoffice sem active/mfa/log completos

O inventário atual contém classificações heurísticas para as Sprints 47G e 47H.

Essas classificações não substituem manifests fechados.

Antes de iniciar a Sprint 47G deve ser criado e reconciliado um manifesto próprio, sem assumir automaticamente as contagens heurísticas atuais.

15. Próximas fases
Sprint 47G

Deverá tratar os contextos remanescentes previstos no programa, após novo inventário e manifesto fechado.

Sprint 47H

Deverá concluir o lote residual permission-first, incluindo decisões pendentes de plataforma, contexto misto e features.

Programa 48

Depois da conclusão do Programa 47, a fase seguinte deverá operacionalizar:

administração global da plataforma;
onboarding municipal;
provisionamento;
módulos contratados;
gestão de entitlements;
operadores de plataforma;
assignments municipais;
configuração inicial;
observabilidade multi-Município.
16. Deployment gates consolidados

Antes da promoção para produção:

PHP e extensões validadas
ext-bcmath instalada
Composer platform requirements PASS
migrations validadas
seeders de permissions executados
entitlements confirmados
assignments globais confirmados
caches reconstruídas
workers reiniciados
scope municipal A/B validado
documentos privados validados
contratos validados
pagamentos validados
reversões validadas
recibos validados
importações validadas
auditoria confirmada
logs monitorizados
rollback preparado
17. Classificação consolidada
Sprint 47E
REPOSITORY_PASS_DEPLOYMENT_GATED
Sprint 47F no momento de geração
REPOSITORY_PASS_DEPLOYMENT_GATED
Sprint 47F após publicação e igualdade local/remoto
REPOSITORY_PASS_DEPLOYMENT_GATED

Nenhuma das sprints deve ser classificada como DEPLOYED sem evidência do ambiente.
