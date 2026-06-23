# Portal Público de Oferta Habitacional

## Objetivo

A Sprint 20 disponibiliza uma experiência pública para consulta de oferta habitacional municipal sem expor dados pessoais, documentos privados ou estados internos do processo.

## Funcionalidades implementadas

- Página `/oferta-habitacional` com filtros por pesquisa, tipologia, freguesia, estado público, concurso e renda.
- Lista pública de concursos em `/oferta-habitacional/concursos`.
- Detalhe público de concurso com habitações associadas quando publicadas.
- Lista pública de habitações em `/oferta-habitacional/imoveis`.
- Ficha pública de habitação com localização controlada, características, galeria e documentos públicos.
- Endpoint JSON `/oferta-habitacional/mapa` com marcadores públicos sem morada interna nem paths de storage.
- Download de documentos públicos por controller autorizado por regras de publicação.
- Backoffice para configurar ficha pública, publicar/ocultar, gerir imagens, documentos, links e definições do portal.

## Dados excluídos da exposição pública

- Dados pessoais de candidatos ou agregados.
- Documentos submetidos por candidatos.
- Paths internos de ficheiros.
- Notas internas, decisões administrativas, auditoria e informação de workflow.
- Morada completa quando `public_address_visible` está inativo.

## Rotas principais

- `public.housing-offer.index`
- `public.contests.index`
- `public.contests.show`
- `public.housing-units.index`
- `public.housing-units.show`
- `public.housing-map.index`
- `public.housing-documents.download`

## Backoffice

- `backoffice.public-portal.settings.*`
- `backoffice.public-portal.links.*`
- `backoffice.public-portal.housing-units.*`
- `backoffice.public-portal.images.*`
- `backoffice.public-portal.documents.*`

## Limitações assumidas

- Não foi integrada cartografia externa. O mapa usa fallback operacional com marcadores JSON e lista pública.
- Não foram criados empreendimentos como entidade autónoma; a publicação usa `housing_units`.
- Não foram implementadas candidaturas, elegibilidade, ranking, atribuição ou contratos nesta sprint.
