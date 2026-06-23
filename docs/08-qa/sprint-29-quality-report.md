# Sprint 29 — Relatório de Qualidade

## Âmbito validado

- Extração estruturada para oito tipos documentais.
- Normalização de datas, valores monetários, percentagens, inteiros e identificadores.
- Persistência em `document_ai_analyses`, `document_ai_fields`, `document_ai_flags` e `document_ai_processing_logs`.
- Eventos de extração.
- Auditoria de execução e consulta.
- Painel backoffice protegido.
- Mascaramento de campos sensíveis e ocultação de dados de saúde.

## Testes adicionados

- `tests/Unit/DocumentIntelligence/DocumentFieldExtractionServicesTest.php`
- `tests/Feature/DocumentIntelligence/DocumentFieldExtractionPipelineTest.php`
- `tests/Feature/Backoffice/DocumentAiExtractionPanelTest.php`

## Fixtures

Fixtures sintéticas e anonimizadas foram adicionadas em:

- `tests/Fixtures/document-intelligence/extraction/`

## Resultado parcial já validado

Teste focado executado:

```bash
php artisan test tests/Unit/DocumentIntelligence/DocumentFieldExtractionServicesTest.php tests/Feature/DocumentIntelligence/DocumentFieldExtractionPipelineTest.php tests/Feature/Backoffice/DocumentAiExtractionPanelTest.php tests/Unit/DocumentIntelligence/DocumentAiPipelineTest.php tests/Feature/DocumentIntelligence/DocumentOcrClassificationIntegrationTest.php
```

Resultado:

```text
25 testes / 142 asserções: OK
```

## Resultado final da execução

Comandos executados e resultado:

- `php artisan migrate`: OK, migration Sprint 29 aplicada.
- `php artisan route:list`: OK, 1073 rotas.
- teste focado Sprint 29: OK, 25 testes / 142 asserções.
- `php artisan test`: falhou por limite de memória PHP 128 MB após 162 testes / 966 asserções já passadas.
- `php -d memory_limit=512M artisan test`: manteve o mesmo limite efetivo de 128 MB nesta instalação e falhou por memória.
- `php -d memory_limit=512M vendor/bin/phpunit`: OK, 256 testes / 1606 asserções.
- `./vendor/bin/pint`: executado, formatou cinco ficheiros.
- `./vendor/bin/pint --test`: OK.
- `npm run build`: OK.
- `composer validate`: OK.
- `vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json`: exit code 1 com ficheiro JSON vazio em `storage/phpstan/sprint29-phpstan.json`.
- `php artisan view:cache`: OK.
- `php artisan view:clear`: OK.

Nota PHPStan:

A tentativa única exigida foi executada com `phpstan.neon`. O comando terminou com código 1 e output JSON vazio, comportamento compatível com a anomalia local já documentada nas sprints anteriores. Não foram obtidos erros novos analisáveis para classificar nesta execução.

## Pendências antes de produção

- Validar precisão com dataset municipal anonimizado.
- Rever retenção de `extraction_json` e campos extraídos com DPO.
- Confirmar instalação de Tesseract, Poppler, ImageMagick e Ollama no ambiente alvo.
- Validar que o uso assistivo da IA é comunicado aos técnicos e não substitui decisão administrativa.
