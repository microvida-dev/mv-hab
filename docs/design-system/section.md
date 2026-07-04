# x-mv.section

## Objetivo

Agrupar conteúdo em blocos visuais consistentes.

## Quando utilizar

- Formulários
- Resumos
- Listagens
- Blocos de conteúdo

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| eyebrow | string | Não | Texto introdutório |
| title | string | Não | Título da secção |
| description | string | Não | Descrição |
| padding | string | Não | Classes de espaçamento |

## Exemplo

Usar para separar áreas funcionais de uma página.

## Boas práticas

- Preferir `x-mv.section` a blocos soltos com `mv-surface`.
- Manter cada secção focada numa função.

## Não utilizar

- Como card repetido em grelhas.
