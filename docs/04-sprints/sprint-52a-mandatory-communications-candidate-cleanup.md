# MV-HAB — Relatório de Auditoria e Solução da Sprint 52A

## Comunicações obrigatórias e limpeza da área do candidato

**Data da auditoria:** 29 de julho de 2026
**Artefacto auditado:** `mvhab-sprint-52a-audit-sprint-51-municipal-application-demo-seeder-20260729-172844.zip`
**Branch auditada:** `sprint-51-municipal-application-demo-seeder`
**Commit auditado:** `100602bfbe8797eb11021c346334f255dc9fed97`
**Stack observada:** Laravel 13.12.0, PHP 8.4.21, MySQL, Blade, Eloquent ORM
**Classificação proposta:** `REPOSITORY_PASS_DEPLOYMENT_GATED`

---

# 1. Resumo executivo

A base atual permite executar a Sprint 52A de forma incremental, sem migrations destrutivas e sem eliminar módulos históricos.

A solução preparada introduz quatro fronteiras claras:

1. **Comunicações processuais:** email oficial obrigatório, sem opt-out, acompanhado por cópia in-app.
2. **Submissão da candidatura:** bloqueada enquanto o email da conta não for válido e verificado.
3. **Visitas legacy do candidato:** código e dados preservados, mas rotas e navegação desativados por defeito.
4. **Apoio ao inquilino:** disponível apenas após transição concluída, contrato ativo, entrega de chaves concluída e portal do inquilino ativo.

Não é criada nova tabela. A implementação reutiliza `communication_logs`, `communication_deliveries`, `communication_attempts`, `official_notifications`, filas Laravel, auditoria existente, Policies e os modelos de ciclo contratual já presentes.

---

# 2. Evidência observada no ZIP

## 2.1. Estado Git

```text
Branch: sprint-51-municipal-application-demo-seeder
HEAD: 100602bfbe8797eb11021c346334f255dc9fed97
Rotas: 1170
Working tree: .env.example modificado
```

Rotas candidate relevantes encontradas:

```text
candidate.visits.*                      7
candidate.support-tickets.*             6
candidate.notification-preferences.*    2
tenant.*                                18
```

## 2.2. Configuração local observada

