# Sprint 32 Quality Report — Preparacao da Apresentacao Alcanena

## Sprint executada

Sprint 32 — Preparação da Apresentação ao Município de Alcanena.

## Objetivo

Preparar a plataforma para demonstração municipal controlada, sem criar novas funcionalidades profundas e sem prometer módulos que ainda pertencem ao roadmap.

## Trabalho realizado

- Programa e concurso demo de Alcanena ajustados para estado publicado no `DemoAlcanenaAffordableRentSeeder`.
- Seeder demo enriquecido com quatro fogos fictícios públicos:
  - T1 Alcanena Centro;
  - T2 Alcanena;
  - T3 Minde;
  - T2 Monsanto.
- Campos públicos dos fogos preenchidos com referência, título, slug, resumo, descrição, freguesia, localidade, áreas, renda, estado, visibilidade, localização aproximada e SEO básico.
- Página pública de fogos passou a expor filtros por tipologia, freguesia, estado, renda mínima, renda máxima e ordenação.
- Ficha pública do imóvel passou a apresentar área bruta e CTAs para brochura, simulador e área reservada.
- Criada brochura HTML imprimível por fogo.
- Testes focados adicionados para filtros públicos, brochura e privacidade.
- Teste do seeder demo atualizado para quatro fogos e publicação controlada.
- Contas demo controladas adicionadas ao seeder com domínio `@exemplo.pt` e password temporária comum para ambiente local/demo.
- Documentação de apresentação, roadmap e matriz de cobertura atualizadas.

## Ficheiros aplicacionais alterados

- `routes/web.php`
- `app/Http/Controllers/PublicPortal/PublicHousingUnitController.php`
- `resources/views/public/housing-units/index.blade.php`
- `resources/views/public/housing-units/show.blade.php`
- `resources/views/public/housing-units/brochure.blade.php`
- `database/seeders/DemoAlcanenaAffordableRentSeeder.php`
- `tests/Feature/DemoAlcanenaAffordableRentSeederTest.php`
- `tests/Feature/PublicPortal/PublicHousingPresentationSprint32Test.php`

## Ficheiros documentais alterados ou criados

- `docs/presentation/alcanena-demo-data.md`
- `docs/presentation/alcanena-readiness-report.md`
- `docs/qa/alcanena-demo-seeder-checklist.md`
- `docs/qa/test-coverage-matrix.md`
- `docs/backlog/roadmap.md`
- `docs/qa/sprint-32-quality-report.md`

## Contas demo controladas

| Perfil | Email | Password |
| --- | --- | --- |
| Administrador | `admin-demo@exemplo.pt` | `password` |
| Técnico municipal | `tecnico-demo@exemplo.pt` | `password` |
| Júri | `juri-demo@exemplo.pt` | `password` |
| Candidato | `candidato-demo@exemplo.pt` | `password` |

## Estado da demo Alcanena

Parcialmente operacional para apresentação controlada:

- Oferta pública e dados de fogos: preparados.
- Programa/concurso demonstráveis: preparados.
- Regras, checklist documental, elegibilidade e classificação: já existentes no seeder.
- Candidaturas/listas fictícias completas: não criadas nesta sprint para evitar aprofundar o ciclo processual além da preparação de demonstração.

## Estado do portal público

- Página pública de concursos: existente.
- Página pública de fogos: afinada.
- Filtros por freguesia, tipologia, renda e estado: visíveis e testados.
- Ficha pública: afinada.
- Brochura simples: criada como HTML imprimível.

## Visitas, tickets e inconsistências

As rotas e módulos já existem no projeto. Nesta sprint não foram criados novos acessos profundos para evitar expansão funcional. Para apresentação, recomenda-se demonstrar apenas se o roteiro for ensaiado e as permissões/menus estiverem estáveis no ambiente de demo.

## Pagamentos e assinatura digital

- Pagamentos digitais com gateway real: roadmap.
- Assinatura digital qualificada: roadmap.
- Não foram apresentados como funcionalidades prontas.

## RGPD e segurança

- Nenhum dado pessoal real foi introduzido.
- Nenhuma credencial real foi introduzida.
- As moradas completas dos fogos demo ficam ocultas publicamente por `public_address_visible = false`.
- A brochura reforça que a localização pública pode ser aproximada.
- Documentos privados de candidatura não foram alterados.

## Comandos executados

| Comando | Resultado |
| --- | --- |
| `php artisan route:list` | OK — 1086 rotas listadas |
| `php artisan db:seed --class=Database\\Seeders\\DemoAlcanenaAffordableRentSeeder` | OK — seeder executado |
| `./vendor/bin/pint` | OK — formatação executada |
| `./vendor/bin/pint --test` | OK |
| `npm run build` | OK — Vite gerou manifest, CSS e JS |
| `php artisan test` | Falhou por limite de memória PHP de 128 MB durante `Tests\\Feature\\Sprint15MaintenanceInspectionTest::test_inspection_checklist_report_and_tenant_visibility_flow`, após 163 testes e 975 asserções passarem |
| `php -d memory_limit=-1 ./vendor/bin/phpunit` | OK — 280 testes, 1750 asserções |
| `php -d memory_limit=1G ./vendor/bin/phpstan analyse --configuration=phpstan.neon --error-format=json > storage/phpstan/sprint32-before-publish.json` | Falhou com 2897 erros PHPStan existentes |

## PHPStan

- Ficheiro gerado: `storage/phpstan/sprint32-before-publish.json`.
- Erros totais reportados: 2897.
- Tentativa executada uma única vez com `phpstan.neon`.
- Erros nos ficheiros PHP tocados pela Sprint 32:
  - `app/Http/Controllers/PublicPortal/PublicHousingUnitController.php`: 0.
  - `app/Http/Requests/PublicPortal/SearchHousingOfferRequest.php`: 0.
  - `database/seeders/DemoAlcanenaAffordableRentSeeder.php`: 0.
- Classificação: erros remanescentes tratados como legado de análise estática, não bloqueantes para a demonstração local da Sprint 32.

## Migrations

Não foram criadas migrations nesta sprint. `php artisan migrate` não foi executado por não haver alterações de schema.

## Riscos de apresentação

- Candidaturas/listas fictícias completas não foram pré-carregadas por esta sprint.
- A demo deve ser ensaiada antes de ser apresentada ao Município.
- Mapa e imagens/galeria devem ser confirmados no ambiente de apresentação.
- Pagamentos, assinatura digital e integrações externas devem permanecer no discurso de roadmap.

## Recomendação

Recomenda-se avançar para demonstração interna/local após validação técnica final. Para apresentação externa ao Município, recomenda-se ensaio completo do roteiro e decisão explícita sobre preparar ou não um pacote adicional de candidaturas/listas fictícias.
