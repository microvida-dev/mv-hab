# Transição para área do inquilino

## Objetivo

Registar a passagem administrativa do candidato vencedor para condição de inquilino operacional.

## Implementação

`TenantTransitionService` valida vencedor, atribuição, habitação, contrato e entrega de chaves. Quando existir contrato ativo, usa `TenantFinancialAccountService` para garantir conta financeira.

## Regras

- Não apaga dados do candidato.
- Evita duplicados por vencedor/candidatura.
- Regista pré-condições e warnings.
- Candidato mantém role de candidato e passa a ver módulos de inquilino através dos dados financeiros/contratuais existentes.
