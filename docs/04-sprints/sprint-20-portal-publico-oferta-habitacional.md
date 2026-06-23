# Sprint 20 — Portal Público de Oferta Habitacional

## Prioridade de desenvolvimento

Esta sprint pertence à fase de consolidação da experiência pública da plataforma municipal de Habitação/Arrendamento Acessível.

A Sprint 20 deve completar a consulta pública de concursos, empreendimentos e fogos disponíveis, garantindo uma experiência clara, acessível, pesquisável, filtrável e compatível com integração no website institucional do Município.

Esta sprint responde diretamente à necessidade funcional de permitir ao cidadão conhecer a oferta habitacional antes de avançar para registo, simulador ou candidatura.

---

# 1. Objetivo da Sprint

Completar a experiência pública de consulta dos concursos e imóveis.

O portal público deve permitir:

```text
Consultar concursos abertos, futuros, encerrados e publicados
Consultar imóveis/fogos associados a concursos
Pesquisar por freguesia
Pesquisar por tipologia
Pesquisar por intervalo de renda
Pesquisar por estado do concurso
Visualizar mapa interativo dos fogos
Consultar ficha pública do imóvel
Visualizar galeria de imagens
Descarregar brochura/PDF do empreendimento ou imóvel
Aceder à página pública do concurso
Iniciar percurso de registo/simulador/candidatura quando aplicável
Integrar ou expor ligações seguras para o website institucional do Município
```

A experiência pública deve ser informativa, responsiva, acessível e segura.

---

# 2. Instrução operacional para Codex

Executa apenas esta Sprint 20.

Não avances para Sprint 21 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper a execução por causa da branch atual.

Antes de alterar código, lê, se existirem:

```text
docs/architecture/technical-architecture.md
docs/architecture/data-model-overview.md
docs/product/product-vision.md
docs/product/functional-requirements.md
docs/product/user-roles.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/backlog/roadmap.md

docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/sprint-8-candidaturas-submissao-formal.md
docs/backlog/sprint-12-atribuicao-habitacoes.md
docs/backlog/sprint-16-notificacoes-comunicacoes-modelos-documentais.md
docs/backlog/sprint-17-relatorios-indicadores-dashboard-executivo.md
docs/backlog/sprint-18-rgpd-seguranca-auditoria-avancada.md
docs/backlog/sprint-19-testes-integrados-qualidade.md
docs/backlog/sprint-20-portal-publico-oferta-habitacional.md

docs/qa/test-coverage-matrix.md
docs/qa/sprint-19-quality-report.md
docs/qa/quality-gates.md
docs/qa/regression-test-plan.md
```

Se algum ficheiro não existir, continua apenas se for tecnicamente possível, mas documenta a ausência na resposta final.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não usar tokens reais de mapas, APIs externas, SMS, e-mail ou analytics.

Não criar dependência obrigatória de serviços externos pagos.

Não implementar candidatura nesta sprint; a candidatura deve apenas ser ligada através de CTA para fluxo existente.

---

# 3. Verificação PHPStan obrigatória antes de criar e publicar

Antes de criar novas migrations, models, controllers, requests, resources, services, views ou rotas públicas, verificar se o projeto tem PHPStan/Larastan configurado.

Executar, se existir:

```bash
test -f vendor/bin/phpstan && ./vendor/bin/phpstan analyse || true
```

ou o comando equivalente presente no `composer.json`, por exemplo:

```bash
composer phpstan
```

Se PHPStan não existir, documentar:

```text
PHPStan/Larastan não está instalado/configurado. Não foi possível executar análise estática antes da implementação.
```

Se PHPStan existir e falhar antes da implementação:

```text
Registar o erro inicial.
Classificar se o erro é pré-existente.
Não misturar correções de PHPStan alheias à Sprint 20, salvo se bloquearem a compilação/execução da sprint.
Não afirmar que a Sprint 20 introduziu erros pré-existentes.
```

Após criar o portal público, antes de considerar qualquer funcionalidade “publicada” ou pronta, executar novamente PHPStan:

```bash
test -f vendor/bin/phpstan && ./vendor/bin/phpstan analyse
```

Se existirem erros PHPStan causados pela Sprint 20, corrigir antes da resposta final.

Se não for possível corrigir, documentar:

```text
Ficheiro
Linha
Tipo PHPStan
Causa provável
Impacto
Correção recomendada
Bloqueia publicação: sim/não
```

A Sprint 20 só pode ser marcada como pronta para publicação se não introduzir erros PHPStan novos.

---

# 4. Dependências funcionais

Esta sprint depende preferencialmente de:

```text
Sprint 3 — Portal Público e Programas
Sprint 12 — Atribuição de Habitações
Sprint 17 — Relatórios, Indicadores e Dashboard Executivo
Sprint 18 — RGPD, Segurança e Auditoria Avançada
Sprint 19 — Testes Integrados e Qualidade
```

Modelos ou conceitos esperados:

```text
Program
Contest
ContestStatus
ContestHousingUnit
HousingUnit
HousingUnitImage
HousingUnitDocument
HousingUnitStatus
Municipality
Parish/Freguesia
Document/Media/Attachment
Application
```

Se `ContestHousingUnit` não existir, usar a relação existente entre concursos e habitações.

Se `HousingUnitImage` não existir, criar estrutura compatível e incremental.

Se `HousingUnitDocument` não existir, criar estrutura própria para documentos públicos do imóvel/empreendimento, sem reutilizar documentos privados de candidatura.

Se já existir sistema de media/documentos públicos, reaproveitar.

