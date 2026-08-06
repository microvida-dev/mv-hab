# TECH-MIGRATION-ROLLBACK-001 — Portabilidade dos rollbacks

## Identificação

- branch: `hotfix/migration-rollback-portability`;
- base: `c12ae42db1bdbde863dd5a91b10aa801522cd172`;
- migrations existentes: 88;
- migrations novas: 0;
- migrations alteradas: 16;
- métodos `up()` alterados: 0;
- alterações ao schema aplicado: 0.

## Incidentes reproduzidos

O primeiro ciclo SQLite integral falhava em:

```text
2026_07_26_030000_add_municipality_id_to_communication_logs
```

Erro:

```text
This database driver does not support dropping foreign keys by name.
```

Depois da correção desse caso, o rollback avançou e revelou uma segunda
incompatibilidade em:

```text
2026_07_26_005952_add_municipal_scope_to_visit_domain_tables
```

Erro:

```text
no such table: information_schema.TABLE_CONSTRAINTS
```

A segunda migration executava consultas diretas ao catálogo
`information_schema` antes de distinguir o driver. SQLite não expõe esse
catálogo.

Depois da proteção do domínio de visitas, o rollback avançou até:

```text
2026_07_23_000037_add_municipal_scope_to_access_management_tables
```

Erro:

```text
error in index roles_municipality_lookup_index after drop column:
no such column: municipality_id
```

A migration de metadata de templates de roles criava um índice auxiliar em
`roles.municipality_id` tanto em MySQL/MariaDB como em SQLite. O seu rollback
preservava esse índice para suportar a foreign key histórica no MySQL, mas o
índice sobrevivente impedia o SQLite de eliminar a coluna quando a migration
municipal anterior era revertida.

Depois dessa correção, o rollback avançou até:

```text
2026_06_21_000029_add_structured_extraction_fields_to_document_ai_tables
```

Erro:

```text
error in index document_ai_fields_document_type_index after drop column:
no such column: document_type
```

A auditoria dirigida confirmou um padrão mais amplo: migrations que adicionam
colunas indexadas a tabelas existentes removiam as colunas no `down()` sem
eliminar previamente os índices ou constraints `unique`. SQLite recusa a
alteração enquanto qualquer índice continuar a referenciar a coluna.

## Auditoria consolidada

Foram inventariadas:

- 51 chamadas diretas a `dropForeign()` com nome literal;
- 38 ocorrências sem proteção SQLite em 10 migrations, todas corrigidas;
- 13 ocorrências já protegidas por ramo explícito em 5 migrations;
- 1 chamada dinâmica `dropForeign($foreignKey)`;
- 2 leituras diretas de tabelas `information_schema`.

A chamada dinâmica e as leituras de metadados pertencem à migration do domínio
de visitas. Ficaram protegidas por um ramo SQLite anterior e registadas numa
allowlist estrita.


Foi também inventariado o padrão de helpers que preservam ou recriam índices
durante rollback. Esses helpers passam a exigir uma condição explícita de
driver. O índice auxiliar de `roles` permanece em MySQL/MariaDB, onde suporta a
foreign key após a remoção do índice único composto, e deixa de ser criado em
SQLite, onde não é necessário.


Foi ainda inventariado o ciclo `índice criado no up() → coluna removida no
down()`. O gate calcula nomes implícitos Laravel, reconhece índices simples,
compostos e `unique`, e exige que o rollback elimine cada índice antes de
remover uma das suas colunas. Foram corrigidas antecipadamente todas as
ocorrências ainda alcançáveis no rollback integral, evitando uma nova sequência
de falhas uma a uma.

## Estratégia

As dez migrations com nomes literais usam um helper local que fornece as
colunas em SQLite e o nome físico em MySQL/MariaDB.

A migration do domínio de visitas preserva a implementação histórica de
MySQL/MariaDB e adiciona um caminho SQLite explícito que remove a foreign key
pelas colunas, remove os índices e elimina a coluna antes de retornar.


A migration de metadata de roles mantém o corpo de `up()` inalterado, mas o
helper `ensureMunicipalityIndex()` passa a retornar em SQLite. O comportamento
de produção em MySQL/MariaDB permanece inalterado.


As migrations que adicionam colunas indexadas a tabelas existentes passam a
remover explicitamente, por esta ordem:

1. foreign keys aplicáveis;
2. constraints `unique` e índices simples/compostos;
3. colunas introduzidas pela migration.

Esta alteração limita-se aos métodos `down()` e preserva integralmente o schema
forward.

Semântica preservada:

