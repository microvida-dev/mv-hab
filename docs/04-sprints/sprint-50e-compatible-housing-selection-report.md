# Sprint 50E — Seleção de Habitações Compatíveis

## 1. Resumo

A Sprint 50E consolidou a seleção de habitações no fluxo da candidatura,
reutilizando `housing_preferences` como fonte oficial e preservando
`application_preferences` como fonte legacy de leitura.

A solução:

- filtra no servidor apenas habitações compatíveis;
- consome o perfil e o snapshot regulamentar da Sprint 50A;
- usa regras de rendimento, esforço e tipologia versionadas;
- permite uma a três escolhas ordenadas na configuração de Alcanena;
- revalida e bloqueia as escolhas durante a submissão;
- conserva as preferências no snapshot, comprovativo e dossier;
- atribui estritamente pela ordem submetida;
- mantém o candidato em reserva quando nenhuma escolha está disponível;
- não reserva uma habitação no momento da seleção.

Não foi criado um terceiro sistema de preferências, não foi removida a tabela
legacy e não foram alteradas regras de scoring, listas ou sorteios.

## 2. Branch e commit-base

- Branch: `sprint-50e-compatible-housing-selection`
- Commit-base publicado da Sprint 50A:
  `53f34444e3ebff10b539799301060de8cf699898`
- Branch de origem:
  `sprint-50a-paa-rsaa-legal-transition`
- Pasta:
  `/Users/brunocorreia/Documents/CRM HAB/MV-HAB`

A Sprint 50E só foi iniciada depois de a 50A estar publicada, com HEAD local e
remoto iguais e gates verdes. A branch `main` não foi alterada.

## 3. Inventário e decisão de fonte oficial

O inventário prévio está em:

- `docs/architecture/sprint-50e-preference-consolidation-inventory.md`.

Foram identificadas duas fontes:

### `housing_preferences`

Possui candidatura, candidato, concurso, `contest_housing_unit_id`, habitação,
ordem e data de submissão. É a única fonte com contexto suficiente para
validação, lock, snapshot e atribuição.

Decisão:

- fonte oficial de escrita;
- fonte oficial de submissão;
- fonte oficial de snapshot;
- fonte oficial de atribuição.

### `application_preferences`

Não possui `contest_housing_unit_id`, snapshot regulamentar, avaliação,
invalidação ou lock.

Decisão:

- manter a tabela e os dados;
- não efetuar novas escritas pelo fluxo consolidado;
- usar apenas como fallback de leitura transitório;
- reconciliar apenas correspondências inequívocas;
- não eliminar a tabela nesta sprint.

## 4. Migration

Foi criada:

- `database/migrations/2026_07_27_000042_add_compatible_housing_preference_context.php`.

### `allocation_rule_sets`

Foram adicionados:

- `minimum_preferences`;
- `maximum_preferences`;
- `preferences_required_before_submission`;
- `allow_unselected_unit_fallback`;
- `preference_selection_starts_at`;
- `preference_selection_ends_at`.

### `housing_preferences`

Foram adicionados:

- `compatibility_status`;
- `compatibility_snapshot`;
- `regulatory_snapshot_id`;
- `evaluated_at`;
- `invalidated_at`;
- `invalidation_reason`;
- `locked_at`;
- `legacy_application_preference_id`.

Foram criados índices para candidatura/compatibilidade, snapshot regulamentar
e reconciliação legacy, com foreign keys restritivas em MySQL/MariaDB.

### Validação real

A migration foi:

1. aplicada em MySQL/MariaDB no batch 15;
2. revertida isoladamente com `migrate:rollback --step=1`;
3. reaplicada;
4. confirmada como `Ran` em `migrate:status`.

O teste SQLite também executa `up()`, `down()` e nova aplicação. A tabela
`application_preferences` e os seus dados de teste permanecem disponíveis.

## 5. Reconciliação legacy

Foi criado:

- `preferences:reconcile-legacy`;
- opção explícita `--apply`.

O comando é dry-run por defeito, processa em chunks e só cria uma preferência
oficial quando:

- a candidatura existe;
- o concurso é conhecido;
- existe exatamente uma relação concurso/habitação;
- a ordem e a habitação não colidem;
- ainda não existe reconciliação equivalente.

As preferências importadas ficam em `requires_revalidation`. Casos ambíguos ou
em conflito são contabilizados e omitidos. A aplicação é idempotente e cada
alteração é auditada sem incluir PII.

## 6. Motor de compatibilidade

Foram criados:

- `HousingCompatibilityService`;
- `HousingCompatibilityResult`;
- `CompatibleHousingOptionData`;
- `HousingCompatibilityStatus`;
- `HousingTypology`.

Estados:

