# Sprint 50E — Inventário e consolidação de preferências habitacionais

## Estado

Concluído antes de alterações funcionais.

## Objetivo

Consolidar a seleção de habitações compatíveis sem criar um terceiro sistema,
sem apagar histórico e sem alterar regras regulamentares por inferência.

## Fontes existentes

### `housing_preferences`

Criada na migration de atribuição
`2026_06_12_010000_create_allocation_tables.php`.

Contém:

- candidatura;
- candidato;
- concurso;
- relação concreta `contest_housing_unit_id`;
- habitação;
- ordem;
- notas;
- data de submissão;
- soft delete.

Integridade existente:

- FK restritiva para candidatura, utilizador, concurso, relação concurso/fogo e
  habitação;
- ordem única por candidatura;
- habitação de concurso única por candidatura.

Readers:

- `HousingPreferenceController`;
- `HousingPreferenceService`;
- `PreferenceAllocationService`;
- relações de `Application`, `Contest`, `ContestHousingUnit` e `User`;
- páginas `candidate/housing-preferences`;
- testes e seeder de atribuição.

Writer oficial atual:

- `HousingPreferenceService::replace()`.

Limitações encontradas:

- a página envia todas as unidades disponíveis, não uma seleção explícita;
- não existe mínimo/máximo configurável;
- o `exists` HTTP é global;
- não existe snapshot de compatibilidade;
- não existe invalidação/revalidação;
- a submissão da candidatura não bloqueia nem reavalia preferências;
- o serviço de atribuição aplica fallback para uma unidade não escolhida.

### `application_preferences`

Criada anteriormente na migration processual
`2026_06_11_010000_create_process_applications_tables.php`.

Contém:

- candidatura;
- habitação;
- ordem;
- notas.

Integridade existente:

- unidade única por candidatura;
- ordem única por candidatura.

Readers:

- `EligibilityDataProvider`;
- `ProcedureMinutePayloadBuilder`;
- relação `Application::preferences()`;
- testes/factories de atas e candidatura.

Writers:

- não foi encontrado writer HTTP ativo no fluxo atual;
- factories e dados históricos ainda podem criar registos.

Limitações:

- não guarda concurso nem `contest_housing_unit_id`;
- não prova que a habitação pertencia ao concurso;
- não guarda avaliação regulamentar;
- não suporta lock ou invalidação;
- a correspondência com `housing_preferences` só é segura quando existe uma
  única relação concurso/habitação.

## Decisão de fonte oficial

`housing_preferences` passa a ser a fonte oficial de escrita, validação,
submissão, snapshot e atribuição.

`application_preferences` fica classificada como legacy:

- não é eliminada;
- não recebe novas escritas pelo fluxo consolidado;
- pode ser reconciliada apenas quando candidatura, concurso, habitação, relação
  concurso/habitação, ordem e candidato são inequívocos;
- casos ambíguos permanecem intactos e são reportados para revisão.

Durante a compatibilidade transitória, readers documentais devem preferir
`housing_preferences` e usar a relação legacy apenas quando não existir fonte
oficial.

## Regras de configuração

`AllocationRuleSet` receberá:

- `minimum_preferences`;
- `maximum_preferences`;
- `preferences_required_before_submission`;
- `allow_unselected_unit_fallback`;
- `preference_selection_starts_at`;
- `preference_selection_ends_at`.

Defaults de schema preservam concursos existentes:

- mínimo 0;
- máximo 3;
- seleção não obrigatória;
- fallback legacy permitido;
- janela sem limites adicionais.

A configuração demo de Alcanena será explícita:

- preferências permitidas;
- mínimo 1;
- máximo 3;
- obrigatórias antes da submissão;
- sem fallback para unidade não selecionada.

## Contexto de compatibilidade

Cada preferência oficial conservará:

- estado de compatibilidade;
- snapshot explicável;
- snapshot regulamentar;
- data de avaliação;
- data e motivo de invalidação;
- lock de submissão.

A compatibilidade será calculada por service puro a partir da candidatura,
unidade do concurso, perfil/snapshot regulamentar, agregado, rendimentos,
tipologia, capacidade, acessibilidade, disponibilidade e renda.

Valores jurídicos não serão codificados no service. Quadro I, taxa de esforço e
demais limites provêm do perfil/rule sets versionados da Sprint 50A.

## Pontos de integração

### HTTP e UX

- manter as rotas candidate existentes;
- reforçar Form Requests e Policy;
- filtrar opções no servidor;
- integrar “Habitações pretendidas” na navegação da candidatura;
- seleção e ordenação por controlos acessíveis.

### Submissão

`ApplicationValidationService` verificará a configuração e as preferências.
`ApplicationSubmissionService` bloqueará candidatura e preferências, reavaliará
no servidor, criará snapshot e só depois submeterá.

### Snapshots e documentos

Adicionar `HousingPreferences` a `ApplicationSnapshotType` e reutilizar a mesma
serialização em:

- snapshot da candidatura;
- comprovativo;
- dossier documental;
- atas que já apresentam preferências.

### Atribuição

`PreferenceAllocationService` deverá respeitar estritamente a ordem bloqueada.
O fallback só pode existir quando a configuração o permite e houver base
expressa. Em Alcanena será desativado.

## Reconciliação legacy

A reconciliação será implementada como comando idempotente, dry-run por
defeito. Um registo só é migrado quando:

- a candidatura existe;
- o candidato e o concurso são conhecidos;
- existe exatamente um `contest_housing_unit` para o par
  concurso/habitação;
- a ordem não colide;
- não existe preferência oficial equivalente.

Nenhum caso ambíguo é inventado. Não haverá drop nem update destrutivo da
tabela legacy.

## Riscos e mitigação

| Risco | Mitigação |
| --- | --- |
| Duas fontes divergentes | Fonte oficial explícita e fallback legacy só de leitura |
| Unidade de outro Município | Filtro por concurso e Município antes de avaliar |
| Dados incompletos tratados como zero | Estado `requires_data` |
| RSAA incompleto | Estado `configuration_incomplete`, não selecionável |
| Mudança de agregado/rendimento | Invalidar sem apagar preferências |
| Unidade indisponível entre seleção e submissão | Reavaliação transacional com locks |
| Regras alteradas após submissão | Snapshot e preferências bloqueados |
| Atribuição de quarta unidade não escolhida | Fallback desativado por configuração |
| Backfill ambíguo | Dry-run e omissão documentada |

## Decisão final

Prosseguir com migrations incrementais e reversíveis, `housing_preferences`
como fonte oficial e `application_preferences` preservada como legacy.
