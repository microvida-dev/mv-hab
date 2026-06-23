# Alertas Internos — Sprint 24

## Objetivo

Sinalizar prazos, documentos pendentes, candidaturas sem técnico, listas por gerar, atas por rever, relatórios falhados e confirmações pendentes.

## Implementação

- Tabela: `internal_alerts`
- Model: `InternalAlert`
- Controller: `InternalAlertController`
- Services: `InternalAlertDetector`, `InternalAlertResolver`, `InternalAlertService`

## Operação

- Os alertas podem ser detetados por ação manual no backoffice.
- Alertas podem ser resolvidos ou dispensados por utilizador autorizado.
- O estado do alerta não altera automaticamente estados administrativos da candidatura.

## Pendências

- Definir scheduler de deteção recorrente.
- Definir SLA e matriz de escalonamento por município.
