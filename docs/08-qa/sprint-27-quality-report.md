# Sprint 27 — Relatório de Qualidade

## Comandos executados

| Comando | Resultado |
| --- | --- |
| `php artisan --version` | `Laravel Framework 13.12.0` |
| `php -v` | `PHP 8.4.21` |
| `php artisan migrate` | OK: migration `2026_06_21_000027_create_document_ai_tables` aplicada |
| `php artisan route:list` | OK: 1067 rotas |
| `php artisan test --filter=DocumentAi` | OK: 9 testes, 53 asserções |
| `php artisan test` | Falhou por limite de memória PHP CLI `128M` após 193 testes / 1263 asserções reportados como passados |
| `php -d memory_limit=512M artisan test` | Falhou pelo mesmo limite efetivo de subprocesso `128M` após 196 testes / 1282 asserções reportados como passados |
| `php -d memory_limit=512M ./vendor/bin/phpunit` | OK: 224 testes, 1447 asserções |
| `./vendor/bin/pint` | OK |
| `php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint27-before-publish.json` | Falhou com código 1 e ficheiro JSON vazio |

## Resultado das migrations

Migration criada:

- `database/migrations/2026_06_21_000027_create_document_ai_tables.php`

Resultado de `php artisan migrate`: concluído com sucesso.

## Resultado dos testes

Cobertura específica Document AI passou com:

- Unit: enum, pipeline, transições, fields, flags, logs, eventos e job.
- Feature: integração com upload e substituição documental.
- Suite completa via PHPUnit direto: 224 testes e 1447 asserções.

Nota: o wrapper `php artisan test` falhou por limite de memória efetivo de `128M` em subprocesso. A falha não aponta para uma regressão da Sprint 27; a execução direta do PHPUnit com `memory_limit=512M` passou.

## Resultado PHPStan antes de publicar

Executado uma única vez com `phpstan.neon` e `memory_limit=1G`.

Resultado: código 1, ficheiro `storage/phpstan/sprint27-before-publish.json` criado com 0 bytes. Não foi possível extrair número de erros, warnings ou ficheiros afetados a partir do output gerado.

## Erros legados considerados

O projeto já possuía histórico de dívida/anomalia PHPStan antes da Sprint 27. Como o output atual ficou vazio, não foi possível distinguir automaticamente erros legados de erros em ficheiros novos ou alterados nesta execução.

## Erros novos introduzidos

Não verificável via PHPStan nesta execução por output vazio. Os testes específicos e a suite PHPUnit direta passaram.

## Cobertura de queue fake

`tests/Feature/DocumentIntelligence/DocumentAiUploadIntegrationTest.php` valida que `ProcessDocumentAiJob` é despachado com `Queue::fake()`.

## Cobertura de eventos

`tests/Unit/DocumentIntelligence/DocumentAiPipelineTest.php` valida:

- `DocumentAnalysisStarted`;
- `DocumentAnalysisCompleted`;
- `DocumentAnalysisFailed`;
- ausência de propriedades `raw_text` e `raw_ai_json` nos eventos.

## Cobertura de auditoria

Os testes validam audit logs para:

- criação de análise pendente;
- conclusão da análise;
- flags técnicas.

## Riscos RGPD

- `raw_ai_json` deve continuar sem exposição por UI pública.
- `raw_text` deve exigir regras de acesso próprias antes de ser preenchido por OCR real.
- Logs técnicos devem manter minimização em sprints futuras.

## Riscos funcionais

- Queue worker inativo pode deixar análises em `pending`.
- Ferramentas locais ausentes encaminham análises para `manual_review`.

## Riscos técnicos

- Instalação de Tesseract/Poppler/ImageMagick/Ollama varia por ambiente.
- PHPStan global pode continuar com erros legados fora dos ficheiros da Sprint 27.

## Recomendação de publicação

Recomendado avançar para validação local/staging controlada, com atenção a:

- configurar memória adequada para o runner de testes;
- resolver/analisar a anomalia de PHPStan com output vazio;
- confirmar instalação das ferramentas locais de OCR/PDF/imagem no ambiente alvo.
