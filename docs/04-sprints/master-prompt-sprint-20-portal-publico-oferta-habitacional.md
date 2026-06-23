# MASTER PROMPT — EXECUÇÃO DA SPRINT 20: PORTAL PÚBLICO DE OFERTA HABITACIONAL

Atua como arquiteto sénior Laravel, tech lead, frontend engineer, accessibility engineer e product engineer especializado em plataformas públicas municipais.

O objetivo desta execução é implementar exclusivamente a:

```text
Sprint 20 — Portal Público de Oferta Habitacional
```

Esta sprint pertence à fase de consolidação da experiência pública da plataforma municipal de Habitação/Arrendamento Acessível.

A Sprint 20 deve completar a experiência pública de consulta dos concursos, empreendimentos e fogos disponíveis, garantindo uma navegação clara, filtrável, responsiva, acessível, segura e preparada para ligação ao website institucional do Município.

---

# 1. Regra principal

Executa apenas a Sprint 20.

Não avances para Sprint 21, Sprint 22 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Não executar:

```bash
git branch --show-current
```

Não interromper a execução por causa da branch atual.

---

# 2. Ficheiro principal da sprint

Usa como referência principal:

```text
docs/backlog/sprint-20-portal-publico-oferta-habitacional.md
```

Se este ficheiro não existir, interrompe e informa que falta o ficheiro de definição da Sprint 20.

Não improvisar uma implementação sem o ficheiro de sprint.

---

# 3. Documentação obrigatória a ler antes de implementar

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

---

# 4. Inspeção inicial obrigatória

Antes de implementar, inspeciona o repositório e identifica:

```text
Versão do Laravel
Versão do PHP
Stack frontend real
Se o projeto usa Blade, Inertia, React, Vue, Livewire ou Alpine
Sistema de autenticação
Sistema de roles/permissões
Sistema de policies
Sistema de storage
Sistema de media/documentos
Sistema de SEO existente, se existir
Sistema de sitemap, se existir
Sistema de auditoria, se existir
Sistema de logs de download, se existir
Sistema de cache, se existir
Sistema de testes
Configuração Vite
Configuração Tailwind
Configuração PHPStan/Larastan
Configuração Pint
Configuração PHPUnit/Pest
Comandos disponíveis em composer.json
Comandos disponíveis em package.json
```

Inspeciona também os modelos, migrations, controllers, services, requests, policies, factories e views existentes relacionados com:

```text
Program
Contest
ContestStatus
ContestDeadline
ContestHousingUnit
HousingUnit
HousingUnitFeature
HousingUnitImage
HousingUnitDocument
HousingUnitPublicDocument
HousingUnitStatus
Municipality
Parish
Application
Document
DocumentSubmission
PublicPage
Faq
AuditLog
ReportExport
```

Não duplicar entidades existentes.

Se já existir algo equivalente a:

```text
PublicHousingOfferController
PublicContestController
PublicHousingUnitController
PublicHousingMapController
HousingUnitImage
HousingUnitPublicDocument
PublicPortalSetting
PublicPortalLink
PublicHousingSearchService
PublicHousingMapService
PublicPortalSeoService
```

reaproveitar ou adaptar com compatibilidade.

Não apagar dados existentes.

Não apagar imagens existentes.

Não apagar documentos existentes.

Não alterar `.env`.

Não introduzir credenciais.

Não usar dados pessoais reais.

Não usar API keys reais.

Não usar tokens reais de mapas, SMS, e-mail, analytics ou serviços externos.

---

# 5. Verificação PHPStan obrigatória antes de criar e publicar

Esta sprint tem uma regra adicional obrigatória:

```text
Executar PHPStan/Larastan antes de criar funcionalidades novas e novamente antes de considerar o portal pronto para publicação.
```

## 5.1 Verificação inicial PHPStan

Antes de criar migrations, models, controllers, requests, resources, services, views, componentes ou rotas públicas, verificar se PHPStan existe.

