# x-mv.check-card

## Objetivo

Apresentar requisitos, validações ou verificações com estado positivo/negativo.

## Quando utilizar

- Checklists
- Pré-validações
- Requisitos de candidatura
- Verificações documentais

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| label | string | Sim | Nome da verificação |
| detail | string | Não | Detalhe complementar |
| passed | bool | Não | Resultado da verificação |

## Exemplo

Usar para mostrar se um requisito foi cumprido.

## Boas práticas

- Usar texto claro e direto.
- Reservar para verificações binárias.

## Não utilizar

- Para estados administrativos complexos.
