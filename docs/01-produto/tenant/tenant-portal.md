# Área do Inquilino

## Objetivo

A área do inquilino consolida a fase pós-atribuição para utilizadores com contrato habitacional ativo, suspenso ou renovado. O acesso é separado da área geral de candidato através das rotas `tenant.*` em `/area-inquilino`.

## Implementado na Sprint 26

- Dashboard do inquilino com contratos, faturas em aberto, valor em dívida, pedidos de manutenção, vistorias e comunicações.
- Controlo de acesso por contrato ativo e role `candidate`.
- Criação/atualização automática de `TenantProfile` e `TenantContractAccess` a partir dos contratos existentes.
- Navegação dedicada no menu como `Área do inquilino`.

## Segurança

- Inquilinos só consultam contratos, faturas, pagamentos, pedidos, vistorias e comunicações associados ao próprio utilizador.
- O backoffice continua separado por middleware de roles municipais.
- Não foram criadas integrações externas nem dados pessoais reais.

## Pendências

- Definir regras municipais para bloqueio temporário de acesso.
- Expandir comprovativos documentais se houver modelo oficial aprovado.