Executar, se existir:

```bash
test -f vendor/bin/phpstan && php -d memory_limit=1G ./vendor/bin/phpstan analyse || true
```

Se existir `phpstan.neon`, usar:

```bash
test -f vendor/bin/phpstan && php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon || true
```

Se o projeto tiver script no `composer.json`, por exemplo `composer phpstan`, usar o comando do projeto.

Se PHPStan não existir, documentar:

```text
PHPStan/Larastan não está instalado/configurado. Não foi possível executar análise estática antes da implementação.
```

Se PHPStan existir e falhar antes da implementação:

```text
Registar o erro inicial.
Classificar como erro pré-existente quando aplicável.
Não misturar correções alheias à Sprint 20, salvo se bloquearem a execução da sprint.
Não afirmar que a Sprint 20 introduziu erros pré-existentes.
```

## 5.2 Verificação PHPStan antes de publicar

Depois de criar ou alterar ficheiros da Sprint 20, e antes de considerar qualquer funcionalidade “publicável”, executar novamente:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse
```

ou:

```bash
php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon
```

Se existirem erros PHPStan introduzidos pela Sprint 20, corrigir antes da resposta final.

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

# 6. Dependências funcionais

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
HousingUnitStatus
Municipality
Parish/Freguesia
Document/Media/Attachment
PublicPage
Application
```

Regras de adaptação:

```text
Se ContestHousingUnit não existir, usar a relação existente entre concursos e habitações.
Se HousingUnitImage não existir, criar estrutura incremental.
Se HousingUnitPublicDocument não existir, criar estrutura própria para documentos públicos.
Se já existir sistema de media/documentos públicos, reutilizar.
Se já existir portal público parcial, completar sem quebrar rotas existentes.
Se não existir freguesia como entidade própria, usar campo textual existente de localização/freguesia.
```

---

# 7. Validação funcional e legal

O portal público deve respeitar regras administrativas e RGPD.

Regras obrigatórias:

```text
Publicar apenas concursos autorizados/publicados.
Publicar apenas imóveis marcados como públicos.
Publicar apenas imagens aprovadas como públicas.
Publicar apenas documentos públicos aprovados.
Não expor documentos de candidatura.
Não expor dados pessoais.
Não expor logs.
Não expor auditoria.
Não expor ranking interno.
Não expor dados financeiros privados.
Não expor contratos privados.
Não expor custos internos.
Não expor storage_path.
Não expor coordenadas exatas se a configuração pública exigir localização aproximada.
```

A experiência pública deve permitir conhecer a oferta, mas não deve permitir contornar o fluxo obrigatório de registo, simulador e candidatura.

---

# 8. Objetivo da implementação

Completar o Portal Público de Oferta Habitacional.

A plataforma deve permitir ao cidadão:

```text
Consultar a oferta habitacional pública
Consultar concursos abertos
Consultar concursos futuros
Consultar concursos encerrados publicados
Consultar imóveis/fogos associados a concursos
Pesquisar por freguesia
Pesquisar por tipologia
Pesquisar por intervalo de renda
Pesquisar por estado do concurso
Ver resultados em lista
Ver resultados em mapa
Consultar ficha pública do imóvel
Ver galeria de imagens
Descarregar brochura/PDF público
Consultar página pública do concurso
Aceder a informação de apoio/FAQ
Aceder ao website institucional do Município
Avançar para registo/simulador/candidatura quando aplicável
```

A plataforma deve permitir ao Município:

```text
Configurar a visibilidade pública dos imóveis
Gerir dados públicos do imóvel
Gerir imagens públicas
Gerir brochuras/PDF públicos
Gerir links institucionais
Gerir definições do portal público
Pré-visualizar a ficha pública antes de publicar
Publicar e despublicar imóveis
Garantir que documentos privados não são publicados
Consultar regras de publicação
```

---