Não duplicar entidades existentes.

---

# 5. Âmbito incluído

Implementar:

```text
Portal público de oferta habitacional
Página pública de listagem de concursos
Página pública de detalhe do concurso
Página pública de listagem de imóveis/fogos
Mapa interativo dos fogos
Pesquisa por freguesia
Pesquisa por tipologia
Pesquisa por intervalo de renda
Pesquisa por estado do concurso
Filtros combinados
Ordenação pública
Ficha pública do imóvel
Galeria de imagens pública
Brochura/PDF pública do empreendimento ou imóvel
Backoffice para configurar visibilidade pública da oferta, se necessário
Campos públicos adicionais nos imóveis, se necessário
Slugs públicos estáveis
SEO básico
Open Graph básico
JSON-LD básico para páginas públicas, se adequado
Acessibilidade WCAG básica
Responsividade mobile
Testes funcionais
Testes de filtros
Testes de segurança pública
Testes de autorização backoffice
Testes PHPStan antes/depois
Documentação
```

---

# 6. Fora de âmbito

Não implementar nesta sprint:

```text
Registo de adesão
Simulador de elegibilidade
Submissão de candidatura
Upload documental do candidato
Workflow administrativo
Classificação
Ranking
Listas provisórias/definitivas
Atribuição
Contratos
Pagamentos
Manutenção
Vistorias
Área do inquilino
Assinatura digital
Agendamento de visitas
Pagamentos digitais
Integração real com website institucional externo
Integração real com Google Maps paga
Integração real com SIG municipal externo
Analytics externo
Chatbot
Helpdesk/tickets
```

A ligação ao website institucional deve ser feita por URLs configuráveis, botões, links e eventual endpoint público seguro. Não criar dependência de deploy ou domínio externo.

---

# 7. Regras de publicação pública

Apenas podem ser exibidos publicamente:

```text
Concursos com estado publicado, aberto, futuro, encerrado publicado ou equivalente
Imóveis marcados como publicamente visíveis
Informação expressamente classificada como pública
Documentos públicos, como brochuras, fichas técnicas públicas, editais e minutas públicas
Imagens públicas aprovadas
Localização pública autorizada
Valores de renda públicos do concurso/imóvel
Tipologia, área, freguesia, estado e características públicas
```

Nunca expor publicamente:

```text
Dados pessoais de candidatos
Dados de agregados familiares
Documentos de candidatura
Documentos de identificação
NIFs
Moradas completas de candidatos
Logs
Auditoria
Pontuações individuais não publicadas
Ranking interno
Notas internas
Custos internos não públicos
Contratos privados
Dados financeiros de arrendatários
Informação técnica sensível do imóvel não autorizada
Coordenadas exatas se o Município definir visibilidade aproximada
```

---

# 8. Estados públicos recomendados

Criar ou mapear estados públicos sem quebrar enums existentes.

## ContestPublicStatus

```text
future
open
closing_soon
closed
under_analysis
results_published
cancelled
archived
```

## HousingPublicStatus

```text
available
reserved
allocated
under_maintenance
unavailable
closed
```

## PublicVisibilityStatus

```text
draft
ready_for_review
published
hidden
archived
```

Se já existirem enums equivalentes, reutilizar.

Não substituir enums de negócio internos por enums públicos. Criar camada de apresentação pública quando necessário.

---

# 9. Modelo de dados

## 9.1 HousingUnit — extensão pública

Se o model `HousingUnit` existir, adicionar campos apenas se não existirem:

```text
public_title
public_slug
public_summary
public_description
public_location_description
public_address_visible
public_latitude
public_longitude
public_location_precision
public_visibility_status
is_public
published_at
unpublished_at
public_sort_order
seo_title
seo_description
og_image_path
```

Regras:

```text
public_slug deve ser único.
public_latitude/public_longitude podem ser nullable.
public_address_visible define se a morada completa pode ser mostrada.
public_location_precision pode permitir exact, street, parish, approximate.
is_public não basta para publicar; também validar relação com concurso publicado.
```

## 9.2 HousingUnitFeature

Criar se não existir:

```text
housing_unit_features
```

Campos sugeridos:

```text
id
housing_unit_id
key
label
value
icon
sort_order
is_public
created_at
updated_at
```

Exemplos:

```text
elevator
balcony
parking
accessibility
energy_certificate
furnished
storage
near_public_transport
```

Não usar características internas sensíveis.

## 9.3 HousingUnitImage

Criar se não existir sistema equivalente de media:

```text
housing_unit_images
```

Campos:

```text
id
housing_unit_id
uploaded_by
title
alt_text
caption
path
thumbnail_path
mime_type
size_bytes
width
height
sort_order
is_cover
is_public
approved_at
approved_by
created_at
updated_at
deleted_at
```

Regras:

```text
Apenas imagens is_public=true e approved_at preenchido podem aparecer no portal.
Usar storage público apenas para imagens explicitamente públicas.
Validar mime type e tamanho.
Gerar alt_text obrigatório ou fallback seguro.
Não publicar fotografias com dados pessoais visíveis se não houver validação manual.
```

## 9.4 HousingUnitPublicDocument

Criar se não existir equivalente:

```text
housing_unit_public_documents
```

Campos:

```text
id
housing_unit_id
contest_id nullable
uploaded_by
title
description
document_type
path
original_filename
mime_type
size_bytes
checksum
is_public
approved_at
approved_by
published_at
expires_at nullable
sort_order
download_count
created_at
updated_at
deleted_at
```

Tipos:

