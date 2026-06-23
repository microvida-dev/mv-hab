# Atualização de ranking pós-sorteio

## Objetivo

Registar snapshot antes/depois do ranking após sorteio, sem apagar histórico anterior.

## Implementação

`RankingUpdateService` cria `ranking_update_runs` com:

- `before_snapshot`;
- `after_snapshot`;
- resumo de participantes/resultados;
- estado `applied`;
- auditoria.

## Nota

A sprint não implementa nova matriz de classificação nem recalcula pontuações. O objetivo é rastrear a ordenação pós-sorteio.
