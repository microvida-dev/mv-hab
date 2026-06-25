# QA-34 — Portal Público Avançado

## 1. Sumário executivo

A QA-34 reforçou o portal público da MV HAB sobre a base já existente de oferta habitacional, concursos e fichas públicas.

Foram implementados ou reforçados:

- filtros avançados de oferta habitacional;
- payload mínimo e seguro para mapa público;
- arredondamento de coordenadas conforme precisão pública configurada;
- metadados SEO, OpenGraph, Twitter Card e JSON-LD;
- breadcrumbs visíveis e estruturados;
- sitemap público e robots;
- alias públicos para empreendimentos sobre o catálogo de imóveis existente;
- validações de acessibilidade e RGPD por testes automatizados.

Não foram alteradas regras administrativas, elegibilidade, scoring, ranking, listas, contratos, rendas, pagamentos ou IA documental.

Decisão final: **PASS**

## 2. Dependências QA-30/QA-31/QA-32/QA-33

Base confirmada por histórico Git:

- QA-30: utilizadores, roles, equipas, MFA e auditoria de acesso.
- QA-31: Work Tasks, SLA e workflow operacional.
- QA-32: hardening de segurança e RGPD.
- QA-33: IA documental avançada sem decisão automática.

A branch contém `0378234 feat: implement QA-33 advanced document AI`.

## 3. Estado inicial do portal público

Inventário inicial:

| Área | Estado encontrado |
| --- | --- |
| `/` | Página pública base existente. |
| `/programas` e `/programas/{slug}` | Existentes com scope público. |
| `/concursos` e `/concursos/{slug}` | Existentes como alias legado. |
| `/oferta-habitacional` | Existente com listagem, filtros base e mapa textual. |
| `/oferta-habitacional/imoveis` | Existente com listagem paginada. |
| `/oferta-habitacional/imoveis/{slug}` | Existente com ficha pública, galeria e documentos públicos. |
| `/oferta-habitacional/mapa` | Existente como endpoint JSON. |
| `/oferta-habitacional/documentos/{document}/download` | Existente com validação de documento público. |
| `/sitemap.xml` | Não existia. |
| `/robots.txt` | Não existia. |
| `/empreendimentos` | Não existia como domínio separado. |

Não existe modelo separado de empreendimento nesta base. A QA-34 criou alias de navegação para `/empreendimentos` e `/empreendimentos/{slug}` apontando ao catálogo/ficha pública de imóveis já existente, documentando esta equivalência.

Documento opcional ausente:

- `docs/08-qa/deep-research-report.md`

## 4. Páginas públicas validadas

Páginas cobertas por testes ou inventário:

- `/`
- `/programas`
- `/programas/{slug}`
- `/concursos`
- `/concursos/{slug}`
- `/oferta-habitacional`
- `/oferta-habitacional/imoveis`
- `/oferta-habitacional/imoveis/{slug}`
- `/oferta-habitacional/imoveis/{slug}/brochura`
- `/oferta-habitacional/mapa`
- `/empreendimentos`
- `/empreendimentos/{slug}`
- `/sitemap.xml`
- `/robots.txt`

As URLs públicas existentes foram preservadas.

## 5. Pesquisa/filtros

Foram reforçados:

- `locality`;
- `zone`;
- `program`;
- `energy_rating`;
- `visit_available`;
- tratamento booleano explícito de `accessible`;
- opções de filtros derivadas apenas de imóveis publicáveis.

Filtros existentes preservados:

- pesquisa livre;
- tipologia;
- freguesia;
- estado público;
- renda mínima;
- renda máxima;
- concurso;
- estado do concurso;
- ordenação.

Listagens continuam paginadas e com query string preservada.

## 6. Mapa

Foi criado `PublicMapPayloadService` e o `PublicHousingMapService` passou a delegar nele.

Reforços:

- endpoint mostra apenas imóveis publicáveis;
- payload deixa de expor ID interno;
- payload não inclui localização privada, documentos ou paths;
- coordenadas são arredondadas conforme precisão pública:
  - exata: 6 casas;
  - rua: 4 casas;
  - freguesia/aproximada: 3 casas;
- popup contém apenas título, tipologia/localização pública e estado.

## 7. Fichas de fogos/empreendimentos

Ficha pública reforçada:

- breadcrumbs visíveis;
- OpenGraph/Twitter Card;
- JSON-LD `RealEstateListing`;
- `BreadcrumbList`;
- imagem pública para metadados quando existe;
- coordenadas apresentadas com precisão adequada;
- documentos privados continuam fora da view.

Como não existe entidade técnica própria de empreendimentos, as rotas de empreendimentos funcionam como alias público da ficha de imóvel.

## 8. Galeria/brochuras

Validações:

- imagem de capa pública é usada no cartão, ficha e metadados;
- alt text obrigatório nos testes;
- galeria usa apenas imagens públicas;
- brochura pública mantém a localização privada omitida quando configurado;
- documentos públicos são descarregáveis apenas quando `is_public`, publicados e não expirados.

## 9. SEO/OpenGraph/Schema.org

