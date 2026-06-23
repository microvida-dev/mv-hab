# Confirmações de Processo — Sprint 24

## Objetivo

Emitir número de processo e confirmação interna/in-app para candidaturas, preservando payload e timeline processual quando disponível.

## Implementação

- Tabela: `process_confirmations`
- Model: `ProcessConfirmation`
- Controller: `ProcessConfirmationController`
- Services: `ProcessConfirmationService`, `ProcessNumberGenerator`, `AutomaticProcessConfirmationService`

## Regras

- Geração reutiliza confirmação existente salvo `force_regenerate`.
- Número de processo é único.
- Candidato não acede a rotas backoffice.
- A confirmação pode gerar notificação interna e evento de timeline.

## Pendências

- Validar formato oficial do número de processo por município.
- Definir envio externo apenas em sprint de integrações.
