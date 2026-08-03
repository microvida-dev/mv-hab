# Fecho da Sprint 47H

## Estado

`REPOSITORY_PASS_DEPLOYMENT_GATED`

O repositório está tecnicamente apto para revisão e merge. Não foi
efetuado deploy, merge ou alteração direta de `main`. A disponibilização
em ambiente exige o processo normal de aprovação, aplicação da migration
e validação operacional.

## Objetivo e base

A Sprint 47H concluiu o programa permission-first do backoffice nos
domínios finais de reporting, comunicações, notificações e configuração.
O trabalho partiu do commit:

```text
58f47b18198a562e105b3b68c67dbc3fdece8c40
```

A branch de implementação foi:

```text
sprint-47h-reporting-communications-notifications-config-permissions
```

A arquitetura consolidada aplica cumulativamente:

```text
permission exata
&& Policy/ability semântica
&& scope municipal
&& estado ou transição válida
&& utilizador ativo
&& MFA
&& logging/auditoria
```

As roles continuam a agregar permissions. Não foi introduzido bypass por
role, grant direto a utilizador, wildcard adicional ou entitlement sem
correspondência funcional.

## Inventário imutável

O manifesto
`docs/access/manifests/sprint-47h-route-manifest.json` fixa exatamente
123 rotas:

| Domínio | Rotas |
|---|---:|
| Reporting | 38 |
| Communications | 36 |
| Notifications | 41 |
| Configuration | 8 |
| **Total** | **123** |

O snapshot inicial registava:

- 123 permissions pendentes;
- 123 rotas com middleware de role fixa;
- 101 rotas sem os três guards de backoffice;
- 123 rotas residuais do backoffice com role middleware.

O snapshot final
`docs/access/progress/sprint-47h-after-route-migration.json` regista:

| Indicador | Resultado |
|---|---:|
| `manifest_routes` | 123 |
| `runtime_found` | 123 |
| `missing` | 0 |
| `duplicates` | 0 |
| `permission_exact` | 123 |
| `permission_pending` | 0 |
| `role_middleware_remaining` | 0 |
| `active_backoffice_missing` | 0 |
| `mfa_backoffice_missing` | 0 |
| `log_backoffice_missing` | 0 |
| `total_backoffice_fixed_role_routes` | 0 |

O inventário global final encontrou 1 170 rotas. Permanecem 220 rotas
com role middleware exclusivamente no portal do candidato, fora do
universo do backoffice e do manifesto 47H. O backoffice tem zero rotas
com role middleware.

## Catálogo de permissions

O manifesto reconcilia 86 permissions únicas:

- 29 permissions existentes reutilizadas;
- 57 permissions semânticas novas;
- 62 rotas de leitura;
- 61 rotas de mutação.

### Reporting

Foram separadas as operações de:

- consulta de reporting e dashboards;
- execução de relatórios;
- gestão de definições;
- gestão de dashboards e widgets;
- gestão de indicadores;
- gestão de filtros predefinidos;
- consulta executiva.

`reports.run` passou a distinguir execução de simples leitura. Reporting
municipal genérico não reutiliza indevidamente
`applications.export`.

### Communications

Foram separadas as operações de:

- consultar, criar, cancelar e arquivar logs;
- repetir entregas;
- registar entrega postal;
- consultar e gerir variáveis de comunicação;
- gerir tickets, mensagens e anexos privados;
- gerar, aprovar, publicar e descarregar atas e documentos.

Foi formalizada `documents.publish`, já exigida semanticamente pela
Policy de documentos processuais.

### Notifications

Foram separadas as operações de:

- gerir templates;
- criar, aprovar, ativar e arquivar versões;
- criar, ativar e desativar regras de evento;
- consultar preferências;
- detetar, resolver e dispensar alertas internos;
- criar notificações e registar estados controlados de envio;
- operar Work Tasks através do catálogo granular existente.

Uma permission de leitura não concede envio, repetição, cancelamento,
aprovação ou ativação.

### Configuration

Foram preservadas as permissions `settings.view`, `settings.create` e
`settings.update`, com permissions distintas para ativar e desativar.
O fecho de concurso usa `contests.close`.

Configuração global exige operador de plataforma explícito. Configuração
municipal exige origem municipal canónica.

## Matriz least-privilege

### Administrator