- `compatible`;
- `incompatible`;
- `requires_data`;
- `requires_manual_review`;
- `configuration_incomplete`;
- `requires_revalidation`.

API:

- `optionsFor(Application)`;
- `evaluate(Application, ContestHousingUnit)`;
- `assertCompatible(Application, ContestHousingUnit)`;
- resumo explicável para a UI.

O motor não depende de `Request`, não autoriza dentro do cálculo puro e não
usa `float` nas decisões monetárias.

## 7. Regras regulamentares consumidas

O motor usa o perfil e o snapshot regulamentar fixados pela Sprint 50A.
Nenhum valor jurídico permanente foi adicionado ao service.

Parâmetros consumidos do snapshot versionado:

- limite anual base;
- incremento da segunda pessoa;
- incremento por pessoa adicional;
- rendimento mensal mínimo dos adultos;
- taxa máxima de esforço;
- metadata de exceção de tipologia superior.

As regras de tipologia são obtidas de `TypologyAdequacyRule`, ligadas ao perfil,
programa e concurso. `HousingTypology` converte `T1` a `T99` numericamente,
evitando comparação lexical.

São avaliados cumulativamente:

- candidatura em rascunho;
- concurso e janela de seleção;
- Município;
- disponibilidade e publicação;
- agregado e rendimentos completos;
- limite anual;
- rendimento mínimo dos adultos;
- taxa de esforço;
- tipologia, quartos e capacidade;
- acessibilidade e mobilidade reduzida;
- condições especiais suportadas pelo domínio;
- configuração regulamentar completa.

Rendimento incompleto devolve `requires_data`; não é convertido em zero.
RSAA incompleto devolve `configuration_incomplete` e a unidade não é
selecionável. Uma exceção de tipologia superior sem prova regulamentar completa
fica para revisão manual.

## 8. Consulta e performance

`optionsFor()`:

- filtra primeiro por concurso;
- filtra o Município através da habitação;
- exige unidade disponível e publicamente selecionável;
- usa `select()` explícito;
- usa eager loading de habitação, imagem principal e características;
- ordena por tipologia, renda e ID;
- não executa queries no Blade;
- só entrega opções compatíveis à view.

O teste de query count compara 1 e 20 cards. O número de queries permanece
limitado e independente do número de opções, prevenindo N+1.

## 9. UX do candidato

Foi integrado o passo **Habitações pretendidas** na candidatura, sem o
adicionar às quatro etapas do perfil pessoal.

A página apresenta:

- regras de mínimo e máximo;
- resumo do agregado e rendimentos;
- perfil/regime regulamentar;
- limite anual;
- renda mensal máxima estimada;
- tipologias adequadas;
- número de opções compatíveis;
- cards de habitação com dados públicos minimizados;
- mensagem explícita de que a seleção não constitui reserva.

Cada opção pode apresentar referência pública, localização, freguesia,
tipologia, área, quartos, renda, taxa de esforço, ocupação, acessibilidade,
características, ficha pública e visita, quando existirem rotas válidas.

A ordenação possui:

- drag-and-drop progressivo;
- botões **Subir** e **Descer**;
- remoção;
- posições visíveis;
- labels;
- foco;
- controlos de teclado;
- atualização de mensagens para tecnologias de apoio.

O estado vazio encaminha para agregado, rendimentos, regras e apoio.

## 10. HTTP, Policy e validação

Foram reforçados:

- `HousingPreferenceController`;
- `HousingPreferencePolicy`;
- `StoreHousingPreferenceRequest`;
- `UpdateHousingPreferenceRequest`.

O controller permanece fino. A Policy exige:

- role candidate;
- candidatura do próprio utilizador;
- candidatura em rascunho;
- ausência de preferências bloqueadas;
- ausência de execução de atribuição;
- permissão existente.

O Form Request valida estrutura, limite, IDs distintos e ordens distintas. O
service valida o domínio real: ownership, concurso, Município, janela,
disponibilidade, compatibilidade, lock, atribuição e ordem consecutiva.

Não foi usado um `exists` global como substituto da validação de domínio.
Não foram adicionadas rotas nem permissões.

## 11. Guardar, invalidar e revalidar

`HousingPreferenceService` passou a:

- substituir escolhas dentro de transação;
- bloquear a candidatura com `lockForUpdate`;
- validar mínimo, máximo, duplicados e ordens consecutivas;
- guardar avaliação e snapshot explicável;
- impedir alterações após lock ou atribuição;
- auditar criação, atualização, ordenação e confirmação.

`HousingPreferenceInvalidationService` marca escolhas não bloqueadas como
`requires_revalidation`, sem as apagar, quando mudam:

- agregado;
- membros;
- rendimentos;
- situação habitacional.

