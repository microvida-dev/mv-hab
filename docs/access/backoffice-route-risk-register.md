# Registo de risco das rotas backoffice

## Distribuição

| Risco | Rotas | Regra |
| --- | ---: | --- |
| Crítico | 299 | Segurança, utilizadores, RGPD, documentos privados, decisões, contratos, finanças, pagamentos, exports e downloads |
| Alto | 193 | Candidaturas, processos, elegibilidade, scoring, audiência, reclamações, listas e atribuições |
| Médio | 168 | Manutenção, vistorias, visitas, agenda, comunicações e notificações |
| Baixo | 46 | Relatórios agregados, configuração auxiliar e catálogos sem sinal sensível |
| **Total** | **706** |  |

## Achados transversais

| Achado estático | Rotas | Tratamento |
| --- | ---: | --- |
| Middleware permission-first ainda ausente | 706 | Migrar apenas na sprint do bounded context |
| Permission semanticamente disponível no catálogo | 572 | Aplicar e validar por testes de menor privilégio |
| Permission sem equivalente semântico seguro | 134 | Decisão de catálogo; não substituir por `update` genérico |
| Form Request com `authorize(): true` | 160 | Alinhar com a ability específica da Policy |
| Scope municipal não confirmado estaticamente | 615 | Ler controller, Policy e Service; aplicar fail-closed quando necessário |
| Model route-bound sem Policy detetada | 9 | Criar/reforçar Policy antes de remover a role fixa |
| Mutação crítica sem auditoria detetada | 82 | Confirmar Service indireto ou adicionar auditoria transacional |
| Dados privados ou pessoais | 423 | Minimização, Policy, scope e auditoria de acesso |
| MFA sensível | 523 | Preservar `mfa.backoffice` e classificação da permission |
| Sem `active.backoffice` | 594 | Adicionar o guard sem remover autenticação ou Policy |
| Sem `mfa.backoffice` | 594 | Aplicar MFA real antes de remover a role fixa |
| Sem `log.backoffice` | 594 | Preservar correlation ID e logging seguro |
| Referência nominal em testes não detetada | 519 | Criar teste HTTP real ou identificar cobertura indireta |
| Rotas mixed-context | 33 | Resolver o domínio antes de aplicar entitlement |
| Decisão de feature pendente | 32 | Não criar FeatureKey nem aplicar middleware global |
| Rotas de plataforma no backlog fixo | 0 | Não inferir scope de operador a partir deste valor |

Estes achados resultam de Route Collection, middleware resolvido, reflection e
análise estática. `missing` significa ausência de evidência nas fontes
inspecionadas, não prova isolada de vulnerabilidade.

O detetor de cobertura procura referências exatas a nomes `backoffice.*` ou
`admin.*` nos testes existentes. Não classifica URLs literais nem cobertura
indireta por data providers; por isso usa `not_detected`, nunca “sem teste”.

## Entitlements e contexto operacional

- 105 rotas operacionais foram classificadas como exigindo feature existente e
  ainda não possuem o respetivo middleware;
- 32 rotas têm decisão de feature pendente por serem mixed-context;
- 33 rotas são mixed-context no total;
- 673 rotas foram classificadas como municipais;
- nenhuma rota fixa do backlog foi classificada como plataforma.

O último valor não demonstra que não existam operações de plataforma: essas
rotas podem já estar migradas e, por isso, fora do artefacto `--only-fixed-role`.
A Sprint 47A tem de rever explicitamente o scope de operador.

## Riscos bloqueantes

- conceder aprovação, rejeição, publicação, assinatura, reversão ou export por
  uma permission genérica;
- remover `role:` sem adicionar `active.backoffice`, `mfa.backoffice`,
  `log.backoffice` e permission;
- aplicar entitlement a configuração estrutural sem requisito aprovado;
- considerar `municipality_id = null` suficiente para operador de plataforma;
- aceitar `authorize(): true` numa mutação migrada;
- contornar Policy com filtro visual ou query de controller;
- registar payload, documentos ou PII em logs de recusa/auditoria;
- alterar as 220 rotas candidate no programa 47.

## Mitigação

Cada rota tem bounded context, risco, sprint de destino, permission candidata,
Policy, Form Request, feature, scope, auditoria e recomendação no JSON/CSV
canónico. A migração deve ser incremental, com testes entre Municípios e
auditoria de rotas após cada bloco.
