# Plano de migração permission-first do backoffice

## Base verificável

Este plano deriva do inventário determinístico da Sprint 46E:

- Route Collection: 1.165 rotas;
- rotas backoffice ainda protegidas por `role:` fixa: 706;
- rotas candidate preservadas fora do programa: 220;
- rotas residuais sem bounded context: 0;
- branch de origem: `sprint-46d-authorization-feedback`;
- commit-base: `c7b8021f9baf8d9ce9b57449e924d83ec3343e96`.

Os dados completos por rota estão em:

- `docs/access/backoffice-route-inventory.json`;
- `docs/access/backoffice-route-inventory.csv`;
- `docs/access/backoffice-route-inventory.md`.

## Quantificação por sprint

| Sprint | Âmbito | Rotas | Permissões sem equivalente semântico | Scope não confirmado estaticamente | Mutações com auditoria não detetada |
| --- | --- | ---: | ---: | ---: | ---: |
| 47A | Administração, segurança, utilizadores, equipas e RGPD | 72 | 38 | 43 | 10 |
| 47B | Candidaturas, documentos e processos | 102 | 13 | 91 | 13 |
| 47C | Elegibilidade, classificação e decisões | 78 | 16 | 73 | 0 |
| 47D | Audiência, reclamações, listas e atribuições residuais | 78 | 13 | 76 | 2 |
| 47E | Contratos e pós-atribuição | 58 | 9 | 52 | 3 |
| 47F | Finanças e pagamentos | 99 | 13 | 96 | 7 |
| 47G | Manutenção, vistorias, visitas e agenda | 96 | 19 | 89 | 17 |
| 47H | Relatórios, comunicações, notificações e configuração residual | 123 | 13 | 95 | 30 |
| **Total** |  | **706** | **134** | **615** | **82** |

Os contadores de scope e auditoria são candidatos de análise estática. Cada
sprint tem de confirmar o fluxo completo, incluindo Services chamados
indiretamente, antes de classificar uma ocorrência como vulnerabilidade.

## Evidência de guards, testes e contexto

| Sprint | Rotas sem referência nominal em testes | Sem `active.backoffice` | Decisão de feature pendente | Mixed-context |
| --- | ---: | ---: | ---: | ---: |
| 47A | 56 | 1 | 2 | 2 |
| 47B | 82 | 102 | 0 | 1 |
| 47C | 54 | 78 | 0 | 0 |
| 47D | 60 | 78 | 1 | 1 |
| 47E | 45 | 58 | 2 | 2 |
| 47F | 76 | 99 | 0 | 0 |
| 47G | 63 | 77 | 5 | 5 |
| 47H | 83 | 101 | 22 | 22 |
| **Total** | **519** | **594** | **32** | **33** |

As mesmas 594 rotas estão sem `mfa.backoffice` e `log.backoffice`. A evidência
de testes é conservadora: `route_name_reference` confirma que o nome exato da
rota aparece em pelo menos um teste; `not_detected` exige revisão e não prova,
isoladamente, que o fluxo não tenha cobertura indireta.

As 32 decisões de feature pendentes pertencem a rotas mixed-context. Nelas, o
entitlement não pode ser aplicado globalmente antes de o recurso ou a operação
resolver o domínio efetivo. O detalhe por rota está no JSON e no CSV.

## Sequência obrigatória

1. **47A** formaliza primeiro o scope de operador de plataforma e migra a área
   administrativa crítica.
2. **47B** reutiliza `applications.intake`, `applications.review` e
   `MunicipalRecordScopeService`.
3. **47C** separa execução, aprovação, rejeição e lock sem criar FeatureKeys.
4. **47D** decide o scope de entitlement em ADR antes de aplicar qualquer
   feature a audiências e listas.
5. **47E** migra estados contratuais e pós-atribuição sem feature entitlement.
6. **47F** trata finanças como contexto crítico, com MFA, transações,
   idempotência e auditoria before/after.
7. **47G** mantém separadas manutenção, vistorias, visitas e agenda.
8. **47H** volta a executar o inventário e elimina todas as rotas fixas
   remanescentes.

Cada sprint parte do HEAD publicado da anterior. Nenhuma rota deve ser
considerada migrada sem confirmar o middleware resolvido:

```text
auth
active.backoffice
mfa.backoffice
log.backoffice
permission:<permission>
municipality.feature:<feature>, quando aplicável
```

Também são obrigatórias Policy, scope municipal, Form Request autorizado,
auditoria nas mutações críticas e testes HTTP reais.

## Critério de fecho global

No fecho da 47H:

```text
backoffice_fixed_role_routes = 0
fixed_role_routes = candidate_fixed_role_routes
```

Os valores só podem ser fixados nos testes depois de medidos pelo comando
`access:audit-routes`.
