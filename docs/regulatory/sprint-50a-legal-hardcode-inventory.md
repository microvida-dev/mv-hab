# Sprint 50A — Inventário de hardcodes e riscos PAA/RSAA

## Estado

Concluído antes de alterações funcionais.

## Âmbito e fontes consultadas

O inventário cobre o código de produção, migrations, seeders, testes e
documentação relacionados com programas, concursos, elegibilidade, tipologia,
rendas, atribuição e contratos.

Foram consultadas as fontes jurídicas existentes em `docs/00-fontes/`:

- `regime-arrendamento-acessivel-alcanena.pdf`;
- `manual-concursos-habitacao-acessivel.pdf`;
- `requisitos-plataforma.pdf`.

O diretório `docs/03-regulamento-alcanena/` não existe. Não foi inferido
conteúdo em sua substituição. Também não existe no estado atual um relatório
final autónomo da Sprint 49; a implementação e os testes dessa sprint foram
auditados diretamente no histórico e no código.

## Matriz de inventário

| Local | Regra/valor encontrado | Domínio | Risco | Origem presumida | Ação proposta | Compatibilidade | Cobertura existente | Decisão |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `app/Services/Eligibility/EligibilityDataProvider.php:33-35` | RMMG 2026 = 920 € e taxa máxima = 35% como constantes globais | Elegibilidade | Valores municipais/nacionais tornam-se permanentes e aplicáveis fora da vigência | Regulamento de Alcanena, art. 8.º e art. 10.º; RMMG 2026 | Resolver por perfil regulamentar e rule set versionado | Manter as chaves de critérios atuais | `AlcanenaEligibilityRulesTest`, testes determinísticos de elegibilidade | Remover as constantes após existir resolução versionada |
| `app/Services/Eligibility/EligibilityDataProvider.php:81-82,104,120-125` | Cálculos monetários com `float` e comparação direta | Elegibilidade/renda | Arredondamento não determinístico em decisões críticas | Implementação histórica | Migrar decisões para strings decimais e `DecimalMoney` | Payloads públicos continuam com os mesmos campos | Testes de elegibilidade e contratos | Corrigir incrementalmente nos cálculos regulamentares |
| `app/Services/Eligibility/EligibilityDataProvider.php:364-370` | Quadro I: 38 632 € + 10 000 € + 5 000 € | Limite anual de rendimento | Regra PAA aplicada eternamente e a qualquer Município | Portaria n.º 175/2019, alterada pela Portaria n.º 52/2024 | Guardar parâmetros tipados no perfil PAA e snapshot | Preservar critério `annual_income_within_alcanena_limit` | Testes regulamentares de Alcanena | Eliminar o método hardcoded quando o perfil for consumido |
| `app/Services/Eligibility/EligibilityDataProvider.php:355-356` | Validade documental comparada com `today()` | Elegibilidade | Reexecução histórica pode alterar resultado | Regra operacional de validade | Passar data de referência do check | Manter avaliação corrente quando não há check histórico | Testes de elegibilidade | Data explícita no contexto de elegibilidade |
| `app/Services/Eligibility/EligibilityDataProvider.php:386-410` | Fallback para `application_preferences` com query por item | Preferências/elegibilidade | N+1 e coexistência de duas fontes | Compatibilidade legacy | Consolidar na Sprint 50E; manter leitura legacy temporária | Não apagar tabela histórica | Testes de preferências existentes | Inventariar e substituir por reconciliação controlada na 50E |
| `app/Services/Simulator/RentEstimateService.php:19-47` | `float`, fallback implícito de 35% e rule set escolhido por `today()` | Simulador | Simulação pode usar valor sem fonte ou regime errado | Configuração demo/PAA | Exigir rule set/profile aplicável; sem fallback jurídico | Manter resposta defensiva `requires_review` | Testes do simulador | Falhar de forma explicável quando configuração estiver incompleta |
| `app/Models/EligibilityRuleSet.php:75-82` | Scope `active()` usa `now()` | Elegibilidade | Reclassificação silenciosa de checks históricos | Conveniência operacional | Adicionar scope por data explícita e usá-lo nos resolvers históricos | Manter `active()` para listagens correntes | Testes do resolver | Novo `activeAt(referenceDate)`; histórico nunca usa implicitamente o relógio |
| `app/Services/Eligibility/EligibilityRuleSetResolver.php:12-42` | Resolver não recebe data | Elegibilidade | Regra ativa atual substitui regra aplicada no passado | Implementação histórica | Receber data explícita e preferir snapshot persistido | Assinatura antiga mantém wrapper seguro apenas para contexto corrente | Testes unitários do resolver | Criar API temporal sem remover métodos existentes |
| `app/Models/RentRuleSet.php:75-82` | Scope `active()` usa `today()` | Renda | Cálculo/revisão histórica pode selecionar conjunto novo | Conveniência operacional | Adicionar `activeAt(referenceDate)` | Manter scope para UI corrente | Testes de renda determinística | Resolver de cálculo passa sempre data autoritativa |
| `app/Services/Contracts/RentRuleSetResolver.php:20-39` | Resolver seleciona rule set corrente | Renda/contratos | Contrato PAA pode ser recalculado com RSAA | Implementação histórica | Resolver por data e snapshot regulamentar da atribuição/candidatura | Cálculos existentes mantêm `rent_rule_set_id` | Testes de cálculo e ciclo contratual | Bloquear mudança de regime e conservar rule set aplicado |
| `app/Services/Contracts/RentCalculationService.php:39-98` | Cálculo guarda snapshot de dados, mas não regime jurídico | Renda | Falta prova imutável do perfil/overlay aplicado | Arquitetura de contratos existente | Associar `RegulatorySnapshot` e incluir checksum/IDs de regras | Não alterar resultados já gravados | `RentCalculationDeterministicTest` | Snapshot regulamentar separado e referenciado |
| `app/Services/Contracts/RentSnapshotService.php:15-110` | Snapshot operacional contém rule set, agregado e habitação | Renda/RGPD | Mistura contexto pessoal com configuração jurídica | Implementação existente | Manter snapshot operacional privado e referenciar snapshot regulamentar sem PII | Sem migração destrutiva | Testes de contratos | Não duplicar PII no snapshot regulamentar |
| `app/Services/Contracts/LeaseContractService.php:37-159` | Contrato nasce do cálculo aprovado sem regime/snapshot explícito | Contratos | Histórico legal depende de relações mutáveis | Implementação existente | Copiar regime e snapshot bloqueado do cálculo | Contratos existentes ficam não classificados até revisão segura | Testes de lifecycle | Novo contrato exige snapshot; legacy não é adivinhado |
| `app/Services/Contracts/ContractActivationService.php:24-102` | Ativação não confirma readiness regulamentar | Contratos | Ativação de contrato com configuração legal incompleta | Implementação existente | Validar snapshot/regime para contratos novos classificados | Não bloquear contratos legacy já ativos | Testes de ativação | Fail-closed para contratos configurados após a Sprint 50A |
| `app/Services/Programs/ProgramService.php:75-102` | Publicação valida apenas existência de regra pública | Programas | Programa RSAA pode ser publicado sem tabela oficial | Implementação existente | Integrar `RegulatoryPublicationReadinessService` e snapshot | Registos já publicados não são republicados nem reclassificados | `Sprint3PortalProgramsTest`, testes permission-first | Publicação nova exige perfil completo |
| `app/Services/Contests/ContestService.php:81-116` | Publicação valida programa e prazos, não configuração jurídica | Concursos | Concurso RSAA incompleto fica público | Implementação existente | Validar perfil, vigência, rule sets, renda, tipologia e overlay | Concursos já publicados preservados | `Sprint3PortalProgramsTest`, testes de segurança | Publicação nova fail-closed |
| `database/seeders/DemoAlcanenaAffordableRentSeeder.php:71-75` | RMMG e taxa de esforço como constantes do seeder | Dados demo | Seeder torna-se fonte de verdade transversal | Regulamento e dados de 2026 | Criar perfis PAA/RSAA e obter valores do catálogo versionado | Seeder continua idempotente e explicitamente demo | `AlcanenaAffordableRentSeederTest` e QA demo | Valores passam para perfil/rule sets versionados |
| `database/seeders/DemoAlcanenaAffordableRentSeeder.php:115` | Base legal PAA no programa | Programas | Programa atravessa 01/09/2026 sem regime explícito | Edital n.º 1820/2024 e diplomas PAA | Ligar programa/concurso legacy ao perfil PAA | Manter texto legal original no histórico | Testes regulamentares | Fixar perfil PAA e snapshot |
| `database/seeders/DemoAlcanenaAffordableRentSeeder.php:136-138` | Concurso abre antes e fecha depois da transição | Concursos | Data de fecho poderia reclassificar o concurso como RSAA | Dados demo | Usar publicação/abertura e perfil persistido; nunca a data de fecho | Concurso demo mantém PAA | Testes de fronteira novos | Regime do procedimento é fixado na publicação |
| `database/seeders/DemoAlcanenaAffordableRentSeeder.php:194-196` | 5 anos, 9 meses e 35% em regras textuais | Contratos/programa | Texto não é parâmetro executável nem versionado | Regulamento de Alcanena | Manter texto público e guardar parâmetros tipados no overlay | Sem alterar conteúdo publicado | Testes do seeder | Perfil municipal guarda termos estruturados |
| `database/seeders/DemoAlcanenaAffordableRentSeeder.php:359-377` | Quadro I, RMMG e 35% repetidos no `expected_value` | Elegibilidade | Divergência entre critério e serviço | Portarias PAA e regulamento municipal | Popular critérios a partir do perfil versionado | Preservar códigos e labels | Testes de critérios | Perfil é fonte; critérios ficam snapshot executável |
| `database/seeders/DemoAlcanenaAffordableRentSeeder.php:840-873` | Renda 320–470 €, 35% e caução | Renda | Valores demo podem ser confundidos com tabela oficial | Edital/dados fictícios declarados | Marcar fonte demo e associar perfil PAA; não usar em RSAA | Manter cenários PAA existentes | Testes de renda | Provider PAA aceita tabela versionada; provider RSAA recusa ausência |
| `database/seeders/DemoAlcanenaAffordableRentSeeder.php:944-965` | Prazo e taxa em cláusulas contratuais | Contratos | Template pode ser reutilizado fora do regime | Regulamento municipal | Associar template indiretamente ao programa/concurso fixado | Manter cláusulas PAA existentes | Testes de contratos | Snapshot do contrato identifica o perfil |
| `database/seeders/RentRuleSetSeeder.php:28` | Taxa genérica de 30% | Renda base | Valor sem fonte explícita | Seeder base histórico | Não tratar como regra nacional; exigir perfil nos fluxos regulados | Manter apenas como catálogo demo/legacy | Testes de seeders | Documentar e excluir de resolução regulamentar PAA/RSAA |
| `database/seeders/ProgramSeeder.php:41-48` | Programa genérico com datas relativas | Portal/demo | Dados variam por execução e não identificam regime | Seeder demo | Não usar como catálogo regulamentar | Preservar testes de portal | Testes de portal | Fora da fonte regulamentar |
| `app/Services/Allocation/TypologyAdequacyService.php:13-95` | Rule set de tipologia sem perfil ou data | Tipologia/atribuição | Regra atual pode alterar candidatura histórica | Configuração existente | Associar regras a perfil e consumir snapshot na 50E | Manter API atual para fluxos legacy | Testes de atribuição | API nova recebe contexto regulamentar |
| `app/Models/Program.php:164-176` e `app/Models/Contest.php` | `now()/today()` na visibilidade pública | Portal | Potencial confusão com resolução legal | Estado operacional atual | Manter: serve visibilidade corrente, não classificação histórica | Nenhuma alteração necessária | Testes públicos | Aceite, desde que não seja reutilizado pelo resolver legal |
| `app/Services/Programs/ProgramService.php`, `app/Services/Contests/ContestService.php` | `published_at = now()` | Auditoria/publicação | Nenhum, se a data for evento real | Operação atual | Usar como data autoritativa de publicação e referência do snapshot | Preservar | Testes de publicação | Aceite |

