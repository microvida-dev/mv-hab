# x-mv.stat-card

## Objetivo

Apresentar indicadores resumidos em dashboards e páginas operacionais.

## Quando utilizar

- Métricas
- Contadores
- Estados agregados
- Resumos operacionais

## API

| Propriedade | Tipo | Obrigatória | Descrição |
|---|---|---:|---|
| label | string | Sim | Nome da métrica |
| value | mixed | Sim | Valor apresentado |
| hint | string | Não | Texto auxiliar |
| icon | string | Não | Nome do ícone |
| href | string | Não | Link de detalhe |

## Exemplo

Usar para indicadores curtos com valor principal visível.

## Boas práticas

- Manter valores curtos.
- Usar em grelhas de indicadores.

## Não utilizar

- Para conteúdo textual longo.
