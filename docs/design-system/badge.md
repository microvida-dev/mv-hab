# x-mv.badge

## Objetivo

Apresentar estados ou classificações curtas.

## Quando utilizar

- Estados de documentos
- Estados de candidaturas
- Prioridades
- Indicadores simples

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| tone | string | Não | neutral, success, warning ou danger |

## Exemplo

Usar para textos curtos como Validado, Pendente ou Rejeitado.

## Boas práticas

- Usar apenas para informação curta.
- Preferir `x-mv.alert` para mensagens longas.

## Não utilizar

- Para blocos informativos com várias linhas.