Mantém exclusivamente o wildcard histórico `*`. Não existe tratamento
especial baseado no nome da role nas rotas da Sprint 47H.

### Técnico municipal

Mantém leitura e operações municipais comuns de reporting,
comunicações, notificações e tarefas. Não recebe gestão global de
templates nem configuração de plataforma.

### Gestor de habitação

Mantém reporting e dashboards habitacionais, comunicações operacionais,
notificações e trabalho de equipa compatível com o domínio.

### Apoio

Mantém tickets, mensagens, anexos, FAQ contextual, notificações
operacionais e Work Tasks necessárias ao atendimento. Não recebe
reporting financeiro, configuração de plataforma ou gestão global de
templates. MFA permanece obrigatória.

### Gestor financeiro

Mantém reporting e operações financeiras autorizadas, sem configuração
de comunicações não financeiras.

### Gestor jurídico

Mantém reporting, atas, documentos e comunicações jurídicas aplicáveis,
sem configuração técnica global.

### Auditor

Permanece read-only. Não recebe create, update, delete, publish, send,
retry, cancel, resolve, activate ou deactivate.

### Candidato

Recebe zero permissions backoffice da Sprint 47H. Foi ainda reforçado o
ownership das visitas: um candidato não pode consultar nem alterar uma
visita de outro candidato, mesmo quando ambos pertencem ao mesmo
Município.

## Entitlements

Não foi criado qualquer `FeatureKey`.

Os entitlements existentes:

- `applications.intake`;
- `applications.review`;
- `applications.export`;

não representam reporting genérico, comunicações, notificações ou
configuração. Não foram reutilizados fora da respetiva semântica.

## Isolamento municipal

`MunicipalRecordScopeService` foi expandido para aplicar scope antes de
filtros, agregações, paginação, execução e download nos recursos da
Sprint 47H.

As validações cobrem, entre outros:

- definições, execuções e exports de relatórios;
- dashboards, widgets, indicadores e filtros;
- logs, entregas e notificações oficiais;
- templates, versões e regras de notificação;
- alertas internos;
- FAQ contextual;
- tickets, mensagens e anexos;
- atas e templates processuais;
- configurações administrativas;
- Work Tasks.

Registos sem origem municipal, com relações órfãs ou com fontes
municipais contraditórias falham fechado.

### Origem canónica de comunicações

Foi criado
`app/Services/Municipalities/CommunicationMunicipalContextService.php`.
O serviço resolve e reconcilia o Município através do recurso de origem,
ator, destinatário e template aplicável.

O destinatário isolado nunca define o Município da comunicação. As
relações com programa, concurso, candidatura, contrato, atribuição,
imóvel, reclamação, audiência, ticket ou lista têm de convergir para o
mesmo Município.

`communication_logs` passa a guardar `municipality_id` como snapshot
canónico através da migration reversível:

```text
2026_07_26_030000_add_municipality_id_to_communication_logs.php
```

A migration cria foreign key com `nullOnDelete` e índice composto
`municipality_id/status`. Registos históricos com valor nulo não recebem
acesso global implícito e falham fechado.

## Operador global

O scope global depende de `PlatformOperatorAssignment` ativo, não
revogado e validado por `PlatformOperatorScopeService`.

Um utilizador com `municipality_id = null` sem assignment válido não é
tratado como operador global. Um operador revogado ou inativo também
falha fechado.

Catálogos system podem ser lidos apenas quando previsto. A respetiva
mutação requer permission de plataforma e assignment explícito.

## Reporting e exports

As queries são scoped antes de filtros e agregações. Runs e exports
preservam definição, ator e contexto municipal.

Os downloads:

- exigem permission específica e Policy;
- validam ownership municipal;
- validam expiração e existência do ficheiro;
- usam storage privado;
- validam paths;
- auditam o acesso;
- devolvem comportamento fail-closed para ficheiro ausente, expirado ou
  incoerente.

Uma URL assinada não substitui a autorização.

## Comunicações e notificações

Os services críticos voltam a validar permission, scope e estado antes
de efeitos laterais. O envio não pode atravessar Municípios.

Os jobs:

- transportam identificadores de ator e Município;
- preservam o contexto de permission e auditoria;
- revalidam o recurso antes de executar;
- são idempotentes perante repetição;
- não marcam sucesso quando o provider falha;
- não registam PII nos metadados operacionais.

