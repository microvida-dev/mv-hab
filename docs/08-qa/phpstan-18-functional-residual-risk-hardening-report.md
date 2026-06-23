# Relatorio PHPSTAN-18 - Functional Residual Risk Hardening

Data de execucao: 2026-06-23

## Resumo executivo

A sprint PHPSTAN-18 atacou os erros residuais de maior risco funcional apos a PHPSTAN-17.

Resultado final:

- PHPStan inicial: 96 erros wrapper / 85 assinaturas normalizadas.
- PHPStan final: 14 erros wrapper / 14 assinaturas normalizadas.
- Reducao liquida: 82 erros wrapper / 71 assinaturas normalizadas.
- Erros novos: 0.
- Estado: excellent, abaixo da meta excelente de 25 erros.

Nao foram criadas migrations, rotas, controllers, seeders, factories ou dependencias.

## Metricas antes/depois

| Metrica | Antes | Depois |
| --- | ---: | ---: |
| Erros wrapper | 96 | 14 |
| Assinaturas normalizadas | 85 | 14 |
| Ficheiros afetados | 42 | 11 |
| Erros novos | 0 | 0 |

## Fases executadas

| Fase | Ficheiro | Resultado |
| --- | --- | --- |
| Baseline | `storage/phpstan/phpstan-18-before.txt` | 96 erros |
| Method non-object | `storage/phpstan/phpstan-18-after-method-non-object.txt` | 41 erros, new=0 |
| Property non-object | `storage/phpstan/phpstan-18-after-property-non-object.txt` | 22 erros, new=0 |
| Impossible comparisons | `storage/phpstan/phpstan-18-after-impossible-comparisons.txt` | 14 erros, new=0 |
| Dead code | `storage/phpstan/phpstan-18-after-dead-code.txt` | 14 erros, new=0 |
| Argument type | `storage/phpstan/phpstan-18-after-argument-type.txt` | 14 erros, new=0 |
| Final | `storage/phpstan/phpstan-18-final.txt` | 14 erros, new=0 |

## Correcoes por tipo

| Tipo | Classificacao | Decisao |
| --- | --- | --- |
| `method.nonObject` | BR/RF | Corrigido com guards explicitos ou condicionais fora de `when()` |
| `property.nonObject` | BR/RF | Corrigido com validacao de dominio ou PHPDoc de casts reais |
| Enum/string mismatch | BR/TS | Corrigido com PHPDoc de propriedades castadas em models |
| `deadCode.unreachable` | BR/TS | Resolvido indiretamente ao corrigir enum casts |
| `argument.type` | TS/RF | Corrigido com casts de ID, guards e normalizacao de input |
| `property.notFound` | TS | Corrigido removendo acesso dinamico inseguro a `pivot`/relacoes |

## Bugs reais encontrados

| Codigo | Area | Evidencia | Correcao |
| --- | --- | --- | --- |
| BR | Candidaturas | `ApplicationService` podia aceder a agregado/situacao habitacional nulos | Guard com `ValidationException` antes de criar rascunho |
| BR | Snapshots | `ApplicationSnapshotService` assumia agregado/situacao habitacional sempre presentes | Guard com erro de dominio antes de gerar snapshots |
| BR | Documentos adicionais | `AdditionalDocumentSubmissionService` podia registar timeline sem candidatura | Guard explicito antes de gravar evento |
| BR | Contratos | Documento contratual podia ser gerado sem minuta ou descarregado sem path | Guards em `LeaseContractDocumentService` |
| BR | Anexos de apoio | Conteudo de ficheiro podia ser `null` antes de `hash()` | Guard apos leitura do storage |

## Falsos positivos confirmados

| Codigo | Area | Observacao |
| --- | --- | --- |
| FP | Enums castados | Varios models ja tinham casts, mas faltava PHPDoc para o PHPStan inferir enums |
| FP | Datas castadas | `SimulationSession` e `CandidateDataReuseProfile` tinham casts datetime, mas eram inferidos como string |
| FP | List entries | `status` e `entry_type` estavam castados, mas faltava PHPDoc |

## Codigo morto removido ou adiado

- Nao foi removido codigo morto estrutural.
- Os `deadCode.unreachable` relacionados com comparacoes enum/string foram resolvidos por tipagem correta.
- Os 14 erros residuais sao essencialmente nullsafe redundante e limpeza cosmetica; foram adiados para PHPSTAN-19 para evitar alteracoes sem ganho funcional relevante.

## Testes executados

### Testes dirigidos

- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Application`
  - OK: 23 testes / 166 assercoes.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Contract`
  - OK: 11 testes / 84 assercoes.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Finance`
  - OK: 4 testes / 36 assercoes.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Maintenance`
  - OK: 5 testes / 50 assercoes.
- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter Administrative`
  - OK: 6 testes / 32 assercoes.

### Suite completa

- `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml`
  - OK: 283 testes / 1775 assercoes.

### Outros gates

- `./vendor/bin/pint --test`
  - OK.
- `php artisan route:list --except-vendor`
  - OK: 1083 rotas.
- `./vendor/bin/phpstan analyse --memory-limit=1G -v --error-format=json`
  - Gerado com exit code 1 esperado por existirem 14 erros residuais.

## Regressao detetada e corrigida

Durante os testes dirigidos, o filtro `Application` falhou inicialmente:

- Teste: `Sprint8ApplicationSubmissionTest::test_valid_submission_creates_number_declarations_document_links_snapshots_and_audit`
- Causa: a checklist documental podia chegar como `Collection`, e uma protecao demasiado restritiva aceitava apenas `array`, resultando em 0 documentos associados.
- Correcao: `ApplicationDocumentService` passou a aceitar `Collection` ou `array`, preservando o comportamento original.
- Revalidacao: filtro `Application` passou e suite completa passou.

## Erros residuais

Distribuicao final:

| Identificador | Quantidade |
| --- | ---: |
| `nullsafe.neverNull` | 12 |
| `instanceof.alwaysTrue` | 1 |
| `nullCoalesce.offset` | 1 |

Ficheiros residuais:

- `app/Services/Applications/ApplicationReceiptService.php`
- `app/Services/CandidateExperience/ApplicationSimulationConsistencyService.php`
- `app/Services/Contracts/ContractPlaceholderService.php`
- `app/Services/DocumentStandardization/DocumentDossierBuilder.php`
- `app/Services/ProcedureMinutes/ProcedureMinuteService.php`
- `app/Services/ProcedureTemplates/GeneratedProcedureDocumentService.php`
- `app/Services/ProcedureTemplates/TemplateRenderingService.php`
- `app/Services/ProcessConfirmations/ProcessConfirmationService.php`
- `app/Services/ProcessConfirmations/ProcessNumberGenerator.php`
- `app/Services/Scoring/RankingSnapshotService.php`
- `app/Services/Scoring/ScoringMessageService.php`

## Riscos residuais

- Os erros restantes sao maioritariamente `nullsafe.neverNull`, sem indicio direto de bug funcional.
- A remocao mecanica de todos os nullsafe foi adiada porque alguns representam defesa contra dados historicos incompletos.
- PHPStan ainda nao esta a zero, mas a divida de maior risco funcional desta sprint foi reduzida de forma significativa.

## Recomendacao para PHPSTAN-19

Avancar para PHPSTAN-19 com foco em:

1. Remover os 14 erros residuais de baixo risco.
2. Revalidar se nullsafe defensivo deve ficar como codigo defensivo documentado ou ser removido.
3. Ativar quality gate de CI para impedir novos erros PHPStan.
4. Manter politica `new=0` ate PHPStan chegar a zero.
