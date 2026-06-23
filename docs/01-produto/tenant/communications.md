# Comunicações do Inquilino

## Objetivo

Criar um canal interno de comunicação entre inquilino e serviços municipais para a fase pós-atribuição.

## Implementado

- `tenant_communications`.
- `tenant_communication_messages`.
- Criação de comunicação pelo inquilino.
- Resposta pelo backoffice.
- Isolamento por `user_id`.
- Associação opcional a contrato do próprio inquilino.

## Segurança

- Um inquilino não consegue associar uma comunicação a contrato de terceiro.
- Um inquilino não consulta comunicação de outro utilizador.
- Auditoria regista criação e mensagens.
