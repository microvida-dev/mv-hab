# Relatório de Qualidade — Sprint 20

## Âmbito validado

- Portal público de oferta habitacional.
- Publicação de habitações.
- Filtros e mapa/fallback.
- Ficha pública de habitação.
- Documentos públicos.
- Backoffice de publicação.

## Testes executados

### Testes específicos

`php artisan test tests/Feature/PublicPortal/PublicHousingOfferSprint20Test.php`

- 6 testes;
- 27 asserções;
- aprovado.

### Compatibilidade funcional

Executados e aprovados:

- `php artisan test tests/Feature/Sprint3PortalProgramsTest.php`: 9 testes / 61 asserções.
- `php artisan test tests/Feature/Sprint8ApplicationSubmissionTest.php`: 10 testes / 85 asserções.
- `php artisan test tests/Feature/Integrated/FullHousingProgramFlowTest.php`: 2 testes / 61 asserções.
- `php artisan test`: 180 testes / 1191 asserções.

### Comandos de validação

- `php artisan route:list`: executado com sucesso; 856 rotas.
- `php artisan migrate`: executado com sucesso; nada pendente para migrar no estado final.
- `npm run build`: executado com sucesso; Vite gerou `public/build/manifest.json`, CSS e JS.
- `./vendor/bin/pint --test`: executado com sucesso.

## PHPStan

O PHPStan já apresentava dívida técnica antes da Sprint 20. Os relatórios existentes em `storage/audit/phpstan-audit-2026-06-18.json` e `storage/audit/phpstan-audit-verbose-2026-06-18.json` indicavam 2453 erros.

Durante a validação, a chamada normal ao PHPStan terminava com código 1 sem corpo no terminal. A chamada em modo debug com `php -d display_errors=1 -d error_reporting=E_ALL vendor/phpstan/phpstan/phpstan.phar analyse --memory-limit=1G --debug` devolveu o resultado final:

- Resultado: falhou.
- Erros finais: 2471.
- Categorias dominantes: `argument.type`, `missingType.generics`, `assign.propertyType`, `identical.alwaysFalse`, `function.alreadyNarrowedType`, `argument.templateType`.
- Observação: foram corrigidos os erros diretamente introduzidos/visíveis da Sprint 20 no `HousingUnitImageController` e no request de critérios de elegibilidade; permanecem dívidas PHPStan transversais pré-existentes em módulos de finanças, RGPD, scoring, candidate e models.

## Riscos

- Cartografia externa ainda não integrada.
- Validação editorial das fichas públicas depende do município.
- Imagens e brochuras devem ser revistas para garantir ausência de dados pessoais.

## Pendências

- Reduzir dívida PHPStan transversal em sprint técnica dedicada.
- Validar editorialmente imagens, brochuras, textos e precisão de localização com o município.
- Integrar cartografia externa/municipal caso seja disponibilizada fonte oficial.
