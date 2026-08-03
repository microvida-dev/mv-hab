# Programa 53 - Política técnica de retenção

## Natureza

Este documento regista o comportamento técnico observado. Não aprova nem altera
um prazo legal de conservação. Qualquer mudança de retenção exige validação
municipal/RGPD, configuração, testes e plano de migração.

## Artefactos temporais

| Artefacto | Estado/configuração observada | Expiração/limpeza |
|---|---|---|
| ZIP final de exportação | storage privado, SHA-256 e manifesto | 7 dias por configuração atual |
| NDJSON/checkpoints | staging privado por export | removido após conclusão/expiração ou quando inválido |
| CSV/JSON/XML/XLSX temporários | package staging | removidos após ZIP/move ou falha |
| `.partial` | nunca descarregável | removido na recuperação/cleanup |
| metadata de exportação | base de dados e auditoria | preservada após remoção do ficheiro conforme fluxo |
| logs operacionais | backend de logs do ambiente | política externa aprovada |

## Ordem de expiração

```text
lock do export
-> revalidar estado e download recente
-> marcar expired e bloquear novos downloads
-> eliminar pacote e staging privados
-> limpar metadata de ficheiro
-> auditar a transição
```

Se o delete falhar depois de `expired`, o scheduler volta a selecionar o
registo apenas para cleanup. A corrida entre download e expiração usa lock e
estado final consistente.

## Scheduler

`reports:expire-temporal-exports` corre de hora a hora com
`withoutOverlapping()` e `onOneServer()`. Despacha `ExpireApplicationResultExport`
em chunks de 100. O job é único por export e tem retry limitado.

## Backups

- Backups pertencem à política do ambiente alvo e podem prolongar a existência
  física até ao ciclo de rotação aprovado.
- O restore deve preservar estado, hashes, permissions e scope municipal.
- Nunca restaurar apenas ficheiros sem metadata/hashes correspondentes.
- Testar eliminação/restore em staging antes de alterar a política.

## Documentos e snapshots administrativos

Lotes, publicações, resultados e recibos são artefactos administrativos
imutáveis, não staging de exportação. Este documento não autoriza a sua remoção.
Documentos privados seguem a política documental/RGPD própria e permanecem fora
do pacote quando não existe garantia antivírus persistida.

## Verificação

- health deteta export expirado com ficheiro, staging órfão e `.partial`;
- testes cobrem expiração normal, retry, cleanup parcial, scheduler duplicado e
  corrida de download;
- o operador confirma filesystem/storage e auditoria após incidentes.