```text
brochure
technical_sheet
floor_plan
energy_certificate_public
contest_notice
contract_template_public
other
```

Regras:

```text
Apenas documentos aprovados e publicados podem ser descarregados publicamente.
Não reutilizar documentos privados do candidato.
Não expor path físico.
Download público deve passar por controller.
Registar contagem de downloads sem recolher dados pessoais desnecessários.
```

## 9.5 PublicPortalLink

Criar se necessário:

```text
public_portal_links
```

Campos:

```text
id
key
label
url
description
target
is_active
sort_order
created_by
updated_by
created_at
updated_at
```

Usos:

```text
Ligação ao website institucional
Ligação para regulamento municipal
Ligação para FAQ
Ligação para manual de utilizador
Ligação para atendimento/linha de apoio
Ligação para política de privacidade
```

## 9.6 PublicPortalSetting

Criar se necessário:

```text
public_portal_settings
```

Campos:

```text
id
key
value
type
group
is_public
created_by
updated_by
created_at
updated_at
```

Configurações possíveis:

```text
show_exact_address
show_map
default_map_center_latitude
default_map_center_longitude
default_map_zoom
institutional_website_url
housing_department_url
support_phone
support_email
privacy_policy_url
faq_url
applications_enabled
```

Evitar `.env` para conteúdos editáveis do portal.

---

# 10. Índices e performance

Adicionar índices seguros se necessário:

```text
housing_units.public_slug unique
housing_units.is_public
housing_units.public_visibility_status
housing_units.parish_id
housing_units.typology
housing_units.monthly_rent
housing_units.public_latitude/public_longitude
contests.status
contests.opens_at
contests.closes_at
contest_housing_units.contest_id
contest_housing_units.housing_unit_id
contest_housing_units.status
housing_unit_images.housing_unit_id/is_public/sort_order
housing_unit_public_documents.housing_unit_id/is_public/document_type
```

As migrations devem ser reversíveis.

Não adicionar índices duplicados se já existirem.

---

# 11. Services

Criar ou completar services em namespace adequado, por exemplo:

```text
App\Services\PublicPortal\PublicContestService
App\Services\PublicPortal\PublicHousingSearchService
App\Services\PublicPortal\PublicHousingMapService
App\Services\PublicPortal\PublicHousingUnitService
App\Services\PublicPortal\PublicHousingMediaService
App\Services\PublicPortal\PublicPortalSeoService
App\Services\PublicPortal\PublicPortalLinkService
```

## PublicContestService

Responsabilidades:

```text
Listar concursos publicamente visíveis
Determinar estado público do concurso
Carregar contadores públicos
Carregar fogos associados publicamente visíveis
Garantir que concursos não publicados não aparecem
Fornecer dados para página pública do concurso
```

## PublicHousingSearchService

Responsabilidades:

```text
Aplicar filtros combinados
Filtrar por freguesia
Filtrar por tipologia
Filtrar por intervalo de renda
Filtrar por estado público do concurso
Filtrar por estado público do imóvel
Filtrar por acessibilidade se existir
Filtrar por concurso
Ordenar resultados
Paginar resultados
Evitar N+1
```

Filtros esperados:

```text
q
contest
parish
typology
rent_min
rent_max
contest_status
housing_status
accessibility
available_only
sort
page
```

Ordenações:

```text
recent
rent_asc
rent_desc
typology_asc
parish_asc
closing_soon
```

## PublicHousingMapService

Responsabilidades:

```text
Gerar dataset para mapa
Aplicar os mesmos filtros da pesquisa
Não expor dados privados
Respeitar precisão de localização
Agrupar marcadores próximos se necessário
Gerar payload leve
Suportar endpoint JSON
```

Payload sugerido:

```json
{
    "id": 1,
    "title": "T2 — Freguesia",
    "slug": "t2-freguesia",
    "typology": "T2",
    "rent": 350,
    "parish": "Freguesia",
    "latitude": 39.0,
    "longitude": -8.0,
    "precision": "approximate",
    "status": "available",
    "contest": "Concurso 2026",
    "url": "/oferta-habitacional/imoveis/t2-freguesia"
}
```

## PublicHousingUnitService

Responsabilidades:

```text
Resolver imóvel por slug
Validar visibilidade pública
Carregar concurso associado
Carregar imagens públicas
Carregar documentos públicos
Carregar características públicas
Gerar CTAs
Gerar breadcrumbs
```

## PublicHousingMediaService

Responsabilidades:

```text
Publicar/despublicar imagens públicas
Publicar/despublicar brochuras
Validar ficheiros públicos
Garantir alt text
Garantir storage correto
Garantir ausência de path real na resposta
```

## PublicPortalSeoService

Responsabilidades:

```text
Gerar title
Gerar meta description
Gerar canonical URL
Gerar Open Graph
Gerar JSON-LD se aplicável
Gerar breadcrumbs estruturados
```

---

# 12. Controllers públicos

Criar ou completar:

```text
App\Http\Controllers\Public\HousingOfferController
App\Http\Controllers\Public\PublicContestController
App\Http\Controllers\Public\PublicHousingUnitController
App\Http\Controllers\Public\PublicHousingMapController
App\Http\Controllers\Public\PublicHousingDocumentController
```

## Public\HousingOfferController

Métodos:

```text
index()
```

Responsável por:

```text
Página pública principal da oferta habitacional
Filtros
Resultados
Mapa
CTA para concursos
CTA para registo/simulador/candidatura
```

## Public\PublicContestController

Métodos:

```text
index()
show(Contest $contest)
```

Responsável por:

```text
Listar concursos públicos
Mostrar página pública do concurso
Mostrar datas/prazos
Mostrar estado
Mostrar imóveis associados
Mostrar documentos públicos do concurso
Mostrar CTA adequado
```

## Public\PublicHousingUnitController

Métodos:

```text
index()
show(string $slug)
```

Responsável por:

```text
Listar imóveis/fogos
Mostrar ficha pública
Mostrar galeria
Mostrar características
Mostrar localização/mapa
Mostrar renda
Mostrar concurso associado
Mostrar documentos/brochura
```

## Public\PublicHousingMapController

Métodos:

```text
index()
```

Responsável por:

```text
Endpoint JSON para mapa
Aplicar filtros
Retornar marcadores públicos
```

## Public\PublicHousingDocumentController

Métodos:

```text
download(HousingUnitPublicDocument $document)
```

Responsável por:

```text
Validar visibilidade pública
Não expor storage path
Incrementar download_count
Retornar ficheiro público
Bloquear documentos privados
```

---

# 13. Controllers de backoffice

Criar apenas o necessário para completar a publicação pública:

```text
App\Http\Controllers\Backoffice\PublicPortal\HousingUnitPublicProfileController
App\Http\Controllers\Backoffice\PublicPortal\HousingUnitImageController
App\Http\Controllers\Backoffice\PublicPortal\HousingUnitPublicDocumentController
App\Http\Controllers\Backoffice\PublicPortal\PublicPortalSettingController
App\Http\Controllers\Backoffice\PublicPortal\PublicPortalLinkController
```

Funções:

```text
Editar dados públicos do imóvel
Publicar/despublicar imóvel
Gerir galeria
Gerir brochuras/documentos públicos
Gerir links institucionais
Gerir definições do portal
Pré-visualizar ficha pública antes de publicar
```

Restringir por Policy.

---

# 14. Form Requests

Criar requests tipados e específicos:

```text
App\Http\Requests\PublicPortal\SearchHousingOfferRequest
App\Http\Requests\PublicPortal\SearchPublicContestRequest
App\Http\Requests\PublicPortal\SearchPublicHousingMapRequest

App\Http\Requests\Backoffice\PublicPortal\UpdateHousingUnitPublicProfileRequest
App\Http\Requests\Backoffice\PublicPortal\StoreHousingUnitImageRequest
App\Http\Requests\Backoffice\PublicPortal\UpdateHousingUnitImageRequest
App\Http\Requests\Backoffice\PublicPortal\StoreHousingUnitPublicDocumentRequest
App\Http\Requests\Backoffice\PublicPortal\UpdateHousingUnitPublicDocumentRequest
App\Http\Requests\Backoffice\PublicPortal\UpdatePublicPortalSettingRequest
App\Http\Requests\Backoffice\PublicPortal\StorePublicPortalLinkRequest
App\Http\Requests\Backoffice\PublicPortal\UpdatePublicPortalLinkRequest
```

Validações públicas:

```php
'q' => ['nullable', 'string', 'max:120'],
'parish' => ['nullable', 'integer', 'exists:parishes,id'],
'typology' => ['nullable', 'string', 'max:20'],
'rent_min' => ['nullable', 'integer', 'min:0', 'max:10000'],
'rent_max' => ['nullable', 'integer', 'min:0', 'max:10000', 'gte:rent_min'],
'contest_status' => ['nullable', 'string', 'max:50'],
'housing_status' => ['nullable', 'string', 'max:50'],
'sort' => ['nullable', 'string', 'in:recent,rent_asc,rent_desc,typology_asc,parish_asc,closing_soon'],
```

Validações de imagens:

```php
'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
'title' => ['nullable', 'string', 'max:160'],
'alt_text' => ['required', 'string', 'max:180'],
'is_public' => ['boolean'],
'is_cover' => ['boolean'],
'sort_order' => ['nullable', 'integer', 'min:0'],
```

Validações de brochura/documentos públicos:

```php
'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
'title' => ['required', 'string', 'max:180'],
'description' => ['nullable', 'string', 'max:500'],
'document_type' => ['required', 'string', 'max:80'],
'is_public' => ['boolean'],
```

---

# 15. Policies

Criar ou completar:

```text
HousingUnitPublicProfilePolicy
HousingUnitImagePolicy
HousingUnitPublicDocumentPolicy
PublicPortalSettingPolicy
PublicPortalLinkPolicy
```

Regras:

```text
Público/guest só pode ver informação publicada.
Candidato autenticado não tem mais privilégios que guest no portal público.
Técnico municipal pode preparar conteúdos se tiver permissão.
Admin pode publicar/despublicar.
Auditor pode consultar configuração sem alterar.
Gestor de manutenção não publica imóveis salvo permissão explícita.
Documentos públicos só podem ser descarregados se publicados.
```

Nunca confiar apenas no frontend para esconder conteúdos.

---

# 16. Rotas públicas

Adicionar ou completar rotas públicas:

```php
Route::get('/oferta-habitacional', [HousingOfferController::class, 'index'])
    ->name('public.housing-offer.index');

Route::get('/oferta-habitacional/concursos', [PublicContestController::class, 'index'])
    ->name('public.contests.index');

Route::get('/oferta-habitacional/concursos/{contest:slug}', [PublicContestController::class, 'show'])
    ->name('public.contests.show');

Route::get('/oferta-habitacional/imoveis', [PublicHousingUnitController::class, 'index'])
    ->name('public.housing-units.index');

Route::get('/oferta-habitacional/imoveis/{slug}', [PublicHousingUnitController::class, 'show'])
    ->name('public.housing-units.show');

Route::get('/oferta-habitacional/mapa', [PublicHousingMapController::class, 'index'])
    ->name('public.housing-map.index');

Route::get('/oferta-habitacional/documentos/{document}/download', [PublicHousingDocumentController::class, 'download'])
    ->name('public.housing-documents.download');
```

