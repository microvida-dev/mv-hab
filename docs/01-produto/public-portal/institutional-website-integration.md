# Integração com Website Institucional

## Objetivo

Permitir que o website municipal aponte para a oferta pública MV HAB sem duplicar dados nem expor interfaces internas.

## Pontos de integração

- Página canónica: `/oferta-habitacional`.
- Concursos: `/oferta-habitacional/concursos`.
- Habitações: `/oferta-habitacional/imoveis`.
- Mapa JSON: `/oferta-habitacional/mapa`.
- Links institucionais configuráveis em `public_portal_links`.

## Recomendações para municípios

- Usar links diretos para concursos ou habitações publicados.
- Não copiar documentos privados ou documentos de candidatura para o website institucional.
- Usar o campo de links públicos para apontar para editais, regulamentos ou páginas municipais oficiais.
- Validar textos, imagens e brochuras antes de publicação.

## SEO e partilha

O layout público suporta meta description, OpenGraph, canonical URL e JSON-LD nas páginas de detalhe.

## Pendências futuras

- Sitemap público automático.
- Feed ou API pública versionada para websites municipais.
- Integração com CMS institucional, se o município disponibilizar credenciais e requisitos.
