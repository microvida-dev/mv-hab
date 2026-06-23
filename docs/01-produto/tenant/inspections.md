# Vistorias na Área do Inquilino

## Objetivo

Permitir ao inquilino consultar vistorias visíveis relativas ao seu contrato/habitação.

## Implementado

- Rotas `tenant.inspections.*`.
- Reutilização do modelo `PropertyInspection`.
- Apenas vistorias com `tenant_visible = true` são apresentadas.
- Relatórios e anexos continuam dependentes dos controllers autorizados existentes.

## Texto obrigatório

O agendamento de vistoria está sujeito à disponibilidade dos serviços municipais e à confirmação das partes envolvidas.

## Fora de âmbito

- Assinatura digital de auto de vistoria.
- Integrações externas de agenda.