```text
APP_ENV=local
APP_DEBUG=true
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

Esta configuração é aceitável apenas para desenvolvimento local. Não satisfaz os requisitos de entrega processual em produção.

## 2.3. Achado crítico — segredo em `.env.example`

O working tree continha:

```text
MVHAB_DEMO_USER_PASSWORD='<redigido>'
```

Mesmo sendo uma credencial de demonstração, um segredo não pode permanecer num ficheiro versionável. Deve ser tratado como exposto:

- restaurar `.env.example` para valores neutros;
- mover a configuração local para `.env`, que deve estar ignorado pelo Git;
- gerar uma nova palavra-passe aleatória;
- voltar a executar o seeder demo caso seja necessário atualizar o utilizador já persistido;
- confirmar que o valor não entrou em commits, logs, tickets ou outros artefactos partilhados.

O script de aplicação valida o diff exato observado, restaura `.env.example` e gera uma nova palavra-passe local, sem incluir a credencial exposta no patch.

---

# 3. Diagnóstico funcional

## 3.1. Registo e preferências

A aplicação ainda recolhia no registo:

```text
wants_email_notifications
wants_sms_notifications
wants_postal_notifications
```

A página autónoma de preferências permitia ainda alterar:

```text
allow_email
allow_sms
email_for_notifications
phone_for_notifications
```

As colunas antigas são mantidas para compatibilidade histórica, mas deixam de governar notificações processuais.

## 3.2. Dispatcher de notificações

O `NotificationEventDispatcher` criava entregas de acordo com as regras ativas e consultava `NotificationPreference` para resolver o destino. Consequentemente, uma regra mal configurada, desativada ou limitada ao canal in-app podia impedir o email oficial.

A execução de email era síncrona quando a regra indicava envio imediato. Não existia um Job dedicado com política de retry/backoff para email processual.

## 3.3. Centro de notificações

A plataforma já possuía:

- `CommunicationLog`;
- `CommunicationDelivery`;
- `CommunicationAttempt`;
- `OfficialNotification`;
- recibos de envio/leitura;
- auditoria das entregas;
- KPI de falhas no backoffice.

A solução reutiliza esta infraestrutura. O KPI de falhas passa também a incluir comunicações `partially_sent`.

## 3.4. Submissão de candidatura

A validação final já era executada dentro de uma transação, sobre a candidatura bloqueada com `lockForUpdate()`. Faltava apenas incluir o estado de verificação do email nessa mesma validação autoritativa.

## 3.5. Visitas candidate

As sete rotas candidate de visitas estavam ativas e os links surgiam na navegação e na seleção de habitações. O modelo, serviços, dados e backoffice são preservados; apenas a experiência candidate é desligada por uma feature flag fail-closed.

## 3.6. Apoio

O módulo de tickets estava disponível desde o rascunho da candidatura. A base já contém fontes autoritativas suficientes para decidir o acesso pós-entrega:

```text
TenantTransition.status = completed
Contract.status = active
Contract.activated_at preenchido
KeyHandoverAppointment.status = completed
KeyHandoverAppointment.completed_at preenchido
TenantProfile.status = active
TenantProfile.activated_at preenchido
```

---

# 4. Arquitetura entregue

## 4.1. Política de comunicação processual

Foi criado:

```text
ProceduralNotificationPolicy
```

Comportamento:

- eventos conhecidos de `OfficialNotificationType` são classificados pelo domínio, mesmo que metadata configurável tente despromovê-los;
- eventos oficiais desconhecidos falham fechado e exigem email;
- visitas, reservas públicas, tickets de apoio, marketing e comunicações comerciais permanecem não processuais;
- `OfficialNotificationType::Other`, quando criado como notificação oficial, exige email por defeito.

## 4.2. Proteção das regras obrigatórias

Foi criado:

```text
ProceduralNotificationRuleGuard
```

Uma regra oficial obrigatória de email ou in-app não pode:

- ser desativada;
- mudar de evento;
- mudar de canal;
- trocar silenciosamente de template;
- ficar marcada como inativa por update genérico.

O dispatcher continua a aplicar defesa em profundidade: se existir apenas um dos canais obrigatórios, cria o canal em falta.

## 4.3. Email oficial e destino autoritativo

Para comunicação processual:

```text
origem do destino = users.email
preferência alternativa = ignorada
allow_email = ignorado
email verificado = obrigatório para candidate
```

As preferências legacy, quando temporariamente reativadas, são normalizadas para:

```text
allow_in_app = true
allow_email = true
email_for_notifications = users.email
revoked_at = null
```

## 4.4. Job de entrega

Foi criado:

```text
DeliverProceduralEmail
```

Características:

- `ShouldQueue`;
- unicidade até ao início do processamento;
- cinco tentativas;
- backoff progressivo;
- janela máxima de retry;
- revalidação do Município no consumo da fila;
- execução idempotente sobre estados terminais;
- marcação final como falhada após esgotar tentativas;
- auditoria sem payload, endereço de email ou conteúdo documental;
- erros técnicos sanitizados na base de dados e detalhe enviado apenas para logging seguro.

Fila predefinida:

```text
communications
```

## 4.5. Submissão com email verificado

`ApplicationValidationService` passa a carregar a relação `user` e inclui o check `email` no readiness e na validação transacional da submissão.

A candidatura permanece em rascunho quando:

- o formato do email é inválido;
- `email_verified_at` está vazio.

## 4.6. Feature flags candidate

Foram introduzidas:

```text
MVHAB_CANDIDATE_NOTIFICATION_PREFERENCES_ENABLED=false
MVHAB_LEGACY_CANDIDATE_VISITS_ENABLED=false
MVHAB_TENANT_SUPPORT_ENABLED=true
MVHAB_PROCEDURAL_EMAIL_QUEUE=communications
```

As flags legacy falham fechado. Uma chave desconhecida devolve 404.

## 4.7. Apoio pós-entrega

Foi criado:

```text
TenantSupportEligibilityService
```

A verificação é reutilizada em:

- middleware das rotas;
- navegação;
- `SupportTicketPolicy`;
- `SupportTicketMessagePolicy`;
- `SupportTicketAttachmentPolicy`;
- `SupportTicketService`;
- `SupportTicketMessageService`.

Assim, uma chamada direta ao Service, uma rota conhecida ou uma tentativa de contornar a navegação não desbloqueiam o módulo.

Mensagens internas históricas continuam permitidas ao backoffice. Uma mensagem visível ao candidato é recusada enquanto o ciclo pós-entrega não estiver completo.

## 4.8. Preservação legacy

Mantêm-se:

- tabelas e colunas de preferências;
- controllers, services e views das visitas candidate;
- registos históricos de visitas;
- tickets e anexos históricos;
- gestão backoffice;
- vistorias e inspeções técnicas;
- rotas com os mesmos nomes, protegidas por middleware adicional.

O catálogo de rotas deve permanecer em 1170.

---

# 5. Ficheiros abrangidos

## Produção

```text
app/Enums/CandidateExperienceFeature.php
app/Enums/OfficialNotificationType.php
app/Http/Controllers/Backoffice/NotificationCenterController.php
app/Http/Controllers/Candidate/ProcessDashboardController.php
app/Http/Middleware/EnsureCandidateExperienceFeatureIsEnabled.php
app/Http/Middleware/EnsureTenantSupportIsAvailable.php
app/Http/Requests/Concerns/ValidatesAdhesionRegistration.php
app/Jobs/DeliverProceduralEmail.php
app/Models/CommunicationLog.php
app/Policies/SupportTicketAttachmentPolicy.php
app/Policies/SupportTicketMessagePolicy.php
app/Policies/SupportTicketPolicy.php
app/Services/Applications/ApplicationValidationService.php
app/Services/CandidateExperience/CandidateNavigationService.php
app/Services/CandidateExperience/TenantSupportEligibilityService.php
app/Services/Notifications/Channels/EmailChannelService.php
app/Services/Notifications/CommunicationDeliveryService.php
app/Services/Notifications/NotificationEventDispatcher.php
app/Services/Notifications/NotificationEventRuleService.php
app/Services/Notifications/NotificationPreferenceService.php
app/Services/Notifications/OfficialNotificationService.php
app/Services/Notifications/ProceduralEmailDeliveryService.php
app/Services/Notifications/ProceduralNotificationPolicy.php
app/Services/Notifications/ProceduralNotificationRuleGuard.php
app/Services/ProcessTracking/ProcessTimelineBuilder.php
app/Services/Support/SupportTicketMessageService.php
app/Services/Support/SupportTicketService.php
app/Services/Visits/VisitNotificationService.php
bootstrap/app.php
config/mvhab.php
database/seeders/NotificationTemplateSeeder.php
resources/views/candidate/contextual-faq/index.blade.php
resources/views/candidate/housing-preferences/edit.blade.php
resources/views/candidate/registration/partials/form.blade.php
routes/web.php
```

## Testes

```text
tests/Feature/Candidate/CandidateNavigationEngineTest.php
tests/Feature/Candidate/SupportTicketFlowTest.php
tests/Feature/QA35VisitsCandidateSupportTest.php
tests/Feature/Sprint16CommunicationsTest.php
tests/Feature/Sprint22CandidateSupportTest.php
tests/Feature/Sprint52ACandidateExperienceTest.php
tests/Feature/Sprint8ApplicationSubmissionTest.php
tests/Feature/Workflows/VisitsSupportWorkTaskIntegrationTest.php
tests/Support/CreatesTenantSupportEligibility.php
tests/Unit/Notifications/ProceduralNotificationPolicyTest.php
```

## Base de dados

```text
Migrations novas: 0
Migrations alteradas: 0
Alterações destrutivas: 0
```

---

# 6. Segurança, RGPD e acessibilidade

## Segurança

- destino de email obtido no servidor;
- nenhum endereço alternativo controlado pelo formulário governa comunicações oficiais;
- middleware, Policy e Service aplicam a mesma fronteira de apoio;
- Jobs revalidam Município antes do envio;
- sem bypass por role administrativa;
- sem alteração de route names;
- sem remoção de dados históricos;
- segredos fora do patch e de `.env.example`.

## RGPD

- erros persistidos são sanitizados;
- auditoria guarda IDs técnicos, estados e classe da exceção, não o conteúdo da mensagem nem o endereço do destinatário;
- preferências comerciais continuam separáveis no futuro;
- os dados legacy são preservados até existir política de retenção e migração aprovada;
- o apoio só expõe dados quando existe relação locatícia ativa e entrega concluída.

## Acessibilidade

- o formulário de registo substitui checkboxes ambíguas por um alerta informativo sem interação;
- a navegação deixa de expor ações indisponíveis;
- as rotas protegidas permanecem acessíveis por teclado quando as flags forem explicitamente ativadas;
- os testes de UX existentes devem continuar obrigatórios.

---

# 7. Gates de deploy

O repositório pode ser aprovado após os testes. A entrada em produção fica condicionada a:

```text
QUEUE_CONNECTION != sync
MAIL_MAILER != log
MAIL_FROM_ADDRESS válido
worker ativo para communications,default
failed_jobs configurada e monitorizada
scheduler/health checks operacionais
config cache reconstruída
```

Exemplo de worker:

```bash
php artisan queue:work \
  --queue=communications,default \
  --tries=5 \
  --timeout=120 \
  --sleep=3 \
  --max-time=3600
