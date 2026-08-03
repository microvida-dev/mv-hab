# Programa 53 - Matriz de estados

## Regras de leitura

As permissions abaixo são cumulativas com Policy, Município, entitlement,
conta/role ativa e MFA. “Idempotência” identifica a proteção técnica, não uma
autorização para repetir atos administrativos.

## Candidatura

| Origem | Transição permitida no Programa 53 | Ator | Permission | Entitlement | Evento/efeito | Idempotência | Terminal |
|---|---|---|---|---|---|---|---|
| `draft` | `submitted` | candidato | acesso candidate | n/a | submissão formal | locks/validação | não |
| `submitted` | `under_review` | técnico | `applications.update` | `applications.review` | início de análise | lock version | não |
| estado preservado | sem mutação pela selagem/publicação | sistema | `administrative_processes.publish` | `applications.review` | resultado privado separado | hash do snapshot | n/a |
| `submitted` | `withdrawn` quando o fluxo existente permite | candidato/técnico | Policy existente | contexto | desistência | transação | sim |

Os códigos legacy `requires_correction`, `correction_submitted`, `eligible`,
`ineligible`, `excluded`, `cancelled` e `expired` continuam no enum, mas o
Programa 53 não inventa transições novas para os produzir.

## Fase do concurso

| Origem | Transição | Ator | Permission | Entitlement | Evento | Idempotência | Terminal |
|---|---|---|---|---|---|---|---|
| `upcoming` | `applications` | relógio/configuração | gestão de concurso | módulo | início configurado | prazo único | não |
| `applications` | `initial_review` | relógio/configuração | gestão de concurso | módulo | fim/início de fases | validação de sequência | não |
| `initial_review` | `corrections` | relógio/configuração | gestão de concurso | módulo | abertura de aperfeiçoamento | deadline único | não |
| `corrections` | `revalidation` | relógio/configuração | gestão de concurso | módulo | fim do prazo | scheduler idempotente | não |
| `revalidation` | `completed` | relógio/configuração | gestão de concurso | módulo | fim do calendário | leitura temporal | sim |
| qualquer | `between_phases` | sistema | leitura | módulo | intervalo configurado | cálculo puro | não |
| qualquer | `cancelled` | fluxo de concurso existente | permission existente | módulo | cancelamento | auditoria | sim |

## Revisão documental

| Origem | Transição | Ator | Permission | Entitlement | Evento | Idempotência | Terminal |
|---|---|---|---|---|---|---|---|
| `draft` | `in_progress` | analista | `administrative_processes.update` | `applications.review` | início de revisão | lock version | não |
| `in_progress` | `ready_for_closure` | analista | `administrative_processes.update` | `applications.review` | prontidão confirmada | preview HMAC | não |
| `ready_for_closure` | `in_progress` | analista | `administrative_processes.update` | `applications.review` | reabertura fundamentada | lock version | não |
| `ready_for_closure` | `completed` | sistema ao selar | `administrative_processes.update` | `applications.review` | lote selado | seal key/fingerprint | sim |
| `draft`/`in_progress` | `cancelled` | fluxo autorizado | permission existente | módulo | cancelamento | transação | sim |

## Lote e item

| Origem | Transição | Ator | Permission | Entitlement | Evento | Idempotência | Terminal |
|---|---|---|---|---|---|---|---|
| inexistente | `sealed` (`initial_review`/`revalidation`) | analista | `administrative_processes.update` | `applications.review` | snapshot imutável | `seal_key`, fingerprint, hash, unique | sim |
| `sealed` | `superseded` apenas pelo ciclo já implementado | sistema | fluxo interno | `applications.review` | preservação histórica | constraints | sim |

Outcomes permitidos: `complete_pending_decision`, `correction_required`,
`correction_rejected`, `withdrawn` e `not_assessed`.

## Publicação e notificação

| Origem | Transição | Ator | Permission | Entitlement | Evento | Idempotência | Terminal |
|---|---|---|---|---|---|---|---|
| inexistente | `published` | publicador | `administrative_processes.publish` | `applications.review` | resultados/outbox no mesmo commit | uma publicação por lote | sim |
| `draft` | `queued`/`published` | sistema | operação publicada | `applications.review` | notificação persistida | chave de comunicação | não |
| `queued`/`published` | `sent`/`delivered` | worker | contexto herdado | módulo | entrega externa | delivery key/tentativas | não |
| entregue | `read`/`acknowledged`/`archived` | destinatário/sistema | ownership | n/a | consulta/arquivo | timestamp único | sim quando arquivada |
| pendente | `failed`/`cancelled`/`expired` | worker/sistema | contexto herdado | módulo | falha terminal | tentativa registada | sim |

## Aperfeiçoamento, resposta e revalidação

| Origem | Transição | Ator | Permission | Entitlement | Evento | Idempotência | Terminal |
|---|---|---|---|---|---|---|---|
| inexistente | `notified` | sistema | publicação autorizada | `applications.review` | projeção do finding publicado | resultado origem único | não |
| `notified` | `open` | candidato | ownership | n/a | abertura | timestamp canónico | não |
| `open` | `partially_completed` | candidato | ownership | n/a | resposta parcial | resposta por item | não |
| aberto/parcial | `submitted` | candidato | ownership | n/a | recibo imutável | hash/unique receipt | não |
| aberto/parcial | `expired` | scheduler | comando protegido | módulo | prazo terminado | lock + estado | sim |
| submetido | `resolved` | analista/sistema | `administrative_processes.update` | `applications.review` | revalidação aceite/rejeitada/manual | lote ligado ao pedido | sim |
| não terminal | `cancelled` | fluxo autorizado | permission existente | módulo | cancelamento auditado | transação | sim |

Respostas: `draft`, `submitted`, `under_review`, `accepted`, `rejected`,
`cancelled`. Resultados de revisão: `accepted`, `rejected`,
`requires_more_information`, `requires_manual_decision`, `not_applicable`.
Agregado de revalidação: `accepted`, `rejected`, `requires_manual_decision`.

## Exportação temporal

| Origem | Transição | Ator | Permission | Entitlement | Evento | Idempotência | Terminal |
|---|---|---|---|---|---|---|---|
| inexistente | `pending` | exportador | `applications.export` + `reports.export` | `applications.export` | pedido validado | idempotency key | não |
| `pending` | `processing` | worker `reports` | autorização capturada/revalidada | `applications.export` | snapshot/package | row lock/checkpoint | não |
| `processing` | `completed` | worker | contexto válido | `applications.export` | move atómico + hash final | source fingerprint | sim |
| `pending`/`processing` | `failed` | worker | contexto | módulo | falha terminal/esgotada | failure code | sim |
| pendente | `cancelled` | fluxo existente | Policy | módulo | cancelamento | lock | sim |
| `completed` | `expired` | scheduler/worker | comando interno | módulo | bloqueio de download e cleanup | unique job/lock | sim |

Modos suportados: `current_state`, `sealed_batch`, `phase_snapshot`,
`delta_between_batches`, `delta_since_datetime` e `final_result`.