Preferências pessoais continuam limitadas ao owner. Templates system e
municipais seguem regras diferentes de leitura e mutação.

## Work Tasks e fluxos legados

A atribuição automática de Work Tasks deriva o Município do recurso
operacional e escolhe apenas equipas ativas desse Município. Quando ator
e recurso têm Municípios diferentes, a operação é rejeitada.

Os fixtures legados de atribuição, contratos, finanças, visitas, seeder
integrado e analytics foram alinhados com esta invariável, sem relaxar o
comportamento de produção.

A visibilidade de auditor em Work Tasks é estritamente read-only e não
é reutilizada para reatribuição ou mutation.

## Operações críticas

O manifesto identifica dois downloads críticos:

| Rota | Permission |
|---|---|
| `backoffice.procedure-minutes.download` | `documents.download` |
| `backoffice.support-ticket-attachments.download` | `support.attachments.download` |

Ambos exigem MFA, Policy, scope municipal, storage privado, validação de
path/existência e auditoria.

As restantes operações sensíveis de envio, retry, cancelamento,
ativação, aprovação e configuração usam abilities próprias e logging de
backoffice.

## Testes permanentes

Foram criados:

- `ReportingCommunicationsNotificationsConfigPermissionRoutesTest`;
- `ReportingCommunicationsNotificationsConfigRoleMatrixTest`;
- `ReportingCommunicationsNotificationsConfigMunicipalScopeTest`;
- `ReportingCommunicationsNotificationsConfigCriticalFlowTest`.

Estes testes cobrem:

- integridade das 123 rotas;
- middleware de permission exata;
- ausência de role middleware;
- guards active/MFA/log;
- matriz least-privilege;
- candidate e auditor;
- operador global ativo, revogado e inativo;
- utilizador sem Município;
- isolamento municipal A/B;
- reporting, export e download;
- comunicações e notificações;
- templates system/municipais;
- preferências pessoais;
- configurações globais/municipais;
- jobs, idempotência e auditoria.

Os testes permanentes das Sprints 47A a 47G foram preservados.

## Validação executada

| Comando/área | Resultado |
|---|---|
| `php -l` | PASS, 160 ficheiros alterados |
| `vendor/bin/pint` | PASS |
| `vendor/bin/pint --test` | PASS |
| `vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| `composer validate --strict` | PASS |
| `php artisan optimize:clear` | PASS |
| `php artisan route:cache` | PASS |
| `php artisan route:clear` | PASS |
| `php artisan route:list --except-vendor` | PASS, 1 170 rotas |
| testes permanentes e inventário final | PASS, 41 testes / 2 791 asserções |
| regressões anteriormente falhadas | PASS, 43 testes / 350 asserções |
| suite de segurança | PASS, 435 testes / 14 895 asserções |
| `php artisan test` | PASS, 1 256 testes / 19 838 asserções |
| `npm run build` | PASS |
| `php artisan migrate --pretend` | PASS |
| `git diff --check` | PASS |

## Checksums

SHA-256 do manifesto imutável:

```text
84ed20240f868cdfb50a76f24b2044b978414d1e5bdb5011fe99f22611cc4a2a
```

SHA-256 do snapshot final:

```text
0c3779e2b2eb6ebef0f603f9fd252a9bb3c58613e1e568657ea9bc455eb97f6e
```

## Limitações e gates de deployment

- A migration de `communication_logs.municipality_id` está pendente no
  ambiente local e deve ser aplicada pelo processo de deployment.
- Registos históricos de comunicação precisam de reconciliação
  municipal controlada antes de serem visíveis; até lá falham fechado.
- As 220 rotas candidate com role middleware permanecem fora do
  backoffice e do programa 47.
- Não foram criados entitlements comerciais novos. A evolução desse
  catálogo fica reservada ao Programa 48.
- Não foi efetuado deploy nem merge para `main`.

## Preparação para o Programa 48

O backoffice termina o Programa 47 sem middleware de role fixa. A base
permission-first permite que o Programa 48 trate expansão de produto,
entitlements comerciais e perfis municipais configuráveis sem reabrir
o modelo de autorização operacional.

## Decisão final

Todos os critérios técnicos da Sprint 47H estão cumpridos. O repositório
fica no estado:

`REPOSITORY_PASS_DEPLOYMENT_GATED`
