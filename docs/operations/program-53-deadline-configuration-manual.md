# Programa 53 - Manual de configuração de prazos

## Princípio

Os prazos são configuração municipal por concurso. Este manual não define
duração legal. O operador deve usar apenas datas aprovadas para o procedimento.

## Timezone e persistência

- Introduzir datas no timezone municipal/aplicação configurado.
- Persistir instantes inequívocos e exportar ISO 8601 com offset/UTC.
- Não somar “24 horas” para representar um dia civil durante mudanças de hora.
- Confirmar `APP_TIMEZONE` e timezone da base antes de abrir o concurso.

## Sequência obrigatória

1. `applications`: receção de candidaturas.
2. `review`: análise inicial.
3. `corrections`: aperfeiçoamento.
4. `revalidation`: segunda análise.

Cada fase exige início e fim, com fim posterior ao início. Uma fase não pode
começar no mesmo instante nem antes do fim da anterior. Intervalos entre fases
são permitidos e aparecem como `between_phases`.

## Configuração e publicação

1. Abrir o concurso no backoffice autorizado.
2. Introduzir os quatro intervalos aprovados.
3. Confirmar o timezone apresentado.
4. Corrigir sobreposições ou inversões assinaladas pelo Form Request/service.
5. Guardar e rever a auditoria do antes/depois.
6. Publicar o concurso apenas pelo fluxo existente.

`contests.opens_at`/`closes_at` mantêm compatibilidade pública com a fase de
candidaturas; `contest_deadlines` é a fonte processual.

## Aperfeiçoamento e prorrogação

- O pedido canónico recebe o deadline da fase `corrections`; não se infere um
  prazo legal alternativo.
- Uma prorrogação usa `CorrectionDeadlineExtensionService`, fundamento e ator.
- A extensão preserva o deadline anterior, o novo instante e auditoria.
- Não editar diretamente `response_deadline_at` na base.

## Expiração

`corrections:expire` corre a cada cinco minutos com `withoutOverlapping()` e
`onOneServer()`. O serviço bloqueia/revalida o pedido antes de marcar `expired`.
Execuções duplicadas convergem; um pedido já submetido/resolvido não expira.

## Horário de verão (`Europe/Lisbon`)

- Primavera: horas locais inexistentes devem ser rejeitadas/normalizadas pela
  camada de data, nunca aceites silenciosamente como prazo diferente.
- Outono: a hora repetida exige offset ou conversão inequívoca para UTC.
- Os testes cobrem as duas transições e serialização ISO 8601.

## Alterações posteriores

Antes de alterar um prazo com candidaturas ou resultados existentes:

1. confirmar competência e fundamento;
2. verificar publicações já efetuadas;
3. avaliar notificações necessárias fora deste manual;
4. usar apenas o fluxo auditado;
5. executar health/smoke após a alteração.

Não reescrever snapshots, recibos, lotes ou publicações históricos.
