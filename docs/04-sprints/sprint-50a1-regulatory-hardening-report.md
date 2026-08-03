# Sprint 50A.1 — Regulatory Hardening

## 1. Resumo

A Sprint 50A.1 reforça a camada regulamentar PAA/RSAA sem preencher lacunas
jurídicas por inferência. A implementação:

- centraliza o limite anual do agregado e o teto fiscal versionado;
- devolve `configuration_incomplete` quando falta fonte fiscal;
- exige proveniência verificável para tabelas de renda;
- mantém PAA e RSAA fechados sem fonte oficial instalada;
- torna publicação e snapshot uma operação transacional e idempotente;
- disponibiliza inventários read-only sem PII;
- conserva dados fictícios exclusivamente em modo demo explícito.

Não foram alteradas regras de elegibilidade, scoring, ranking, contratos,
pagamentos ou workflows administrativos fora da composição regulamentar já
existente.

Classificação: `REPOSITORY_PASS_DEPLOYMENT_GATED`.

## 2. Base e findings

- Branch: `sprint-50a1-regulatory-hardening`
- Commit-base: `0e1761a6dd4cdb737e5a1f0f8d95aa9e92b688e7`
- Findings prévios:
  `docs/quality/sprint-50-hardening-findings.md`
- Base funcional: commit remoto final da Sprint 50E
- Fronteira preservada:
  - PAA até 31/08/2026, inclusive;
  - RSAA desde 01/09/2026, inclusive.

Findings 50A.1:

| Finding | Estado final no repositório |
| --- | --- |
| Teto do 6.º escalão sem prova | Infraestrutura corrigida; fonte continua fechada |
| Tabela PAA sem prova integral | `BLOCKED_BY_MISSING_SOURCE` e fail-closed |
| Inventário de contratos legacy inexistente | Concluído, estritamente read-only |
| Publicação potencialmente não atómica | Corrigida e coberta por rollback |
| Corrida na criação de snapshots | Reforçada sobre a constraint estrutural existente |

## 3. Trabalho concluído

### 3.1. Limite anual do agregado

Foi criado `AnnualHouseholdIncomeLimitCalculator`, com resultado tipado
`AnnualIncomeLimitResult` e estado `AnnualIncomeLimitStatus`.

O cálculo usa `DecimalMoney` e strings decimais:

```text
limite efetivo = min(
    fórmula por dimensão do agregado,
    limite superior aplicável do 6.º escalão do IRS
)
```

O perfil regulamentar passa a conservar:

- ano fiscal;
- teto do 6.º escalão;
- referência e versão da fonte;
- início e fim de vigência.

Elegibilidade e `HousingCompatibilityService` usam o mesmo calculador e
expõem a mesma evidência versionada. O snapshot mantém os parâmetros
efetivamente usados e não é reescrito quando o perfil muda.

### 3.2. Proveniência das tabelas de renda

Foram introduzidos:

- `RentLimitTableManifest`;
- `RentLimitTableRow`;
- `RentLimitTableChecksumService`;
- `RentLimitTableAuditService`;
- DTO `RentLimitTableAuditResult`.

A completude deixa de depender apenas de
`rent_limits_configured`. São verificados:

- documento, referência e versão;
- vigência;
- checksum;
- número de linhas;
- cobertura municipal e tipológica;
- combinações em falta;
- correspondência dos limites agregados com o rule set;
- validação técnica;
- natureza `demo_only`.

Os providers PAA e RSAA usam esta auditoria. Não existe fallback entre regimes.

### 3.3. Publicação e snapshots

`ProgramService`, `ContestService`,
`RegulatoryPublicationReadinessService` e `RegulatorySnapshotService` foram
reforçados para:

- bloquear programa/concurso, perfil, perfil pai e rule sets;
- revalidar readiness dentro da transação;
- criar/associar o snapshot antes da mudança para publicado;
- reverter publicação e auditoria quando o snapshot falha;
- rejeitar registos publicados sem snapshot;
- tornar pedidos repetidos idempotentes;
- tratar a corrida de inserção com a chave única existente
  `(source_type, source_id, context)`;
- auditar apenas snapshots realmente criados.

Snapshots bloqueados continuam sem update/delete e não foram alterados
snapshots já emitidos.

### 3.4. Inventários read-only

Comandos criados:

```bash
php artisan regulatory:audit-rent-limit-tables \
    --regime=paa_legacy_2019 \
    --format=json \
    --output=/tmp/paa-rent-limit-audit.json

php artisan regulatory:inventory-legacy-contracts \
    --format=json \
    --output=/tmp/legacy-contracts.json
```

O inventário de contratos segue apenas:

```text
contrato
→ cálculo de renda
→ candidatura/atribuição
→ concurso/programa
→ snapshot/perfil
```

O output contém IDs e razões técnicas. Não contém nomes, NIF, emails, IBAN,
documentos ou moradas. Não existe opção de aplicação.

## 4. Migration

Criada:

- `2026_07_27_000043_harden_affordable_rent_regulatory_sources.php`

A migration:

- adiciona campos fiscais nullable aos perfis;
- cria manifestos e linhas de tabelas de renda;
- usa foreign keys restritivas;
- não faz backfill;
- não atribui valores oficiais;
- é reversível.

Validação MySQL/MariaDB:

1. `php artisan migrate` — PASS;
2. `php artisan migrate:rollback --step=1` — PASS;
3. `php artisan migrate` — PASS;
4. migration 43 no batch 16 — `Ran`.

## 5. Seeders e demo

`AffordableRentRegulatoryProfileSeeder` deixa os perfis nacionais PAA e RSAA
incompletos nos pontos sem fonte oficial.

`DemoAlcanenaAffordableRentSeeder`:

- só executa com `MVHAB_REGULATORY_DEMO_MODE=true`;
- mantém valores fictícios preexistentes;
- marca perfil e manifesto como `demo_only`;
- cria checksum e cobertura apenas para o cenário demo;
- não constitui fonte PAA ou RSAA de produção.

`MVHAB_REGULATORY_DEMO_MODE` tem valor predefinido `false`. O `phpunit.xml`
ativa-o explicitamente apenas para fixtures automatizados.

## 6. Gates regulamentares ainda fechados

Continuam fechados:

1. publicação PAA dependente de tabela nacional sem manifesto verificado;
2. cálculo anual PAA/RSAA sem fonte fiscal versionada instalada;
3. publicação RSAA sem tabela oficial de renda;
4. classificação automática de contratos legacy ambíguos;
5. utilização de qualquer dado `demo_only` fora de modo demo.

Estes gates são comportamento esperado, não falhas técnicas.

## 7. Fontes oficiais necessárias

Antes de desbloquear produção são necessárias:

- publicação oficial da tabela PAA aplicável, com cobertura por Município e
  tipologia;
- referência, versão e vigência dessa tabela;
- fonte oficial do limite superior do 6.º escalão do IRS;
- ano fiscal aplicável e respetiva vigência;
- tabela oficial RSAA quando aplicável;
- validação jurídica e técnica das fontes e dos overlays municipais.

Não foram instalados ou inferidos valores para estes elementos.

## 8. Procedimento futuro de importação e validação

1. receber o documento oficial por canal controlado;
2. calcular e registar checksum do documento original;
3. obter validação jurídica da referência, versão e vigência;
4. importar linhas para staging, nunca diretamente para produção;
5. normalizar códigos municipais e tipologias sem alterar valores;
6. criar manifesto com cobertura e contagem esperadas;
7. calcular checksum determinístico das linhas importadas;
8. executar `regulatory:audit-rent-limit-tables`;
9. resolver todas as linhas/coberturas em falta;
10. obter validação por utilizador autorizado e registar `validated_at/by`;
11. executar publicação PAA/RSAA num ambiente de staging;
12. validar snapshot, rollback, auditoria e regressões integrais;
13. promover o catálogo aprovado sem reutilizar manifestos demo.

Qualquer diferença entre documento, manifesto, checksum ou cobertura mantém
`configuration_incomplete` ou `requires_manual_review`.

## 9. Funcionalidades disponíveis exclusivamente em demo

Com modo demo explícito:

- perfil municipal fictício de Alcanena;
- teto fiscal fictício usado apenas para exercitar o cálculo;
- rendas fictícias de 320,00 EUR a 470,00 EUR;
- manifesto restrito às tipologias e ao Município do cenário demo;
- publicação e snapshots do percurso municipal demonstrativo.

Estas funcionalidades não podem produzir efeitos administrativos reais.

## 10. Evidência local dos comandos

Auditoria PAA local:

- perfis: 0;
- tabelas: 0;
- estado: `configuration_incomplete`;
- finding: ausência de perfil instalado para o regime;
- SHA-256:
  `15a8976178c3d1ad00c4b396d72c15683fbbe212bce0e791eb973bae0a17184f`.

Inventário local de contratos:

- contratos: 1;
- `missing_rent_calculation`: 1;
- SHA-256:
  `b44f2c35fea364032be967f9f9805392df48713e64c0df309f3b81f667333208`.

Estes números descrevem apenas a base local. Não representam produção.

## 11. Testes e quality gates

| Gate | Resultado |
| --- | --- |
| Testes regulamentares | PASS, 53 testes / 221 asserções |
| Regressão regulamentar/elegibilidade/compatibilidade | PASS, 103 / 1 573 |
| PHPUnit integral | PASS, 1 330 / 20 185 |
| PHPUnit UX | PASS, 130 / 645 |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| Integridade desde o commit-base | PASS, 0 violações / 0 avisos |
| Pint integral | PASS |
| PHPStan | PASS, 0 erros |
| Migration apply/rollback/apply | PASS |
| `npm run build` | PASS |
| `git diff --check` | PASS |

Contrato HTTP:

- rotas antes: 1 167;
- rotas depois: 1 167;
- nome, URI, métodos e middleware: sem diferenças;
- SHA-256 antes/depois:
  `8ffba3746d3dc77fb68ca7a5ef8815e30acfa30449f8816002c83c7c9444d87b`.

## 12. Riscos residuais

- as fontes oficiais em falta impedem ativação regulamentar de produção;
- o inventário local não mede contratos de staging/produção;
- eventual migração de contratos legacy exige uma sprint própria;
- a concorrência foi reforçada por locks, transações, constraint e testes de
  idempotência; testes de corrida distribuída devem ser repetidos no ambiente
  MySQL de staging;
- a gestão administrativa dos manifestos ainda não possui UI própria.

## 13. Evidência Git

Antes da publicação:

- base: `0e1761a6dd4cdb737e5a1f0f8d95aa9e92b688e7`;
- branch: `sprint-50a1-regulatory-hardening`;
- `main` não foi alterada.

No fecho da branch deve ser confirmado:

```bash
git status --short --branch
git rev-parse HEAD
git rev-parse origin/sprint-50a1-regulatory-hardening
git rev-list --left-right --count \
    HEAD...origin/sprint-50a1-regulatory-hardening
```

Critério: working tree limpa, HEAD local igual ao remoto e `0 0`.

## 14. Decisão final

`REPOSITORY_PASS_DEPLOYMENT_GATED`

O código e os testes estão aptos para publicação da branch. A utilização
regulamentar em produção permanece bloqueada até instalação e validação das
fontes oficiais identificadas.