# 9. Âmbito incluído

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
Paginação
Ficha pública do imóvel
Galeria de imagens pública
Brochura/PDF pública do empreendimento ou imóvel
Backoffice para configurar publicação pública da oferta, se necessário
Campos públicos adicionais nos imóveis, se necessário
Slugs públicos estáveis
SEO básico
Open Graph básico
JSON-LD básico quando adequado
Breadcrumbs
Acessibilidade WCAG básica
Responsividade mobile
Fallback sem JavaScript
Testes funcionais
Testes de filtros
Testes de mapa
Testes de documentos públicos
Testes de segurança pública
Testes de autorização backoffice
Testes PHPStan antes/depois
Documentação
```

---

# 10. Fora de âmbito

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

A ligação ao website institucional deve ser feita por URLs configuráveis, botões, links e documentação de integração.

Não alterar site externo.

Não criar dependência obrigatória de deploy externo.

---

# 11. Fluxo público obrigatório

## 11.1 Consulta geral da oferta

```text
Cidadão acede a /oferta-habitacional
→ Sistema mostra hero institucional
→ Sistema mostra filtros
→ Sistema mostra lista de imóveis publicados
→ Sistema mostra mapa ou fallback
→ Cidadão aplica filtros
→ Sistema preserva query string
→ Cidadão abre ficha de imóvel
→ Sistema mostra ficha pública, galeria, renda, localização e concurso associado
→ Cidadão pode descarregar brochura pública
→ Cidadão pode avançar para página do concurso ou registo/simulador
```

## 11.2 Consulta de concurso

```text
Cidadão acede a /oferta-habitacional/concursos
→ Sistema mostra concursos públicos
→ Cidadão filtra por estado
→ Cidadão abre concurso
→ Sistema mostra datas, prazos, estado, descrição, documentos públicos e imóveis associados
→ Sistema mostra CTA adequado
```

## 11.3 Gestão backoffice de publicação

```text
Técnico autorizado edita perfil público do imóvel
→ Faz upload de imagens públicas
→ Faz upload de brochura pública
→ Revê preview
→ Admin ou perfil autorizado publica imóvel
→ Sistema regista auditoria
→ Imóvel passa a aparecer no portal público se o concurso também estiver publicável
```

---

# 12. Estados públicos recomendados

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

Não substituir enums internos de negócio. Criar camada de apresentação pública quando necessário.

---

# 13. Modelo de dados

## 13.1 Extensão pública de HousingUnit

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
public_location_precision deve suportar exact, street, parish ou approximate.
is_public não basta para publicar; validar também relação com concurso publicado.
published_at define a publicação efetiva.
unpublished_at deve ser preenchido quando despublicado.
```

## 13.2 HousingUnitFeature

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
public_transport_distance
school_nearby
healthcare_nearby
```

Não usar características internas sensíveis.

## 13.3 HousingUnitImage

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
Não publicar fotografias com dados pessoais visíveis sem validação manual.
```

## 13.4 HousingUnitPublicDocument

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

## 13.5 PublicPortalLink

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

## 13.6 PublicPortalSetting

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

# 14. Índices e performance

Adicionar índices seguros se necessário:

```text
housing_units.public_slug unique
housing_units.is_public
housing_units.public_visibility_status
housing_units.parish_id
housing_units.typology
housing_units.monthly_rent
housing_units.public_latitude
housing_units.public_longitude
contests.status
contests.opens_at
contests.closes_at
contest_housing_units.contest_id
contest_housing_units.housing_unit_id
contest_housing_units.status
housing_unit_images.housing_unit_id
housing_unit_images.is_public
housing_unit_images.sort_order
housing_unit_public_documents.housing_unit_id
housing_unit_public_documents.is_public
housing_unit_public_documents.document_type
public_portal_links.key
public_portal_settings.key
```

As migrations devem ser reversíveis.

Não adicionar índices duplicados se já existirem.

Não carregar coleções grandes sem paginação.

---

# 15. Services obrigatórios

Criar ou completar services em namespace adequado:

```text
App\Services\PublicPortal\PublicContestService
App\Services\PublicPortal\PublicHousingSearchService
App\Services\PublicPortal\PublicHousingMapService
App\Services\PublicPortal\PublicHousingUnitService
App\Services\PublicPortal\PublicHousingMediaService
App\Services\PublicPortal\PublicPortalSeoService
App\Services\PublicPortal\PublicPortalLinkService
App\Services\PublicPortal\PublicPortalSettingsService
```

## 15.1 PublicContestService

Responsável por:

```text
Listar concursos publicamente visíveis
Determinar estado público do concurso
Carregar contadores públicos
Carregar fogos associados publicamente visíveis
Garantir que concursos não publicados não aparecem
Fornecer dados para página pública do concurso
Resolver CTA adequado por estado
```

## 15.2 PublicHousingSearchService

Responsável por:

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

## 15.3 PublicHousingMapService

Responsável por:

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

## 15.4 PublicHousingUnitService

Responsável por:

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

## 15.5 PublicHousingMediaService

Responsável por:

```text
Publicar/despublicar imagens públicas
Publicar/despublicar brochuras
Validar ficheiros públicos
Garantir alt text
Garantir storage correto
Garantir ausência de path real na resposta
Gerar checksum quando aplicável
```

## 15.6 PublicPortalSeoService

Responsável por:

```text
Gerar title
Gerar meta description
Gerar canonical URL
Gerar Open Graph
Gerar JSON-LD se aplicável
Gerar breadcrumbs estruturados
```

## 15.7 PublicPortalLinkService

Responsável por:

```text
Resolver links institucionais ativos
Ordenar links
Validar URLs
Gerir links no backoffice
Fornecer links ao layout público
```

## 15.8 PublicPortalSettingsService

Responsável por:

```text
Resolver definições públicas
Resolver centro do mapa
Resolver zoom do mapa
Resolver configuração de morada visível
Resolver contactos de apoio
Resolver CTAs públicos
Gerir cache de settings se existir padrão
```

---

# 16. Controllers públicos obrigatórios

Criar ou completar:

```text
App\Http\Controllers\Public\HousingOfferController
App\Http\Controllers\Public\PublicContestController
App\Http\Controllers\Public\PublicHousingUnitController
App\Http\Controllers\Public\PublicHousingMapController
App\Http\Controllers\Public\PublicHousingDocumentController
```

## HousingOfferController

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
Links institucionais
```

## PublicContestController

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

## PublicHousingUnitController

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

## PublicHousingMapController

Métodos:

```text
index()
```

Responsável por:

```text
Endpoint JSON para mapa
Aplicar filtros
Retornar marcadores públicos
Não retornar campos internos
Não retornar dados pessoais
```

## PublicHousingDocumentController

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

# 17. Controllers de backoffice

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
Publicar imóvel
Despublicar imóvel
Pré-visualizar ficha pública
Gerir galeria
Gerir brochuras/documentos públicos
Gerir links institucionais
Gerir definições do portal
```

Restringir por Policy.

---

# 18. Form Requests obrigatórios

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

## SearchHousingOfferRequest

```php
'q' => ['nullable', 'string', 'max:120'],
'parish' => ['nullable', 'integer'],
'typology' => ['nullable', 'string', 'max:20'],
'rent_min' => ['nullable', 'integer', 'min:0', 'max:10000'],
'rent_max' => ['nullable', 'integer', 'min:0', 'max:10000', 'gte:rent_min'],
'contest_status' => ['nullable', 'string', 'max:50'],
'housing_status' => ['nullable', 'string', 'max:50'],
'accessibility' => ['nullable', 'boolean'],
'available_only' => ['nullable', 'boolean'],
'sort' => ['nullable', 'string', 'in:recent,rent_asc,rent_desc,typology_asc,parish_asc,closing_soon'],
```

Se existir tabela `parishes`, validar:

```php
'parish' => ['nullable', 'integer', 'exists:parishes,id'],
```

