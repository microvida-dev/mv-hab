# Programa 53 - Processo operacional

## Âmbito

Este documento acompanha o BPMN 2.0 em `program-53-process.bpmn`. Descreve o
fluxo real implementado nas Sprints 53A a 53I; não constitui regulamento nem
define prazos legais.

## Participantes

- **Candidato:** submete candidatura, responde ao aperfeiçoamento e consulta o
  resultado privado.
- **Técnico municipal:** analisa documentos, confirma prontidão, sela lotes,
  revalida alterações e pede exportações quando autorizado.
- **Sistema MV-HAB:** aplica scopes, Policies, entitlements e MFA; cria
  snapshots, publica atomicamente, projeta pedidos e preserva auditoria.
- **Filas/workers:** entregam comunicações e geram/expiram pacotes temporais.

## Sequência canónica

1. O calendário do concurso resolve as fases `applications`, `review`,
   `corrections` e `revalidation` no timezone configurado.
2. A análise progressiva grava decisões técnicas sem comunicação individual.
3. O cálculo de prontidão bloqueia documentos ainda submetidos/em análise e
   identifica falta, rejeição ou expiração.
4. A pré-visualização HMAC é read-only; a selagem revalida a fonte dentro da
   transação e congela um snapshot por candidatura.
5. A publicação cria todos os resultados e o outbox com um único
   `published_at`. A entrega externa ocorre depois do commit.
6. Resultados `correction_required` projetam um pedido associado ao snapshot
   publicado. A resposta formal cria recibo imutável e preserva versões.
7. A revalidação compara apenas alterações posteriores ao recibo. O segundo
   lote reutiliza a infraestrutura de lote/publicação e não abre um terceiro
   ciclo automático.
8. A exportação resolve uma fonte temporal, escreve NDJSON canónico, gera CSV,
   JSON, XML e XLSX, valida schemas/checksums e publica o ZIP por move atómico.

## Invariantes

- Cada operação é scoped por Município e concurso.
- Preview não altera dados; seal/publish são transacionais e idempotentes.
- Snapshots, recibos, itens e resultados publicados são imutáveis.
- Notificações entregues não são repetidas; falhas retomam apenas pendentes.
- Ficheiros `.partial` e staging nunca são descarregáveis.
- Exportação sensível exige permission independente e não pertence ao template
  normal do analista/exportador.
- Nenhum retry contorna autorização, MFA, entitlement ou estado do recurso.

## Exceções operacionais

Consultar `program-53-failure-recovery-matrix.md`. Fonte stale, autorização
revogada e schema inválido não são recuperados como uma repetição cega. Falhas
transitórias de base de dados/storage usam rollback, backoff e limites fixos.

## Fronteiras externas

Workers, cache partilhado, scheduler, storage privado, alertas e smoke após
deploy devem ser validados no ambiente alvo. O BPMN não declara deploy nem
substitui procedimentos municipais aprovados.