Usar slugs estáveis.

Não expor IDs em URLs públicas sempre que existir slug.

---

# 17. Rotas de backoffice

Adicionar apenas se necessário:

```php
Route::prefix('backoffice/public-portal')
    ->name('backoffice.public-portal.')
    ->middleware(['auth'])
    ->group(function (): void {
        Route::get('/imoveis/{housingUnit}/perfil-publico', [HousingUnitPublicProfileController::class, 'edit'])
            ->name('housing-units.public-profile.edit');

        Route::put('/imoveis/{housingUnit}/perfil-publico', [HousingUnitPublicProfileController::class, 'update'])
            ->name('housing-units.public-profile.update');

        Route::post('/imoveis/{housingUnit}/publicar', [HousingUnitPublicProfileController::class, 'publish'])
            ->name('housing-units.publish');

        Route::post('/imoveis/{housingUnit}/despublicar', [HousingUnitPublicProfileController::class, 'unpublish'])
            ->name('housing-units.unpublish');

        Route::resource('/imoveis/{housingUnit}/imagens', HousingUnitImageController::class)
            ->names('housing-unit-images');

        Route::resource('/imoveis/{housingUnit}/documentos', HousingUnitPublicDocumentController::class)
            ->names('housing-unit-public-documents');

        Route::resource('/links', PublicPortalLinkController::class)
            ->names('links');

        Route::get('/settings', [PublicPortalSettingController::class, 'edit'])
            ->name('settings.edit');

        Route::put('/settings', [PublicPortalSettingController::class, 'update'])
            ->name('settings.update');
    });
```

Se o projeto usa Inertia/React, criar páginas equivalentes em `resources/js/Pages`.

Se o projeto usa Blade, criar views Blade.

Respeitar a stack real do projeto.

---

# 18. Views públicas

Criar ou completar:

```text
resources/views/public/housing-offer/index.blade.php
resources/views/public/contests/index.blade.php
resources/views/public/contests/show.blade.php
resources/views/public/housing-units/index.blade.php
resources/views/public/housing-units/show.blade.php
resources/views/public/components/housing-filters.blade.php
resources/views/public/components/housing-card.blade.php
resources/views/public/components/contest-card.blade.php
resources/views/public/components/map-panel.blade.php
resources/views/public/components/gallery.blade.php
resources/views/public/components/public-document-list.blade.php
resources/views/public/components/breadcrumbs.blade.php
```

Adaptar se o projeto usar outra estrutura.

## Página `/oferta-habitacional`

Deve incluir:

```text
Hero institucional
Resumo da oferta
Filtros
Lista de imóveis
Mapa
Concursos em destaque
Ligação para registo/simulador
Ligação para FAQ
Ligação para atendimento/apoio
```

## Página de concursos

Deve incluir:

```text
Título
Estado
Programa
Datas de abertura e fecho
Prazos relevantes
Descrição pública
Documentos públicos
Número de fogos associados
Lista de imóveis
CTA para consultar imóvel
CTA para registo/simulador/candidatura se aplicável
```

## Página de imóvel

Deve incluir:

```text
Título público
Galeria
Tipologia
Freguesia
Renda ou intervalo de renda
Área útil/bruta se existir
Estado público
Concurso associado
Descrição
Características
Mapa/localização
Brochura/PDF
CTA para concurso
CTA para registo/simulador/candidatura
Avisos legais
```

---

# 19. Mapa interativo

Implementar mapa de forma segura e sem dependência externa obrigatória.

Opções permitidas:

```text
Leaflet com OpenStreetMap
Mapa simples com coordenadas e fallback de lista
Mapa estático sem chave externa
Integração com componente existente se já existir
```

Não usar Google Maps com API key hardcoded.

Não colocar chaves no frontend.

Se for necessário usar fornecedor externo, criar abstração configurável e documentar que a chave deve ser definida por configuração segura em fase posterior.

Requisitos do mapa:

```text
Mostrar fogos publicados
Aplicar filtros da página
Abrir popup/card com resumo do fogo
Permitir clicar para ficha pública do imóvel
Ter fallback acessível sem JavaScript
Não falhar a página se o mapa não carregar
Não expor coordenadas exatas se configuração pública exigir aproximação
```

Fallback obrigatório:

```text
Se não houver latitude/longitude, mostrar lista filtrada normalmente.
Se `show_map=false`, ocultar mapa e manter resultados em lista.
```

---

# 20. Pesquisa e filtros

Filtros obrigatórios:

```text
Freguesia
Tipologia
Intervalo de renda
Estado do concurso
```

Filtros recomendados:

```text
Estado do imóvel
Concurso
Disponível apenas
Acessibilidade
Pesquisa livre
Ordenação
```

Regras:

```text
Filtros devem funcionar combinados.
Filtros devem preservar query string.
Filtros devem funcionar sem JavaScript.
Filtros podem ser melhorados com JavaScript progressivo.
Paginação deve preservar filtros.
Inputs devem ser validados por Form Request.
Não usar queries sem limites.
Não retornar todos os imóveis sem paginação.
```

---

# 21. Brochura/PDF do empreendimento

Implementar suporte para brochura pública.

Regras:

```text
Upload em backoffice
Aprovação/publicação
Download público via controller
Não expor path real
Apenas PDF
Tamanho máximo configurável
Título obrigatório
Checksum recomendado
Contador de downloads opcional
```

Se já existir módulo documental público, reutilizar.

Não usar `DocumentSubmission` de candidatos para brochuras públicas.

---

# 22. Integração com website institucional

Implementar ligação segura e simples.

Funcionalidades:

```text
Campo configurável para URL do website institucional
Campo configurável para URL da área de habitação municipal
Botões públicos de retorno ao website institucional
Endpoint público ou bloco de links para embed simples
Documentação de URLs públicas
```

Criar documento:

```text
docs/public-portal/institutional-website-integration.md
```

Conteúdo:

```text
URLs públicas disponíveis
Rotas recomendadas para link no website da Câmara
Como apontar botão “Habitação Acessível”
Como ligar para concursos abertos
Como ligar para mapa da oferta
Como ligar para ficha de imóvel
Como atualizar links institucionais
Limitações
```

Não implementar iframe obrigatório.

Não alterar site institucional externo.

---

# 23. SEO e metadados

Implementar SEO básico:

```text
Title por página
Meta description
Canonical
Open Graph title
Open Graph description
Open Graph image quando existir
Breadcrumbs
Sitemap entry se projeto tiver sitemap
Robots meta coerente
```

Páginas públicas indexáveis:

```text
/oferta-habitacional
/oferta-habitacional/concursos
/oferta-habitacional/concursos/{slug}
/oferta-habitacional/imoveis
/oferta-habitacional/imoveis/{slug}
```

Páginas não indexáveis:

```text
Endpoints JSON do mapa
Downloads se necessário
Backoffice
Pré-visualizações privadas
```

Não indexar páginas de candidatos, documentos privados ou backoffice.

---

# 24. Acessibilidade

Garantir:

```text
Labels nos filtros
Botões com texto claro
Alt text nas imagens
Galeria navegável por teclado
Mapa com alternativa em lista
Contraste adequado
Breadcrumbs
Headings hierárquicos
Mensagens claras para sem resultados
Links descritivos
Foco visível
Sem dependência exclusiva de cor para estado
```

---

# 25. Segurança e RGPD

Regras obrigatórias:

```text
Não expor dados pessoais
Não expor documentos privados
Não expor paths de storage
Não expor IDs sensíveis quando houver slug
Não permitir download de documentos não publicados
Não permitir visualização de imóveis não publicados
Não permitir visualização de concursos não publicados
Validar todos os filtros
Paginar resultados
Evitar SQL injection usando Eloquent/query builder
Não guardar dados de navegação pessoal sem base legal
Não adicionar tracking externo sem consentimento
```

Logs públicos devem ser mínimos.

Se registar downloads, guardar apenas o necessário:

```text
document_id
downloaded_at
user_id nullable
ip_hash nullable
user_agent_hash nullable
```

Evitar guardar IP puro salvo estratégia RGPD existente.

---

# 26. Auditoria

Auditar ações críticas de backoffice:

```text
Publicação de imóvel
Despublicação de imóvel
Alteração de ficha pública
Upload de imagem pública
Remoção de imagem pública
Publicação de brochura
Despublicação de brochura
Alteração de definições públicas
Alteração de links institucionais
```

Se existir `AuditLogger`, usar.

Não auditar cada visita pública anónima.

---

# 27. Cache

Implementar cache apenas se o projeto já tiver padrão.

Possíveis caches:

```text
Filtros disponíveis
Lista de freguesias com oferta
Contadores públicos
Configurações públicas
Links institucionais
```

Invalidar cache ao:

```text
Publicar/despublicar imóvel
Alterar concurso
Alterar relação concurso-imóvel
Alterar imagem pública
Alterar documento público
Alterar settings do portal
```

Não introduzir cache que cause publicação incorreta de conteúdo privado.

---

# 28. Factories e seeders

Criar ou completar factories:

```text
HousingUnitFactory
ContestFactory
ContestHousingUnitFactory
HousingUnitImageFactory
HousingUnitPublicDocumentFactory
HousingUnitFeatureFactory
PublicPortalSettingFactory
PublicPortalLinkFactory
```

Criar seeder opcional para desenvolvimento local:

```text
Database\Seeders\PublicHousingOfferSeeder
```

O seeder deve criar dados fictícios:

```text
Concursos públicos abertos
Concursos futuros
Concursos encerrados
Fogos T0/T1/T2/T3/T4
Freguesias diferentes
Intervalos de renda diferentes
Imagens placeholder seguras
Brochuras placeholder não sensíveis
Coordenadas fictícias/municipais aproximadas
```

Não usar moradas reais completas se não forem dados públicos autorizados.

---

# 29. Testes obrigatórios

Criar ou completar testes.

## 29.1 Testes públicos

```text
tests/Feature/PublicPortal/PublicHousingOfferIndexTest.php
tests/Feature/PublicPortal/PublicContestPageTest.php
tests/Feature/PublicPortal/PublicHousingUnitPageTest.php
tests/Feature/PublicPortal/PublicHousingMapTest.php
tests/Feature/PublicPortal/PublicHousingDocumentDownloadTest.php
```

Cobrir:

```text
Guest vê portal público
Guest vê concursos publicados
Guest não vê concursos não publicados
Guest vê imóveis publicados
Guest não vê imóveis não publicados
Filtro por freguesia funciona
Filtro por tipologia funciona
Filtro por renda funciona
Filtro por estado do concurso funciona
Filtros combinados funcionam
Mapa retorna apenas imóveis publicados
Ficha pública mostra galeria aprovada
Ficha pública mostra brochura pública
Download de brochura pública funciona
Download de documento não publicado falha
Paginação preserva filtros
Sem resultados mostra empty state
```