## Precedência regulamentar decidida

1. Snapshot regulamentar bloqueado do registo.
2. Perfil regulamentar explicitamente associado ao programa/concurso.
3. Perfil aplicável à data autoritativa e ao Município, apenas para registos
   novos ainda não publicados.
4. Caso histórico sem snapshot, perfil ou data autoritativa inequívoca:
   revisão manual; nunca inferência por `created_at` ou pelo relógio atual.

O overlay municipal pode restringir parâmetros nacionais, mas nunca aumentar
máximos nacionais, desativar condições obrigatórias ou preencher uma tabela
oficial RSAA inexistente.

## Datas autoritativas

| Contexto | Data primária | Fallback permitido | Sem data |
| --- | --- | --- | --- |
| Programa | `published_at` | `starts_at` enquanto rascunho | Bloquear publicação |
| Concurso | `published_at` | `opens_at` enquanto rascunho | Bloquear publicação |
| Candidatura | `submitted_at` | concurso fixado enquanto rascunho | Revisão manual |
| Elegibilidade | `executed_at` | candidatura/concurso fixado antes da execução | Bloquear execução |
| Cálculo de renda | `calculated_at` | snapshot da candidatura/atribuição | Bloquear cálculo |
| Contrato | `start_date`/execução formal | snapshot do cálculo de renda | Revisão manual |

## Hardcodes não eliminados nesta sprint

- Textos legais e labels históricos são preservados como prova do procedimento.
- Datas operacionais (`now()` em auditoria, timestamps e visibilidade corrente)
  não são hardcodes regulamentares.
- Regras de scoring do Anexo I ficam fora do âmbito, sem alteração.
- A tabela oficial de rendas RSAA não existe nas fontes do repositório. O
  perfil RSAA será criado como incompleto e a publicação ficará bloqueada.

## Decisão final do inventário

Prosseguir com uma migration incremental que cria perfis e snapshots, adiciona
ligações opcionais aos domínios existentes e não executa backfill ambíguo.
Dados demo de Alcanena serão ligados explicitamente ao perfil PAA; o perfil
RSAA ficará incompleto até existir fonte oficial configurada.
