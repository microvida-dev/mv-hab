# Sprint 46E - Inventário permission-first de rotas backoffice

## 1. Objetivo

A Sprint 46E criou um inventário determinístico e read-only das rotas
backoffice ainda protegidas por middleware fixo `role:`. O inventário combina
Route Collection, middleware resolvido, reflection, Form Requests, Policies,
catálogo de permissions, feature entitlements, scope municipal, auditoria e
referências existentes em testes.

Identificador: `TECH-AUTH-001A`.

Esta sprint não migrou rotas nem alterou comportamento de autorização. O
resultado é o plano verificável para as Sprints 47A a 47H.

## 2. Branch e base

- branch: `sprint-46e-permission-route-inventory`;
- branch de origem: `sprint-46d-authorization-feedback`;
- commit-base: `c7b8021f9baf8d9ce9b57449e924d83ec3343e96`;
- working tree inicial: limpo;
- remoto: `git@github.com:microvida-dev/mv-hab.git`.

Commits:

- `16ccfc89 feat(access): adicionar inventário permission-first do backoffice`;
- `da5cff9a test(access): cobrir inventário de rotas backoffice`;
- `82251d87 docs(access): planear migração permission-first por contexto`;
- commit deste relatório.

## 3. Baseline inicial

| Gate ou métrica | Resultado inicial |
| --- | --- |
| PHPUnit completo | PASS - 1.070 testes, 7.529 asserções |
| UX | PASS - 130 testes, 645 asserções |
| Composer | PASS |
| Pint global | PASS |
| PHPStan global | PASS - 0 erros |
| Build Vite | PASS |
| `git diff --check` | PASS |
| Route Collection | 1.165 rotas |
| Rotas com role fixa | 926 |
| Backoffice com role fixa | 706 |
| Candidate com role fixa | 220 |
| Rotas com permission middleware | 195 |
| Backoffice sem `active.backoffice` | 594 |
| Backoffice sem `mfa.backoffice` | 594 |
| Backoffice sem `log.backoffice` | 594 |

Os ficheiros canónicos do baseline são:

- `/tmp/routes-before-46e.json`;
- `/tmp/access-audit-before-46e.json`;
- `/tmp/program-46e-47-base-commit.txt`.

## 4. Comando de inventário

Foi criado:

```text
php artisan access:inventory-backoffice-routes
```

Opções:

- `--format=table|json|csv|markdown`;
- `--output=`;
- `--only-fixed-role`;
- `--bounded-context=`;
- `--risk=`;
- `--missing-permission`;
- `--missing-policy`;
- `--missing-scope`;
- `--mutation-without-audit`.

O comando:

- não cria, atualiza ou elimina dados;
- não escreve auditoria;
- ordena os resultados de forma estável;
- não inclui timestamps variáveis no envelope;
- produz output byte a byte idêntico para o mesmo estado do código, das rotas e
  do catálogo;
- pode escrever para ficheiro ou stdout;
- mantém inferências identificadas através de `confidence`.

## 5. Modelo do inventário

Foram criados:

- `BackofficeRouteBoundedContext`, com os 25 contextos obrigatórios e destino
  47A-47H;
- `RouteInventoryRisk`, com risco crítico, alto, médio e baixo;
- `BackofficeRouteInventoryService`, responsável apenas pela composição
  estática;
- `InventoryBackofficeRoutes`, responsável por filtros e serialização.

Cada rota contém, entre outros:

- nome, URI, métodos, controller e action;
- middleware resolvido, role ativa/excluída e guards de backoffice;
- permission atual, disponibilidade no catálogo e recomendação semântica;
- Policy, ability e origem da conclusão;
- Form Request e classificação de `authorize()`;
- feature atual, necessidade prevista e FeatureKey existente;
- origem do Município, scope, fail-closed e contexto municipal/plataforma/misto;
- modelo principal, leitura/mutação, sensibilidade MFA e dados privados;
- requisito e evidência de auditoria;
- bounded context, risco, confiança e sprint de destino;
- referência nominal da rota em testes existentes;
- recomendação de migração.