```

Em produção, o worker deve ser gerido por systemd, Supervisor, Horizon ou mecanismo equivalente, com reinício e monitorização.

---

# 8. Critérios de aceitação da Sprint 52A

| Critério | Cobertura |
|---|---|
| Candidato não pode desativar email processual | preferências ocultas + normalização no Service + dispatcher independente |
| Cópia no centro de notificações | canal in-app obrigatório e defesa em profundidade |
| Email válido e verificado na submissão | check dentro da transação de submissão |
| Retentativas automáticas | Job, backoff e `retryUntil()` |
| Falhas auditadas | attempt, delivery, audit trail e evento final de retries |
| Falhas visíveis no backoffice | estado failed/partially_sent incluído no KPI |
| Visitas ausentes do portal candidate | navegação/CTA removidos e rotas com flag desativada |
| Apoio ausente antes da entrega | middleware + Policy + Service + navegação |
| Apoio disponível ao inquilino elegível | verificação conjunta dos quatro marcos do ciclo |
| Código e dados legacy preservados | sem drops, sem renomear rotas, sem eliminar módulos |

---

# 9. Validação do artefacto entregue

Executada no ambiente de geração:

```text
PHP lint dos ficheiros PHP alterados: PASS
Pint 1.29.1 nos ficheiros PHP alterados: PASS
PHPStan nível 8 nos ficheiros de produção alterados: PASS — 0 erros
Patch git apply --check sobre a base auditada: PASS
Git diff --check: PASS
Integridade ZIP: PASS
```

O PHPStan foi executado num repositório híbrido de validação, usando o `vendor` compatível do artefacto integral anterior e sobrepondo os ficheiros atuais do ZIP de auditoria. Não substitui a suite global no repositório real, mas confirma os contratos estáticos dos ficheiros de produção entregues.

Não foi possível executar PHPUnit porque o runtime do sandbox não possui as extensões PHP `dom`, `mbstring`, `xml` e `xmlwriter`. O build Vite também não pôde ser concluído porque o `node_modules` disponível foi criado em macOS e não contém o binding nativo Linux do Rolldown. O script fornecido executa estes gates no repositório real e interrompe imediatamente perante qualquer falha.

---

# 10. Aplicação e rollback

## Aplicação

```bash
unzip mvhab-sprint-52a-solution.zip -d /tmp/mvhab-sprint-52a
cd /caminho/para/MV-HAB
bash /tmp/mvhab-sprint-52a/mvhab-sprint-52a-apply.sh
```

O script:

1. valida branch, HEAD e hashes dos ficheiros auditados;
2. trata apenas o diff conhecido de `.env.example`;
3. cria branch de backup;
4. cria a branch da Sprint 52A;
5. aplica o patch;
6. configura flags locais sem versionar segredos;
7. executa lint, testes direcionados, suite completa, UX, Pint, PHPStan, Composer e Vite;
8. confirma que o catálogo de rotas não mudou;
9. deixa o commit ao utilizador.

## Rollback antes de commit

```bash
git reset --hard <branch-de-backup>
```

## Rollback depois de commit

```bash
git revert <commit-da-sprint-52a>
php artisan optimize:clear
```

Não existe rollback de migration nesta sprint.

---

# 11. Trabalho fora do âmbito

Não foram implementados nesta entrega:

- ordenação integral dos fogos — Sprint 52B;
- entidade `PublicVisitBooking` e casa aberta guest — Sprint 52C;
- testes end-to-end globais e consolidação municipal — Sprint 52D;
- integração com fornecedor real de email;
- CAPTCHA/Turnstile;
- política definitiva de retenção da futura reserva pública.

---

# 12. Conclusão

A Sprint 52A pode avançar sem breaking changes e sem nova migration. A implementação transforma email e in-app em garantias do procedimento, fecha os acessos candidate às visitas legacy, condiciona o apoio ao ciclo locatício real e preserva toda a base histórica.

A classificação correta é:

```text
REPOSITORY_PASS_DEPLOYMENT_GATED
```

O gate não é de código. É operacional: queue assíncrona, mailer real, worker monitorizado, rotação da credencial demo exposta e execução integral dos quality gates no repositório real.

---

## Correção de compatibilidade com o cenário municipal demo

O modo municipal demo mantém a cobertura obrigatória por **área pessoal e
email**, mas a entrega de email é registada com o estado `simulated`. Não é
colocado qualquer job na fila e não existe contacto com um fornecedor externo.

A simulação é ativada quando
`mvhab.municipal_application_demo.enabled=true` ou, explicitamente, através de
`MVHAB_PROCEDURAL_EMAIL_SIMULATE=true`. Fora desses contextos, a entrega
processual continua obrigatoriamente enfileirada, com retries e auditoria.

Esta fronteira preserva simultaneamente:

- a regra processual de dois canais;
- a segurança da demonstração com dados fictícios;
- a idempotência dos seeders;
- a ausência de efeitos administrativos e contactos externos.

