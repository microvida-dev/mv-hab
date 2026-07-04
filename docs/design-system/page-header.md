# x-mv.page-header

## Objetivo

Normalizar cabeçalhos de página da plataforma MV HAB.

## Quando utilizar

- Páginas principais
- Workspaces
- Backoffice
- Portal do candidato

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| eyebrow | string | Não | Texto introdutório |
| title | string | Sim | Título principal |
| description | string | Não | Texto complementar |
| actions | slot | Não | Ações da página |

## Exemplo

Usar com `title`, `description` e slot `actions`.

## Boas práticas

- Usar apenas um por página.
- Colocar botões no slot `actions`.

## Não utilizar

- Dentro de cards, modais ou secções internas.
