# Faturas e Pagamentos do Inquilino

## Objetivo

Criar uma camada operacional clara para consulta de faturas/rendas e pagamentos pelo inquilino, sem substituir o módulo financeiro existente.

## Implementado

- `tenant_invoices` para faturas operacionais de inquilino.
- `tenant_payments` para pagamentos operacionais registados.
- Listagem e detalhe em `/area-inquilino/faturas` e `/area-inquilino/pagamentos`.
- Backoffice para consultar, emitir faturas e registar pagamentos.
- Atualização automática do estado da fatura quando um pagamento é confirmado.

## Texto obrigatório

Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.

## Fora de âmbito

- Gateway bancário.
- Débito direto SEPA real.
- Recibo fiscal oficial.
- Comunicação automática à AT.
