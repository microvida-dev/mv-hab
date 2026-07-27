# Sprint 50A — Transição Legal PAA → RSAA

## 1. Resumo

A Sprint 50A introduziu uma camada regulamentar explícita, temporalmente
versionada e auditável para permitir a coexistência de:

- procedimentos e contratos PAA legacy;
- novos procedimentos RSAA;
- overlays municipais mais exigentes;
- snapshots imutáveis do regime e das regras efetivamente aplicadas.

A implementação é incremental. Não reclassifica dados históricos por
inferência, não altera automaticamente contratos existentes e não reutiliza
limites PAA quando falta configuração oficial RSAA.

## 2. Branch e baseline

- Branch: `sprint-50a-paa-rsaa-legal-transition`
- Commit-base funcional publicado:
  `46287567204e4e203aa58683d9e67074f497a84c`
- Commit inicial obrigatório de `main` confirmado:
  `58f47b18198a562e105b3b68c67dbc3fdece8c40`
- Pasta de trabalho:
  `/Users/brunocorreia/Documents/CRM HAB/MV-HAB`

O baseline anterior à alteração funcional passou:

- Composer;
- limpeza de caches;
- auditoria de integridade;
- Pint;
- PHPStan com zero erros;
- PHPUnit integral;
- PHPUnit UX;
- listagem de rotas;
- estado de migrations;
- build Vite;
- `git diff --check`.

## 3. Documentação e inventário

Foram criados antes da alteração funcional:

- `docs/regulatory/sprint-50a-legal-hardcode-inventory.md`;
- `docs/architecture/adr-affordable-rent-legal-regime-versioning.md`.

O inventário identificou hardcodes e riscos nos domínios de elegibilidade,
renda, tipologia, publicação, contratos e dados demo. Entre os valores
retirados de decisões permanentes no código estão:

- taxa de esforço de 35%;
- RMMG de 920,00 EUR;
- limites do Quadro I de 38 632,00 EUR, 10 000,00 EUR e 5 000,00 EUR;
- resolução de regras históricas pelo relógio corrente.

Textos legais históricos, timestamps de eventos reais e regras de scoring fora
do âmbito foram preservados.

Não existem no repositório:

- `docs/03-regulamento-alcanena/`;
- relatório final autónomo da Sprint 49.

Estas ausências não foram preenchidas por inferência. O código, o histórico e
as restantes fontes existentes foram usados como evidência.

## 4. Decisão arquitetural

O ADR estabeleceu:

1. snapshot bloqueado prevalece sobre qualquer resolução posterior;
2. perfil explicitamente associado prevalece num procedimento já fixado;
3. apenas rascunhos novos podem ser resolvidos por data autoritativa explícita;
4. históricos ambíguos ficam para revisão manual;
5. overlays municipais podem restringir, mas não enfraquecer regras nacionais;
6. RSAA sem tabela oficial de renda configurada falha de forma fechada.

As datas autoritativas são específicas por contexto: publicação do programa,
publicação do concurso, submissão da candidatura, execução da elegibilidade,
cálculo de renda e execução contratual.

## 5. Migration

Foi criada:

- `database/migrations/2026_07_27_000041_create_affordable_rent_regulatory_layer.php`.

A migration cria:

- `affordable_rent_regulatory_profiles`;
- `regulatory_snapshots`.

Adiciona referências regulamentares a:

- `programs`;
- `contests`;
- `applications`;
- `eligibility_checks`;
- `rent_calculations`;
- `contracts`;
- `eligibility_rule_sets`;
- `rent_rule_sets`;
- `typology_adequacy_rules`;
- `allocation_rule_sets`.

O contrato recebe também classificação regulamentar explícita. As foreign keys
que protegem prova histórica usam restrição adequada e não existe cascade de
eliminação de snapshots.

Validação real em MySQL/MariaDB:

1. migration aplicada no batch 14;
2. rollback real com `php artisan migrate:rollback --step=1`;
3. migration reaplicada;
4. `migrate:status` confirmou o estado `Ran`.

Não foi executado backfill histórico nem ocorreu alteração destrutiva de dados.

## 6. Modelos, enums e DTOs

Foram criados:

- `AffordableRentRegulatoryProfile`;
- `RegulatorySnapshot`;
- `AffordableRentLegalRegime`;
- `LegalRegimeResolutionStatus`;
- `RegulatoryClassificationStatus`;
- `RegulatoryConfigurationStatus`;
- `RegulatoryContext`;
- `RegulatoryProfileStatus`;
- `RentLimitConfigurationStatus`;
- `LegalRegimeResolution`;
- `RentLimitResult`.

Os modelos de programa, concurso, candidatura, elegibilidade, renda e contrato
receberam relações e casts tipados. Os rule sets relevantes podem referenciar o
perfil regulamentar.

`RegulatorySnapshot` impede atualização e eliminação pela aplicação depois de
bloqueado. O checksum é determinístico e o payload regulamentar não contém PII.

## 7. Services regulamentares

Foram criados:

- `AffordableRentLegalRegimeResolver`;
- `MunicipalRegulatoryOverlayService`;
- `RegulatoryPublicationReadinessService`;
- `RegulatoryRuleSetLinkService`;
- `RegulatorySnapshotService`;
- `RentLimitProviderInterface`;
- `RentLimitProviderRegistry`;
- `PaaRentLimitProvider`;
- `RsaaRentLimitProvider`.

O resolver:

- recebe sempre uma data explícita;
- resolve PAA até 31/08/2026 inclusive;
- resolve RSAA desde 01/09/2026;
- normaliza a fronteira em `Europe/Lisbon`;
- preserva snapshots e perfis já fixados;
- devolve revisão manual quando o histórico é ambíguo.

O provider PAA usa configuração versionada. O provider RSAA não reutiliza
valores PAA e devolve configuração incompleta enquanto faltar fonte oficial.

## 8. Publicação e fail-closed

`ProgramService` e `ContestService` foram integrados com a validação
regulamentar. Uma publicação nova exige:

- perfil ativo e vigente;
- perfil compatível com o Município;
- base legal;
- configuração nacional/municipal coerente;
- rule sets reais de elegibilidade, renda, tipologia e atribuição;
- tabela de renda aplicável;
- snapshot regulamentar gerável.

Uma configuração RSAA incompleta produz erro de validação em português, sem
erro 500 e sem aceitar regime ou perfil incompatível enviado pelo browser.

Programas e concursos já publicados não são despublicados ou reclassificados.

## 9. Elegibilidade, renda e contratos

O fluxo de elegibilidade:

- captura uma data de execução;
- resolve rule sets por data explícita;
- obtém parâmetros através do perfil/overlay;
- usa `DecimalMoney` nas comparações críticas;
- associa um snapshot regulamentar ao check;
- mantém compatibilidade controlada com critérios legacy.

O cálculo de renda:

- resolve rule set por perfil e data explícita;
- usa o provider próprio do regime;
- falha de forma controlada quando a configuração está incompleta;
- associa snapshot regulamentar.

Novos contratos copiam o snapshot exato do cálculo de renda. A ativação bloqueia
contratos configurados sem snapshot ou marcados para revisão manual. Contratos
legacy sem classificação explícita mantêm o comportamento histórico e não são
recalculados automaticamente.

## 10. Contratos legacy

Não foi criado nem executado um comando de backfill porque o estado atual não
permite classificar todos os contratos históricos sem ambiguidade.

A cadeia autoritativa definida é:

```text
contrato
→ cálculo de renda
→ candidatura/atribuição
→ concurso/programa
→ snapshot ou perfil fixado
```

Sem essa cadeia, o registo permanece para revisão manual. Uma futura
classificação em lote deverá ser dry-run por defeito, baseada em manifesto
externo, idempotente, auditada e sem PII.

## 11. Scope municipal, autorização e HTTP

Foram reforçados:

- `ProgramPolicy`;
- `ContestPolicy`;
- `EligibilityRuleSetPolicy`;
- `TypologyAdequacyRulePolicy`;
- `AllocationRuleSetPolicy`;
- `MunicipalRecordScopeService`;
- controllers de programas, concursos e rule sets;
- Form Requests de programa e de rule sets.

Garantias:

- Município A não lista nem altera configuração do Município B;
- `municipality_id = null` não concede acesso global;
- acesso global exige assignment estrutural explícito;
- auditor mantém leitura sem mutação;
- candidato permanece fora do backoffice;
- opções de formulários também são filtradas antes de renderizar;
- o browser não escolhe regime/perfil fora do contexto autorizado.

Não foram adicionadas rotas nem permissões wildcard.

## 12. Seeder de Alcanena

Foi criado `AffordableRentRegulatoryProfileSeeder` e integrado no
`DatabaseSeeder`.

O catálogo base cria:

- perfil nacional PAA completo com valores já aprovados no projeto;
- perfil nacional RSAA deliberadamente incompleto;
- referência explícita à ausência da tabela oficial RSAA.

`DemoAlcanenaAffordableRentSeeder` foi atualizado para:

- criar overlay municipal PAA completo;
- criar estrutura municipal RSAA incompleta;
- ligar programa, concurso e rule sets ao perfil PAA;
- obter valores a partir do perfil versionado;
- criar snapshots de programa e concurso;
- manter datas demo explícitas;
- preservar idempotência.

Os dados continuam identificados como demo e não são apresentados como
configuração de produção.

## 13. Testes

Foram criados ou reforçados testes para:

- fronteira 31/08/2026 e 01/09/2026;
- timezone `Europe/Lisbon`;
- preservação PAA depois da transição;
- histórico ambíguo em revisão manual;
- overlays mais exigentes e rejeição de enfraquecimento;
- providers PAA e RSAA;
- publicação fail-closed de programa e concurso;
- ausência real de rule sets obrigatórios;
- payload forjado;
- snapshots imutáveis e estáveis após alteração de regra;
- scope municipal e assignment global explícito;
- auditor e candidato;
- migration apply/rollback em SQLite;
- seeder Alcanena idempotente;
- regressões de elegibilidade, portal, renda e contratos.

Resultados dirigidos finais:

- matriz regulamentar e regressões críticas:
  90 testes, 793 asserções, PASS;
- PHPStan dirigido: zero erros.

Resultados integrais:

- PHPUnit: 1 282 testes, 19 942 asserções, PASS;
- PHPUnit UX: 130 testes, 645 asserções, PASS.

## 14. Quality gates

| Gate | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `composer quality:tests:integrity -- 462875...` | PASS com 1 aviso não bloqueante |
| `composer quality:pint` | PASS |
| `composer quality:pint:changed -- 462875...` | PASS |
| `phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| PHPUnit integral | PASS, 1 282/1 282 |
| PHPUnit UX | PASS, 130/130 |
| `php artisan route:list --except-vendor` | PASS, 1 171 linhas de saída |
| `php artisan migrate:status` | PASS |
| rollback/reaplicação MySQL | PASS |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| artefactos proibidos | PASS |

O aviso da auditoria de integridade resulta da alteração da asserção de 8 para
9 snapshots no teste de elegibilidade. A cobertura foi reforçada com uma
asserção explícita de existência do snapshot `regulatory`; não houve remoção de
proteção comportamental.

## 15. Riscos residuais

1. A tabela/portaria oficial de limites de renda RSAA não está disponível nas
   fontes aprovadas do repositório.
2. Perfis RSAA não podem ser publicados enquanto essa configuração não for
   introduzida e juridicamente validada.
3. Contratos legacy ambíguos exigem revisão e eventual manifesto controlado.
4. Não existe ainda uma UI autónoma de administração do catálogo regulamentar;
   a seleção segura ocorre nos fluxos de programa e concurso.
5. A consolidação das duas fontes históricas de preferências habitacionais
   pertence à Sprint 50E.

## 16. Deployment gates

Antes de usar RSAA num ambiente de destino:

1. obter e validar a fonte oficial aplicável;
2. carregar uma tabela RSAA versionada, com referência e vigência;
3. rever juridicamente o perfil e os overlays municipais;
4. executar migration e testes no ambiente de staging;
5. validar publicação fail-closed e snapshots;
6. não executar classificação automática de contratos legacy.

## 17. Classificação final

`REPOSITORY_PASS_DEPLOYMENT_GATED`

O repositório cumpre os gates técnicos e preserva PAA/legacy. A utilização
operacional do regime RSAA continua bloqueada, por desenho, até existir fonte
oficial completa e validação jurídica.