## 6. Resultado do inventário fixo

| Métrica | Rotas |
| --- | ---: |
| Backoffice com role fixa inventariado | 706 |
| Permission middleware efetivo | 0 |
| Permission semântica disponível no catálogo | 572 |
| Permission sem equivalente semântico seguro | 134 |
| Model route-bound sem Policy detetada | 9 |
| Scope municipal não confirmado estaticamente | 615 |
| Mutações com auditoria obrigatória não detetada | 82 |
| Form Requests com `authorize(): true` | 160 |
| Dados privados/pessoais | 423 |
| Rotas sensíveis a MFA | 523 |
| Sem `active.backoffice` | 594 |
| Sem `mfa.backoffice` | 594 |
| Sem `log.backoffice` | 594 |
| Referência nominal em testes detetada | 187 |
| Referência nominal em testes não detetada | 519 |
| Mixed-context | 33 |
| Decisão de feature pendente | 32 |
| Residual/desconhecido | 0 |

`missing` e `not_detected` significam ausência de evidência na análise estática.
Não constituem, por si só, prova de vulnerabilidade ou ausência absoluta de
cobertura.

## 7. Distribuição por sprint

| Sprint | Âmbito | Rotas |
| --- | --- | ---: |
| 47A | Administração, segurança, utilizadores, equipas e RGPD | 72 |
| 47B | Candidaturas, documentos e processos | 102 |
| 47C | Elegibilidade, classificação e decisões | 78 |
| 47D | Audiência, reclamações, listas e atribuições | 78 |
| 47E | Contratos e pós-atribuição | 58 |
| 47F | Finanças e pagamentos | 99 |
| 47G | Manutenção, vistorias, visitas e agenda | 96 |
| 47H | Relatórios, comunicações, notificações e configuração | 123 |
| **Total** |  | **706** |

Nenhuma das 706 rotas ficou sem bounded context, risco ou sprint de destino.

## 8. Permissions, Features e Policies

### Permissions

O inventário recomenda apenas permissions já existentes no catálogo. Operações
sensíveis sem equivalente exato ficam sem recomendação, em vez de serem
convertidas para `update` genérico. Existem 134 decisões semânticas pendentes.

### FeatureKeys

Não foi criada nem alterada nenhuma FeatureKey. Foram identificadas:

- 105 rotas candidatas a features já existentes;
- 32 decisões pendentes em mixed-context;
- 33 rotas mixed-context no total.

Numa rota mixed-context, o domínio deve ser resolvido antes de aplicar o
entitlement.

### Policies e Form Requests

Foram detetadas 9 rotas model-bound sem Policy:

- uma atualização de item de checklist de segurança;
- oito operações de equipas municipais.

Foram detetados 160 Form Requests com autorização incondicional. A correção deve
ser feita na sprint do domínio, usando a mesma ability específica do controller.

## 9. Scope municipal, MFA e auditoria

O inventário classifica 673 rotas fixas como municipais e 33 como
mixed-context. Nenhuma rota fixa foi classificada como plataforma; este valor
não substitui a revisão estrutural do operador de plataforma na Sprint 47A,
porque as rotas de plataforma podem já estar fora do backlog fixo.

O scope foi confirmado estaticamente em 16 rotas, classificado como candidato
em 75 e não confirmado em 615. Cada Sprint 47 tem de rever controller, Policy,
Service e query e testar Município A contra Município B.

As 594 ausências de `active.backoffice`, `mfa.backoffice` e `log.backoffice`
correspondem ao baseline real. O inventário não adiciona os guards; apenas
impede que sejam esquecidos durante a migração.

Existem 82 mutações cujo requisito de auditoria é obrigatório e cuja
implementação não foi detetada nas fontes diretamente relacionadas. Services
indiretos devem ser revistos antes de considerar cada achado uma falha real.

## 10. Artefactos

Foram produzidos:

- `docs/access/backoffice-route-inventory.json`;
- `docs/access/backoffice-route-inventory.csv`;
- `docs/access/backoffice-route-inventory.md`;
- `docs/access/backoffice-route-migration-plan.md`;
- `docs/access/backoffice-route-risk-register.md`;
- `docs/access/backoffice-route-permission-gaps.md`;
- `docs/access/backoffice-route-policy-gaps.md`;
- `docs/access/backoffice-route-municipal-scope-gaps.md`.

O JSON e o CSV contêm o detalhe completo por rota. Os documentos curados
distinguem claramente factos confirmados de candidatos de análise estática.

## 11. Ficheiros alterados

Produção:

- `app/Console/Commands/InventoryBackofficeRoutes.php`;
- `app/Enums/BackofficeRouteBoundedContext.php`;
- `app/Enums/RouteInventoryRisk.php`;
- `app/Services/Access/BackofficeRouteInventoryService.php`.

Teste novo:

- `tests/Feature/Security/InventoryBackofficeRoutesCommandTest.php`.

Documentação:

- os oito artefactos em `docs/access`;
- este relatório.

Testes existentes alterados: nenhum.

Migrations: nenhuma.

Rotas alteradas: nenhuma.

Policies, Form Requests, Services de domínio e regras de negócio alterados:
nenhum.

## 12. Testes do comando

O teste dedicado cobre:

- correspondência do total com a Route Collection;
- campos obrigatórios;
- rotas sem bounded context vazio;
- role fixa;
- permission atual e candidata;
- Policy e Form Request;
- feature e scope municipal;
- guards `active`, MFA e logging;
- evidência de testes;
- filtros por contexto, risco e gaps;
- JSON, CSV e Markdown;
- determinismo;
- ausência de mutação em permissions, users e audit logs.

Resultado dirigido atual:

- PASS - 6 testes, 100 asserções;
- PHPStan dirigido: PASS - 0 erros;
- Pint dirigido: PASS.

## 13. Gates finais

| Gate | Resultado |
| --- | --- |
| Integridade dos testes | PASS - 1 ficheiro, 0 violações, 0 avisos |
| Pint incremental | PASS - 5 ficheiros |
| Pint global | PASS |
| PHPStan global | PASS - 0 erros |
| PHPUnit completo | PASS - 1.076 testes, 7.629 asserções |
| UX | PASS - 130 testes, 645 asserções |
| Composer | PASS |
| `optimize:clear` | PASS |
| Build Vite | PASS |
| `route:list --except-vendor` | PASS - 1.162 rotas |
| Auditoria de rotas final | PASS - contadores inalterados |
| `git diff --check` | PASS |

## 14. Auditoria de rotas antes/depois

A Sprint 46E não altera rotas. O resultado final deve manter:

| Métrica | Antes | Depois |
| --- | ---: | ---: |
| Total | 1.165 | 1.165 |
| Role fixa | 926 | 926 |
| Backoffice com role fixa | 706 | 706 |
| Candidate com role fixa | 220 | 220 |
| Permission middleware | 195 | 195 |
| Sem active/MFA/log | 594 | 594 |

O resumo JSON antes/depois foi normalizado e comparado byte a byte, sem
diferenças. A Sprint 46E não migrou nenhuma rota e preservou as 220 rotas
candidate fora do programa 47.

## 15. Riscos e backlog

- A análise de Policy, scope e auditoria é estática e pode não seguir Services
  chamados em profundidade.
- A evidência de testes procura o nome exato da rota; URLs literais e cobertura
  indireta podem ficar como `not_detected`.
- As 134 permissions sem equivalente seguro bloqueiam migração automática.
- As 32 decisões de feature mixed-context exigem resolução de domínio.
- A Sprint 47A não pode prosseguir se os operadores de plataforma atuais não
  puderem ser identificados com evidência explícita.
- `TECH-TENANCY-002` e expansão de FeatureKeys permanecem fora do âmbito.

## 16. Classificação final

**PASS**

Todos os gates obrigatórios passaram. Não foram alteradas rotas, Policies,
regras de negócio, base de dados ou comportamento de autorização. A branch
fica apta a publicação e a Sprint 47A só poderá avançar depois de confirmada a
igualdade entre os hashes local e remoto.
