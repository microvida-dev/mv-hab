# Presenças em sorteio

## Objetivo

Registar presença, ausência, atraso ou justificação dos convocados no ato de sorteio.

## Regras

- A presença fica associada a sorteio, candidatura, candidato e, quando disponível, convocatória/participante.
- Alterações passam por `DrawAttendanceService`.
- A presença não altera automaticamente o resultado do sorteio.
- O registo atualiza o estado do participante para consulta operacional.

## Estados

`pending`, `present`, `absent`, `justified`, `late`, `not_required`.