## StoreHousingUnitImageRequest

```php
'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
'title' => ['nullable', 'string', 'max:160'],
'alt_text' => ['required', 'string', 'max:180'],
'caption' => ['nullable', 'string', 'max:500'],
'is_public' => ['boolean'],
'is_cover' => ['boolean'],
'sort_order' => ['nullable', 'integer', 'min:0'],
```

## StoreHousingUnitPublicDocumentRequest

```php
'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
'title' => ['required', 'string', 'max:180'],
'description' => ['nullable', 'string', 'max:500'],
'document_type' => ['required', 'string', 'max:80'],
'is_public' => ['boolean'],
'sort_order' => ['nullable', 'integer', 'min:0'],
```

---

# 19. Policies obrigatórias

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

# 20. Rotas públicas obrigatórias

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

Endpoints JSON do mapa devem devolver apenas dados públicos.

---

# 21. Rotas de backoffice

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

Se o projeto já tiver organização própria de rotas de backoffice, respeitar a convenção existente.

---

# 22. Views públicas obrigatórias

Se o projeto usa Blade, criar ou completar:

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

Se o projeto usa Inertia/React/Vue, criar equivalentes respeitando a stack existente.

Não introduzir React/Inertia se o projeto for Blade.

Não introduzir Blade paralelo se o projeto for Inertia.

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
Ligação ao website institucional
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

# 23. Views de backoffice

Se Blade:

```text
resources/views/backoffice/public-portal/housing-units/public-profile/edit.blade.php
resources/views/backoffice/public-portal/housing-units/images/index.blade.php
resources/views/backoffice/public-portal/housing-units/images/create.blade.php
resources/views/backoffice/public-portal/housing-units/documents/index.blade.php
resources/views/backoffice/public-portal/housing-units/documents/create.blade.php
resources/views/backoffice/public-portal/settings/edit.blade.php
resources/views/backoffice/public-portal/links/index.blade.php
resources/views/backoffice/public-portal/links/create.blade.php
resources/views/backoffice/public-portal/links/edit.blade.php
```

Se Inertia/React/Vue, criar equivalentes.

Backoffice deve permitir:

```text
Editar perfil público
Pré-visualizar
Publicar
Despublicar
Gerir imagens
Gerir documentos públicos
Gerir links
Gerir definições
```

---

# 24. Mapa interativo

Implementar mapa de forma segura e sem dependência externa obrigatória.

Opções permitidas:

```text
Leaflet com OpenStreetMap
Mapa simples com coordenadas e fallback de lista
Mapa estático sem chave externa
Componente existente do projeto, se existir
```

Não usar Google Maps com API key hardcoded.

Não colocar chaves no frontend.

Não adicionar serviço externo pago obrigatório.

Requisitos do mapa:

```text
Mostrar fogos publicados
Aplicar filtros da página
Abrir popup/card com resumo do fogo
Permitir clicar para ficha pública do imóvel
Ter fallback acessível sem JavaScript
Não falhar a página se o mapa não carregar
Não expor coordenadas exatas se a configuração pública exigir aproximação
```

Fallback obrigatório:

```text
Se não houver latitude/longitude, mostrar lista filtrada normalmente.
Se show_map=false, ocultar mapa e manter resultados em lista.
Se JavaScript falhar, manter resultados em lista.
```

---

# 25. Pesquisa e filtros

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

# 26. Brochura/PDF do empreendimento

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

# 27. Integração com website institucional

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

# 28. SEO e metadados

Implementar SEO básico:

```text
Title por página
Meta description
Canonical
Open Graph title
Open Graph description
Open Graph image quando existir
Breadcrumbs
JSON-LD quando adequado
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
Downloads, se necessário
Backoffice
Pré-visualizações privadas
Área do candidato
Documentos privados
```

---