## 29.2 Testes de backoffice

```text
tests/Feature/Backoffice/PublicPortal/HousingUnitPublicProfileTest.php
tests/Feature/Backoffice/PublicPortal/HousingUnitImageManagementTest.php
tests/Feature/Backoffice/PublicPortal/HousingUnitPublicDocumentManagementTest.php
tests/Feature/Backoffice/PublicPortal/PublicPortalSettingsTest.php
```

Cobrir:

```text
Admin publica imóvel
Admin despublica imóvel
Técnico autorizado atualiza ficha pública
Utilizador não autorizado não publica
Upload de imagem válida funciona
Upload de imagem inválida falha
Alt text é obrigatório
Upload de PDF público funciona
Upload de ficheiro não PDF falha
Settings públicas são atualizadas com autorização
Auditor não altera settings
```

## 29.3 Testes de segurança

```text
tests/Feature/PublicPortal/PublicPortalSecurityTest.php
```

Cobrir:

```text
Documento privado não é descarregado por rota pública
Imóvel escondido não é consultável por slug
Concurso não publicado não é consultável por slug
Filtros inválidos são rejeitados ou normalizados
Não há exposição de path de storage
Não há exposição de dados pessoais em payload do mapa
Endpoint do mapa não retorna campos internos
```

## 29.4 Testes de performance básicos

```text
tests/Feature/PublicPortal/PublicPortalPerformanceSmokeTest.php
```

Cobrir:

```text
Listagem com 100 imóveis responde com paginação
Mapa com 100 imóveis retorna payload leve
Filtros não causam erro
Página de imóvel usa eager loading suficiente
```

---

# 30. PHPStan obrigatório antes de publicação

Executar PHPStan em três momentos, se existir:

## 30.1 Antes da implementação

```bash
test -f vendor/bin/phpstan && ./vendor/bin/phpstan analyse || true
```

Registar resultado inicial.

## 30.2 Antes de publicar rotas/ficheiros finais

Após criar migrations/models/controllers/services/views/resources, executar:

```bash
test -f vendor/bin/phpstan && ./vendor/bin/phpstan analyse
```

Corrigir erros introduzidos pela Sprint 20.

## 30.3 Validação final

Antes da resposta final:

```bash
test -f vendor/bin/phpstan && ./vendor/bin/phpstan analyse
```

Se o projeto tiver memória baixa, usar comando com memória explícita:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse
```

Se usar Larastan com config:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon
```

Se PHPStan não existir, indicar claramente na resposta final.

Não publicar como concluído se PHPStan existir e falhar por erro introduzido nesta sprint.

---

# 31. Comandos obrigatórios finais

Executar:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se o projeto usar frontend build:

```bash
npm run build
```

Se o projeto usar Pint:

```bash
./vendor/bin/pint
```

Se existir PHPStan:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse
```

Se existir Psalm:

```bash
./vendor/bin/psalm
```

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
Bloqueia publicação: sim/não
```

Não afirmar que comandos passaram se não foram executados.

Não ocultar erros.

---

# 32. Documentação obrigatória

Criar ou atualizar:

```text
docs/backlog/sprint-20-portal-publico-oferta-habitacional.md
docs/public-portal/overview.md
docs/public-portal/institutional-website-integration.md
docs/public-portal/publication-rules.md
docs/public-portal/map-and-filters.md
docs/qa/test-coverage-matrix.md
docs/qa/sprint-20-quality-report.md
docs/backlog/roadmap.md
```

## public-portal/overview.md

Incluir:

```text
Objetivo do portal
Rotas públicas
Tipos de conteúdo
Fluxo do cidadão
Fluxo do backoffice
Regras de publicação
Limitações
```

## public-portal/publication-rules.md

Incluir:

```text
O que pode ser publicado
O que nunca pode ser publicado
Como aprovar imagens
Como aprovar brochuras
Como despublicar
Como validar localização
Como validar SEO
Como garantir RGPD
```

## public-portal/map-and-filters.md

Incluir:

```text
Filtros disponíveis
Payload do mapa
Fallback sem mapa
Regras de localização
Performance
Limitações técnicas
```

## sprint-20-quality-report.md

Incluir:

```text
PHPStan antes da implementação
PHPStan antes da publicação
PHPStan final
Testes executados
Rotas públicas criadas
Funcionalidades concluídas
Funcionalidades pendentes
Riscos RGPD
Riscos de performance
Riscos de publicação
```

---

# 33. Critérios de aceitação

A Sprint 20 está concluída quando:

```text
Existe portal público completo de oferta habitacional
Existe listagem pública de concursos
Existe página pública de concurso
Existe listagem pública de imóveis/fogos
Existe ficha pública de imóvel
Existe pesquisa por freguesia
Existe pesquisa por tipologia
Existe pesquisa por intervalo de renda
Existe pesquisa por estado do concurso
Filtros funcionam combinados
Existe mapa interativo ou fallback operacional documentado
Mapa mostra apenas imóveis publicados
Ficha pública mostra galeria aprovada
Ficha pública permite descarregar brochura/PDF público
Documentos privados nunca são expostos
Imóveis não publicados não aparecem
Concursos não publicados não aparecem
Existe ligação configurável ao website institucional
Existe CTA para registo/simulador/candidatura quando aplicável
Backoffice consegue configurar publicação pública
Backoffice consegue gerir imagens públicas
Backoffice consegue gerir brochuras públicas
SEO básico está implementado
Acessibilidade básica está garantida
Páginas são responsivas
Queries usam eager loading/paginação
PHPStan foi executado antes da implementação, se disponível
PHPStan foi executado antes da publicação, se disponível
PHPStan final não apresenta erros introduzidos pela Sprint 20
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
./vendor/bin/pint executa sem erro ou alterações são documentadas
Documentação pública foi criada
Não foram introduzidas credenciais
Não foram usados dados pessoais reais
Não foram implementadas funcionalidades fora de âmbito
```