A mensagem de invalidação é preservada para correção pelo candidato.

## 12. Submissão

`ApplicationValidationService` inclui um check explícito de preferências com
rota de correção.

`ApplicationSubmissionService`:

1. abre transação;
2. bloqueia a candidatura;
3. confirma readiness e estado draft;
4. fixa/atualiza o snapshot regulamentar de submissão;
5. bloqueia preferências e unidades relevantes;
6. reavalia todas as escolhas no servidor;
7. confirma ordem, disponibilidade e compatibilidade;
8. guarda avaliação final e bloqueia as preferências;
9. cria os snapshots da candidatura;
10. submete e audita.

Uma unidade que fique indisponível entre seleção e submissão bloqueia o fluxo
com mensagem em português e gera auditoria minimizada.

## 13. Snapshots, comprovativo e dossier

Foi adicionado `HousingPreferences` a `ApplicationSnapshotType`.

`HousingPreferenceSnapshotService` serializa:

- ordem;
- relação concurso/habitação;
- habitação;
- referência e título públicos;
- renda e tipologia;
- estado e snapshot de compatibilidade;
- snapshot regulamentar;
- datas de avaliação, submissão e lock;
- origem oficial ou legacy.

O mesmo formato é reutilizado por:

- `ApplicationSnapshotService`;
- `ApplicationReceiptService`;
- comprovativo web e impressão;
- `DocumentDossierBuilder`;
- `DocumentDossierExportService`;
- payload de atas que já apresentava preferências.

Os snapshots existentes passaram a usar `firstOrCreate`, impedindo reescrita
silenciosa depois de criados.

## 14. Atribuição

`PreferenceAllocationService` usa apenas preferências:

- submetidas;
- bloqueadas;
- não invalidadas;
- compatíveis;
- ligadas ao mesmo snapshot regulamentar da candidatura.

A ordem é estrita:

1. primeira escolha disponível;
2. segunda escolha;
3. terceira escolha;
4. reserva.

Não existe fallback para uma quarta habitação não selecionada. Mesmo que um
rule set legacy permita fallback, a Sprint 50E não o executa sem o futuro
registo estrutural de aceitação expressa exigido pelo regulamento.

A disponibilidade atual da unidade continua a ser verificada na execução.

## 15. Auditoria e RGPD

Foram adicionados eventos minimizados para:

- preferências guardadas;
- preferências confirmadas;
- reordenação;
- invalidação;
- revalidação;
- lock de submissão;
- recusa durante submissão;
- reconciliação legacy;
- atribuição por preferência;
- entrada em reserva.

Os metadados não incluem payload HTTP integral, NIF, IBAN, documentos, recibos
ou dados pessoais desnecessários.

As views só recebem opções previamente filtradas. Uma candidatura de outro
utilizador responde 403 e uma unidade de outro Município não é revelada.

## 16. Seeder de Alcanena

`DemoAlcanenaAffordableRentSeeder` configura explicitamente:

- preferências ativas;
- mínimo 1;
- máximo 3;
- seleção obrigatória antes da submissão;
- fallback para unidade não selecionada desativado;
- ligação ao perfil regulamentar versionado.

Os dados mantêm natureza demo e não substituem configuração de produção.

## 17. Testes

Foram criados ou reforçados testes para:

- agregados de 1, 2, 3 e 7 pessoas;
- Quadro II, quartos e capacidade real;
- limite anual, rendimento mínimo e taxa de esforço;
- rendimentos incompletos;
- PAA, RSAA e RSAA incompleto;
- janela de seleção;
- acessibilidade;
- concurso, Município, publicação e disponibilidade;
- ownership e 403;
- mínimo, máximo, duplicados e ordem consecutiva;
- seleção sem reserva e seleção concorrente;
- invalidação por agregado e rendimentos;
- submissão obrigatória e revalidação no servidor;
- unidade indisponível na submissão;
- snapshots imutáveis;
- comprovativo e dossier;
- primeira, segunda e terceira preferência;
- reserva sem fallback;
- reconciliação legacy idempotente;
- migration apply/rollback/reapply;
- query count sem N+1;
- regressões da Sprint 12 e documentos repetíveis.

Resultados dirigidos finais:

- 32 testes;
- 195 asserções;
- PASS.

Resultados integrais:

- PHPUnit: 1 304 testes, 20 070 asserções, PASS;
- PHPUnit UX: 130 testes, 645 asserções, PASS.

## 18. Quality gates

