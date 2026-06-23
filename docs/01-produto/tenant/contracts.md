# Contratos na Área do Inquilino

## Objetivo

Permitir ao inquilino consultar os contratos pós-atribuição associados às suas habitações.

## Implementado

- Listagem em `/area-inquilino/contratos`.
- Detalhe do contrato com estado, renda mensal e habitação.
- Reutilização do modelo `Contract` e relações já existentes.

## Decisão técnica

Não foi criado um segundo módulo de contratos. A Sprint 26 reutiliza `contracts`, `contract_deposits`, documentos contratuais e contas financeiras existentes.

## Pendências

- Downloads adicionais continuam a usar controllers autorizados já existentes.
- Minutas oficiais e assinatura digital continuam fora desta sprint.
