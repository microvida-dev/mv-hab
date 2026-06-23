# Entrega de chaves

## Objetivo

Agendar, reagendar, cancelar e concluir entrega de chaves para vencedor.

## Regras

- Só deve ser criada para vencedor ativo.
- Candidato vê apenas agendamentos próprios.
- Conclusão regista técnico responsável e auditoria.

Texto obrigatório:

> A entrega de chaves só deve ocorrer após validação dos requisitos administrativos, contratuais e documentais aplicáveis.

## Estados

`pending_schedule`, `scheduled`, `rescheduled`, `cancelled`, `completed`, `missed`, `blocked`.