- SQLite não consulta `information_schema`;
- MySQL/MariaDB mantém a verificação defensiva dos nomes físicos;
- todos os métodos `up()` permanecem inalterados;
- nomes, índices, regras `onDelete`, nulabilidade e dados permanecem
  inalterados.

## Migrations corrigidas

```text
2026_06_10_000002_add_foundation_fields_to_users_table.php
2026_06_10_030000_create_candidate_household_domain.php
2026_06_11_010000_create_process_applications_tables.php
2026_06_11_030000_create_administrative_workflow_tables.php
2026_06_12_020000_create_contractual_tables.php
2026_06_13_010000_create_finance_tables.php
2026_06_14_010000_create_maintenance_inspection_tables.php
2026_06_15_010000_create_communication_document_tables.php
2026_06_18_120000_add_public_portal_fields_to_housing_units_table.php
2026_06_18_120600_add_public_portal_missing_indexes.php
2026_06_21_000028_add_ocr_classification_fields_to_document_ai_analyses.php
2026_06_21_000029_add_structured_extraction_fields_to_document_ai_tables.php
2026_07_24_195430_add_repeatable_requirement_fields_to_document_tables.php
2026_07_25_181312_add_municipal_scope_to_maintenance_catalogs.php
2026_07_26_005952_add_municipal_scope_to_visit_domain_tables.php
2026_07_26_030000_add_municipality_id_to_communication_logs.php
2026_08_01_000055_add_template_metadata_to_roles_table.php
```

## Gate preventivo

O comando `composer quality:migrations:rollback` falha perante:

- `dropForeign()` com nome literal não revisto;
- `dropForeign()` com argumento dinâmico não revisto;
- acesso direto não revisto a tabelas `information_schema`;
- helpers de reparação/preservação de índices sem scope explícito por driver;
- remoção de colunas que permanecem referenciadas por índices ou constraints
  `unique` criados pela mesma migration;
- migrations de compatibilidade que removam no `down()` índices canonicamente
  criados por uma migration anterior.

As exceções são estritas por ficheiro, referência e justificação. O gate também
falha quando uma entrada revista fica obsoleta.

## Validação obrigatória

SQLite isolado:

```text
migrate:fresh
rollback integral
todas as migrations Pending
reaplicação integral
todas as migrations Ran
segunda repetição independente do ciclo
```

MariaDB deve ser validado numa base isolada e descartável. Nunca executar
`migrate:fresh` ou rollback integral sobre staging persistente ou produção.

## Rollout

Esta alteração não exige migration nem alteração de ambiente. Num deploy
normal, os métodos `down()` não são executados. O rollback de release continua
a ser efetuado por symlink; rollback de schema exige procedimento autónomo e
backup verificado.

## Correção v5 — ordem de remoção antes de reconstrução SQLite

O rollback integral revelou que, em SQLite, `dropForeign()`, `dropConstrainedForeignId()` e `dropColumn()` podem reconstruir a tabela e remover automaticamente índices associados. Qualquer `dropIndex()` ou `dropUnique()` executado depois dessa reconstrução pode falhar com `no such index`.

Foram corrigidas:

- `2026_06_18_120000_add_public_portal_fields_to_housing_units_table.php`;
- `2026_06_10_000002_add_foundation_fields_to_users_table.php`.

A ordem canónica passa a ser:

1. remover índices e constraints unique;
2. remover foreign keys;
3. remover colunas.

O gate deteta agora remoções de índices posteriores a operações que reconstruam a mesma tabela. Casos com ramo SQLite específico previamente validado permanecem numa allowlist estrita e documentada.


## Correção v6 — propriedade canónica de índices entre migrations

O rollback integral revelou uma dependência cruzada no portal público. A
migration `2026_06_18_120600_add_public_portal_missing_indexes.php` existe para
restaurar condicionalmente índices ausentes, mas os índices:

- `hu_public_state_idx`;
- `hu_public_coords_idx`;
- `hu_typology_rent_idx`;
- `hupd_unit_public_sort_idx`;
- `hupd_contest_public_idx`;

são canonicamente criados pelas migrations `120000` e `120300`. A migration de
compatibilidade não pode removê-los no `down()`, porque o rollback das migrations
proprietárias ainda necessita de os encontrar e eliminar na sua própria ordem.

O `down()` da migration `120600` passa, por isso, a ser deliberadamente um no-op.
O método `up()` permanece inalterado e continua a reparar instalações históricas
que não possuam os índices.

O gate passa a correlacionar definições explícitas de índices entre migrations
ordenadas cronologicamente. Quando uma migration posterior redefine um índice já
propriedade de uma migration anterior, o respetivo `down()` não pode removê-lo.
