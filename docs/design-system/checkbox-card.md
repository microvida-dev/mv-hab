# x-mv.checkbox-card

## Objetivo

Normalizar checkboxes apresentados como cartões selecionáveis.

## Quando utilizar

- Consentimentos
- Confirmações
- Declarações obrigatórias
- Opções destacadas

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| name | string | Sim | Nome do campo |
| label | string | Sim | Texto visível |
| checked | bool | Não | Estado inicial |
| align | string | Não | center ou start |
| tone | string | Não | default ou danger |

## Exemplo

Usar para confirmações relevantes em fluxos de candidatura.

## Boas práticas

- Usar `tone="danger"` apenas em confirmações sensíveis.
- Manter labels compreensíveis.

## Não utilizar

- Para listas longas de opções.