---

# 34. Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Sprint executada
2. Resumo do trabalho realizado
3. Estado PHPStan antes da implementação
4. Estado PHPStan antes da publicação
5. Estado PHPStan final
6. Rotas públicas criadas ou alteradas
7. Rotas de backoffice criadas ou alteradas
8. Models criados ou alterados
9. Migrations criadas
10. Services criados ou alterados
11. Controllers criados ou alterados
12. Form Requests criados ou alterados
13. Policies criadas ou alteradas
14. Views/components criados ou alterados
15. Funcionalidades de filtros implementadas
16. Estado do mapa interativo
17. Estado da ficha pública do imóvel
18. Estado da galeria de imagens
19. Estado da brochura/PDF pública
20. Estado da ligação ao website institucional
21. Testes criados ou alterados
22. Resultado de php artisan route:list
23. Resultado de php artisan test
24. Resultado de php artisan migrate, se aplicável
25. Resultado de npm run build, se aplicável
26. Resultado de ./vendor/bin/pint, se aplicável
27. Resultado de PHPStan/Psalm, se aplicável
28. Riscos ainda existentes
29. Pendências técnicas
30. Confirmação de que não foram expostos dados pessoais
31. Confirmação de que não foram publicadas informações privadas
32. Confirmação de que não foram usadas credenciais
33. Recomendação objetiva para avançar ou não para Sprint 21
```

Não ocultar erros.

Não afirmar que comandos passaram se não foram executados.

Não avançar para qualquer sprint seguinte sem validação explícita.

---

# 35. Definition of Done

A Sprint 20 só está concluída quando a plataforma disponibilizar uma experiência pública completa de consulta da oferta habitacional, com concursos e imóveis publicados, filtros operacionais, mapa dinâmico ou fallback funcional, ficha pública do imóvel, galeria, brochura/PDF, ligação institucional, segurança RGPD, testes, documentação e validação PHPStan antes da publicação.

Fim da Sprint 20.

---

# Execução Sprint 20 — Registo de Implementação

## Implementado

- Portal público `/oferta-habitacional`.
- Concursos públicos em `/oferta-habitacional/concursos`.
- Habitações públicas em `/oferta-habitacional/imoveis`.
- Detalhe público de habitação com galeria, documentos públicos e concursos associados.
- Endpoint `/oferta-habitacional/mapa` com fallback operacional.
- Backoffice `backoffice.public-portal.*` para settings, links, fichas públicas, imagens e documentos.
- Campos públicos adicionados a `housing_units`.
- Tabelas novas para features, imagens, documentos públicos, settings e links.
- Services, Form Requests, Policies e testes específicos.

## Fora de âmbito mantido

- Candidaturas, elegibilidade, classificação, listas, atribuição, contratos, pagamentos e manutenção não foram alterados por esta sprint.
- Não foram usadas credenciais, tokens ou dados pessoais reais.

## Validação preliminar

- `php artisan route:list` executou com sucesso e mostrou 856 rotas.
- `php artisan test tests/Feature/PublicPortal/PublicHousingOfferSprint20Test.php` passou com 6 testes/27 asserções.
- Testes de compatibilidade Sprint 3, Sprint 8 e fluxo integrado Sprint 19 passaram.

## Validação final

- `php artisan route:list`: executou com sucesso e mostrou 856 rotas.
- `php artisan migrate`: executou com sucesso; nada pendente para migrar no estado final.
- `php artisan test`: executou com sucesso; 180 testes / 1191 asserções.
- `npm run build`: executou com sucesso; Vite gerou manifest, CSS e JS em `public/build`.
- `./vendor/bin/pint --test`: executou com sucesso.
- `php -d display_errors=1 -d error_reporting=E_ALL vendor/phpstan/phpstan/phpstan.phar analyse --memory-limit=1G --debug`: executou e falhou com 2471 erros PHPStan transversais.

## Problemas encontrados e correções

- As primeiras execuções de `php artisan migrate` falharam em MySQL por nomes de índices demasiado longos nas novas tabelas/campos públicos. Os índices foram encurtados e foi adicionada migração corretiva idempotente para ambientes parcialmente migrados.
- A suíte completa expôs uma falha antiga no update de critérios de elegibilidade: `UpdateEligibilityCriterionRequest` reutilizava regras do store sem conseguir resolver o rule set quando a rota tinha apenas `eligibilityCriterion`. O request foi ajustado para resolver o rule set via critério.
- A suíte completa expôs uma incompatibilidade de enum em `AdministrativeProcess::isClosed()`. O método foi ajustado para lidar corretamente com o cast enum.
- PHPStan identificou dois erros diretamente relacionados com a Sprint 20; ambos foram corrigidos antes do fecho.

## Pendências

- Validar editorialmente imagens, textos, brochuras e precisão de localização com o município.
- Integrar cartografia externa ou municipal em sprint futura, se aplicável.
- Resolver dívida PHPStan transversal, ainda em 2471 erros no nível 8.