| Gate | Resultado |
| --- | --- |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `composer quality:tests:integrity -- 53f34444...` | PASS, 0 violações e 0 avisos |
| `composer quality:pint` | PASS |
| `composer quality:pint:changed -- 53f34444...` | PASS, 56 ficheiros |
| `phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| PHPUnit dirigido | PASS, 32/32 |
| PHPUnit integral | PASS, 1 304/1 304 |
| PHPUnit UX | PASS, 130/130 |
| `php artisan route:list --except-vendor` | PASS, 1 167 rotas |
| `php artisan migrate:status` | PASS |
| rollback/reaplicação MySQL | PASS |
| rollback/reaplicação SQLite | PASS |
| `npm run build` | PASS |
| `git diff --check` | PASS |
| artefactos proibidos | PASS |

O scan de artefactos encontrou apenas `.gitignore` e `.gitkeep` históricos em
`storage/framework` e `storage/logs`; não existem logs, sessões, caches,
ficheiros `.env`, `vendor` ou `node_modules` indevidamente versionados.

Não houve alteração de rotas; por isso o audit adicional de novas rotas
backoffice não foi aplicável.

## 19. Riscos residuais

1. A tabela oficial de rendas RSAA continua ausente das fontes aprovadas. Um
   concurso RSAA incompleto permanece fail-closed.
2. O domínio atual não possui campo ou enum de modalidade residencial. Não foi
   inventada uma correspondência. Um concurso que dependa dessa dimensão exige
   modelação regulamentar explícita antes de disponibilizar opções.
3. Preferências legacy ambíguas permanecem por reconciliar e requerem revisão.
4. A exceção de tipologia superior só pode ser automatizada quando existir
   tabela versionada que prove o limite de renda da tipologia adequada.
5. A seleção pode tornar-se extensa em concursos com muitas unidades; o query
   count está controlado, mas deve ser monitorizado o tamanho do payload.
6. O fallback para unidade não selecionada fica deliberadamente desativado
   enquanto não existir aceitação expressa, auditável e juridicamente aprovada.

## 20. Deployment gates

Antes de ativar a seleção num Município/ambiente de destino:

1. aplicar a migration e confirmar índices/foreign keys;
2. rever o rule set de atribuição do concurso;
3. carregar perfil e snapshot regulamentar completos;
4. validar Quadro I, Quadro II, esforço, renda e condições especiais;
5. confirmar que a configuração RSAA usa fonte oficial versionada;
6. executar o comando legacy primeiro sem `--apply`;
7. rever casos ambíguos antes de qualquer reconciliação;
8. validar o fluxo candidato em staging;
9. confirmar comprovativo, dossier e atribuição;
10. monitorizar volume de opções e concorrência de submissão.

## 21. Classificação final

`REPOSITORY_PASS_DEPLOYMENT_GATED`

O repositório cumpre os gates técnicos e funcionais da Sprint 50E. A ativação
de concursos RSAA em ambiente de destino continua condicionada à fonte oficial
de rendas e à configuração regulamentar completa, sem fallback PAA.

## Hardening 50E.1

A Sprint 50E.1 reforçou a implementação sem alterar o objetivo funcional desta
sprint:

- persistiu a origem estrutural `uninitialized`, `legacy`, `official`,
  `reconciled` ou `requires_manual_review`;
- centralizou todos os readers num resolver fail-closed;
- impediu que uma origem oficial vazia reative preferências legacy;
- tornou a substituição de rascunhos compatível com os índices únicos reais de
  MySQL/MariaDB;
- limitou snapshots finais à submissão e reforçou unicidade, imutabilidade,
  idempotência e concorrência;
- passou a exigir snapshot final oficial na atribuição;
- centralizou autorização na candidatura e ownership;
- cobriu writers candidate, backoffice, correções, renovações e atualização
  anual com invalidação por evento de domínio;
- reforçou teclado, foco, `aria-live` e estado vazio na página de seleção.

Foi criada a migration incremental e reversível
`2026_07_27_000044_add_application_preference_source_state.php`. Não foram
apagados snapshots, `application_preferences` ou dados históricos.

Validação final:

- 48 testes focados e 218 asserções;
- 4 testes MySQL de migration/índices e 23 asserções;
- concorrência MySQL com uma única linha final;
- 1 356 testes integrais e 20 276 asserções;
- 130 testes UX e 645 asserções;
- PHPStan com 0 erros;
- Pint integral e incremental;
- build Vite;
- 1 167 rotas antes e depois, com diff estrutural vazio;
- browser real em desktop/tablet sem overflow ou erros de consola.

O relatório detalhado está em:

- `docs/04-sprints/sprint-50e1-preference-integrity-hardening-report.md`.

As fontes PAA, RSAA e do limite superior do 6.º escalão do IRS continuam
fechadas quando não estão oficialmente instaladas e validadas. Dados demo
permanecem `demo_only`.

Classificação do hardening:
`REPOSITORY_PASS_DEPLOYMENT_GATED`.