Reforços:

- `canonical` na oferta habitacional;
- `og:type` configurável;
- `og:image` quando existe imagem pública;
- Twitter Card;
- JSON-LD em grafo para ficha de imóvel;
- `RealEstateListing`;
- `Apartment`;
- `Offer`, quando existe renda pública;
- `BreadcrumbList`;
- metadados de concurso como `GovernmentService`.

## 10. Sitemap

Criado:

- `app/Services/PublicPortal/PublicSitemapService.php`
- `app/Http/Controllers/PublicPortal/PublicSitemapController.php`
- `resources/views/public/sitemap.blade.php`
- rota `public.sitemap`
- rota `public.robots`

O sitemap inclui:

- páginas públicas estáticas;
- programas publicados;
- concursos publicados;
- imóveis publicados.

O sitemap exclui:

- rotas autenticadas;
- área reservada;
- backoffice;
- downloads de documentos;
- imóveis não publicados.

## 11. Acessibilidade

Validações implementadas:

- filtros com labels;
- breadcrumbs com `aria-label`;
- navegação pública com `aria-label`;
- imagens públicas com alt text;
- fallback textual para mapa/lista;
- estados sem resultados controlados.

## 12. Performance

Guardrails preservados:

- listagens paginadas;
- eager loading em pesquisa pública;
- scopes públicos reutilizados;
- endpoint de mapa limitado a 200 unidades;
- filtros apoiados por colunas já indexadas em migrations anteriores para estado público, freguesia e coordenadas;
- sem `all()` em tabelas operacionais.

Não foram criadas migrations novas.

## 13. RGPD e segurança pública

Validações:

- recursos não publicados devolvem 404 ou ficam ausentes das listagens;
- documentos privados não são descarregáveis;
- paths internos não aparecem nas views;
- mapa não expõe dados pessoais nem identificadores internos;
- sitemap não inclui áreas reservadas nem downloads;
- robots bloqueia áreas reservadas.

## 14. Testes executados

Testes novos/reforçados:

- `tests/Feature/QA34AdvancedPublicPortalTest.php`
- `tests/Feature/PublicPortal/PublicHousingAdvancedSearchTest.php`
- `tests/Feature/PublicPortal/PublicHousingMapTest.php`
- `tests/Feature/PublicPortal/PublicHousingDetailSeoTest.php`
- `tests/Feature/PublicPortal/PublicSitemapTest.php`
- `tests/Feature/PublicPortal/PublicPortalRgpdProtectionTest.php`
- `tests/Feature/PublicPortal/PublicPortalAccessibilitySmokeTest.php`
- `tests/Unit/PublicPortal/PublicSeoMetadataServiceTest.php`
- `tests/Unit/PublicPortal/PublicMapPayloadServiceTest.php`

Resultados:

- QA34: PASS, 1 teste, 20 assertions.
- PublicPortal: PASS, 18 testes, 144 assertions.
- Public: PASS, 37 testes, 288 assertions.
- Seo: PASS, 3 testes, 40 assertions.
- Rgpd: PASS, 13 testes, 95 assertions.

## 15. Evidências

Artefactos locais:

- `storage/qa/qa-34-composer.txt`
- `storage/qa/qa-34-optimize-clear.txt`
- `storage/qa/qa-34-pint.txt`
- `storage/qa/qa-34-qa34-tests.txt`
- `storage/qa/qa-34-public-portal-tests.txt`
- `storage/qa/qa-34-public-tests.txt`
- `storage/qa/qa-34-seo-tests.txt`
- `storage/qa/qa-34-rgpd-tests.txt`
- `storage/qa/qa-34-route-list.txt`
- `storage/qa/qa-34-phpstan.txt`
- `storage/qa/qa-34-build.txt`
- `storage/qa/qa-34-diff-check.txt`

Resultados técnicos:

- Composer: PASS.
- Optimize clear: PASS.
- Pint: PASS.
- Route list: PASS, 1118 rotas.
- PHPStan: PASS, 0 erros.
- Build: PASS.
- Diff check: PASS.

## 16. Riscos residuais

| Risco | Mitigação |
| --- | --- |
| Não existe domínio próprio de empreendimentos. | Alias público documentado para catálogo/ficha de imóveis; criar domínio separado apenas se o produto o exigir. |
| Mapa ainda é JSON/lista, sem biblioteca visual interativa nesta sprint. | Payload seguro e fallback textual estão prontos; camada visual pode evoluir sem alterar contrato público. |
| Pesquisa por distância aproximada não existe por falta de geocoding/raio no schema atual. | Filtros por freguesia, localidade e zona foram reforçados; distância fica como evolução futura. |
| Imagens dependem de assets públicos configurados pelo backoffice. | Fallback visual e alt text foram validados. |

## 17. Decisão final

**PASS**

A QA-34 cumpre os critérios definidos para portal público avançado em ambiente de demonstração/staging: pesquisa reforçada, mapa seguro, fichas públicas completas, SEO técnico, sitemap, acessibilidade mínima, RGPD preservado e quality gate sem erros.
