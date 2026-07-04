# x-mv.alert

## Objetivo

Apresentar mensagens contextuais da interface.

## Quando utilizar

- Avisos
- Erros
- Informação de contexto
- Mensagens de validação visual

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| tone | string | Não | info, success, warning ou danger |

## Exemplo

Usar para mensagens que precisam de destaque visual.

## Boas práticas

- Usar `danger` para erro ou rejeição.
- Usar `warning` para atenção ou pendência.
- Usar `success` para confirmação positiva.

## Não utilizar

- Para estados curtos, usar `x-mv.badge`.