# 29. Acessibilidade

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
Inputs com erro legível
Estados com texto e não apenas cor
```

---

# 30. Segurança e RGPD

Regras obrigatórias:

```text
Não expor dados pessoais.
Não expor documentos privados.
Não expor paths de storage.
Não expor IDs sensíveis quando houver slug.
Não permitir download de documentos não publicados.
Não permitir visualização de imóveis não publicados.
Não permitir visualização de concursos não publicados.
Validar todos os filtros.
Paginar resultados.
Evitar SQL injection usando Eloquent/query builder.
Não guardar dados de navegação pessoal sem base legal.
Não adicionar tracking externo sem consentimento.
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

# 31. Auditoria

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

# 32. Cache

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

# 33. Factories e seeders

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

Não usar imagens com pessoas reais.

Não usar documentos reais com dados pessoais.

---

# 34. Testes obrigatórios

Criar ou completar testes.

## 34.1 Testes públicos

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

## 34.2 Testes de backoffice

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

## 34.3 Testes de segurança

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

## 34.4 Testes de performance básicos

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

# 35. Comandos obrigatórios finais

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

# 36. Atualização documental obrigatória

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

## docs/public-portal/overview.md

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

## docs/public-portal/institutional-website-integration.md

Incluir:

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

## docs/public-portal/publication-rules.md

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

## docs/public-portal/map-and-filters.md

Incluir:

```text
Filtros disponíveis
Payload do mapa
Fallback sem mapa
Regras de localização
Performance
Limitações técnicas
```

## docs/qa/sprint-20-quality-report.md

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

# 37. Critérios de aceitação

A Sprint 20 está concluída quando:

```text
Existe portal público completo de oferta habitacional.
Existe listagem pública de concursos.
Existe página pública de concurso.
Existe listagem pública de imóveis/fogos.
Existe ficha pública de imóvel.
Existe pesquisa por freguesia.
Existe pesquisa por tipologia.
Existe pesquisa por intervalo de renda.
Existe pesquisa por estado do concurso.
Filtros funcionam combinados.
Existe mapa interativo ou fallback operacional documentado.
Mapa mostra apenas imóveis publicados.
Ficha pública mostra galeria aprovada.
Ficha pública permite descarregar brochura/PDF público.
Documentos privados nunca são expostos.
Imóveis não publicados não aparecem.
Concursos não publicados não aparecem.
Existe ligação configurável ao website institucional.
Existe CTA para registo/simulador/candidatura quando aplicável.
Backoffice consegue configurar publicação pública.
Backoffice consegue gerir imagens públicas.
Backoffice consegue gerir brochuras públicas.
SEO básico está implementado.
Acessibilidade básica está garantida.
Páginas são responsivas.
Queries usam eager loading/paginação.
PHPStan foi executado antes da implementação, se disponível.
PHPStan foi executado antes da publicação, se disponível.
PHPStan final não apresenta erros introduzidos pela Sprint 20.
php artisan route:list executa sem erro.
php artisan test executa sem erro ou falhas são documentadas.
npm run build executa sem erro ou falhas são documentadas.
./vendor/bin/pint executa sem erro ou alterações são documentadas.
Documentação pública foi criada.
Não foram introduzidas credenciais.
Não foram usados dados pessoais reais.
Não foram implementadas funcionalidades fora de âmbito.
```

---

# 38. Resposta final obrigatória

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

# 39. Definition of Done

A Sprint 20 só está concluída quando a plataforma disponibilizar uma experiência pública completa de consulta da oferta habitacional, com concursos e imóveis publicados, filtros operacionais, mapa dinâmico ou fallback funcional, ficha pública do imóvel, galeria, brochura/PDF, ligação institucional, segurança RGPD, testes, documentação e validação PHPStan antes da publicação.

---

# 40. Execução imediata

Executa agora apenas:

```text
Sprint 20 — Portal Público de Oferta Habitacional
```

Usa como referência principal:

```text
docs/backlog/sprint-20-portal-publico-oferta-habitacional.md
```

Fim da master prompt da Sprint 20.
