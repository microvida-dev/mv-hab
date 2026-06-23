# Sprint 4 — Registo de Adesão e Área Pessoal

## Prioridade de desenvolvimento

Esta sprint pertence à prioridade funcional:

```text
1. Candidatura
```

A Sprint 4 cria a base autenticada do candidato e o **Registo de Adesão**, que será o ponto de entrada obrigatório antes da submissão formal de candidaturas.

Esta sprint deve permitir ao cidadão criar, preencher, guardar, finalizar, cancelar ou remover o seu registo de adesão, preparando a plataforma para as sprints seguintes:

```text
Sprint 5 — Agregado familiar, rendimentos e situação habitacional
Sprint 6 — Gestão documental avançada
Sprint 8 — Candidaturas e submissão formal
```

---

# Objetivo da Sprint

Implementar a área pessoal do candidato e o módulo de Registo de Adesão, permitindo que o utilizador autenticado com perfil `candidate` possa:

- Aceder ao painel do candidato;
- Consultar o estado do seu registo;
- Criar registo de adesão;
- Preencher dados pessoais base;
- Guardar rascunho;
- Finalizar o registo quando os campos mínimos estiverem completos;
- Cancelar o registo quando aplicável;
- Remover o registo se ainda não existirem candidaturas associadas;
- Consultar histórico simples de alterações de estado;
- Ver orientações claras sobre os próximos passos;
- Receber feedback visual sobre dados em falta.

Esta sprint não deve implementar ainda candidatura formal, elegibilidade, classificação ou upload documental avançado.

---

# Instrução operacional para Codex

Executa apenas esta Sprint 4.

Não avances para Sprint 5, Sprint 6, Sprint 8 ou qualquer sprint futura sem validação explícita.

Ignora qualquer verificação de branch Git.

Antes de alterar código, lê primeiro, se existirem:

```text
docs/architecture/technical-architecture.md
docs/architecture/data-model-overview.md
docs/product/product-vision.md
docs/product/functional-requirements.md
docs/product/user-roles.md
docs/product/process-workflows.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/backlog/roadmap.md
docs/backlog/sprint-0-foundation.md
docs/backlog/sprint-1-foundation.md
docs/backlog/sprint-2-foundation.md
docs/backlog/sprint-3-portal-publico-programas.md
docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Se algum ficheiro de documentação não existir, continua a execução se for tecnicamente possível, mas documenta a ausência na resposta final.

Antes de implementar, confirma que existem ou identifica alternativas para:

```text
Sistema de autenticação
Role candidate
Middleware de permissões
Layout candidate-layout ou equivalente
Componentes de UI da Sprint 2
Rotas públicas da Sprint 3
Models User e roles/permissões
Auditoria base, se implementada na Sprint 1
```

Não duplicar estruturas existentes.

Não reimplementar autenticação se já existir.

Não alterar o fluxo de login/registo existente sem necessidade.

---

# Âmbito desta Sprint

## Incluído

Implementar:

```text
Área pessoal do candidato
Dashboard do candidato
Registo de adesão
Estados do registo de adesão
Formulário de dados pessoais base
Consentimentos base
Preferências de notificação
Guardar rascunho
Finalizar registo
Cancelar registo
Remover registo, se não houver candidaturas
Histórico simples de estado
Checklist de preenchimento
Proteção por role candidate
Testes mínimos
Atualização documental
```

## Fora de âmbito

Não implementar nesta sprint:

```text
Gestão completa de agregado familiar
Gestão completa de rendimentos
Situação habitacional detalhada
Upload documental avançado
Submissão formal de candidatura
Motor de elegibilidade
Simulador real de elegibilidade
Matriz de classificação
Ranking
Listas provisórias
Reclamações
Audiência de interessados
Lista definitiva
Atribuição
Contratos
Pagamentos
Manutenção
Notificações reais por email/SMS
Integração com Autenticação.GOV
Integração com serviços externos
```

Nesta sprint podem existir placeholders claros para módulos futuros.

---

# Conceito funcional do Registo de Adesão

O Registo de Adesão representa a manifestação de interesse do cidadão em aceder aos programas de habitação do município.

Deve anteceder qualquer candidatura formal.

A estrutura completa futura do Registo de Adesão será composta por quatro áreas principais:

```text
1. Utilizador
2. Agregado
3. Rendimentos
4. Habitação Atual
```

Nesta Sprint 4 deve ser implementada a fundação do registo, com foco na área:

```text
1. Utilizador
```

As restantes áreas podem aparecer como passos futuros ou placeholders bloqueados, a implementar na Sprint 5.

---

# Estados do Registo de Adesão

Criar estados formais para o registo:

```text
incomplete
registered
cancelled
removed
blocked
expired
```

## Significado dos estados

### incomplete

Registo iniciado, mas ainda não finalizado.

### registered

Registo finalizado com sucesso e apto a permitir futuras candidaturas, desde que os restantes requisitos estejam completos.

### cancelled

Registo cancelado pelo utilizador ou pelos serviços, mantendo histórico.

### removed

Registo removido pelo titular dos dados, quando ainda não existem candidaturas associadas.

### blocked

Registo bloqueado por existência de candidatura submetida ou outra regra administrativa futura.

### expired

Registo caducado por falta de renovação ou atualização dentro do prazo previsto.

---

# Regras de estado

## Registo novo

Quando um candidato inicia o registo:

```text
status = incomplete
```

## Finalização

O registo só pode passar para:

```text
registered
```

quando os campos obrigatórios mínimos estiverem preenchidos.

## Cancelamento

O candidato pode cancelar o registo se:

```text
status in incomplete, registered
```

e se a lógica futura permitir.

Nesta sprint, se não existir candidatura formal, permitir cancelamento e registar histórico.

## Remoção

O candidato pode remover o registo apenas se:

```text
não existirem candidaturas associadas
```

Como candidaturas formais ainda não existem nesta sprint, preparar a regra para verificar relação futura.

Se a tabela de candidaturas já existir, usar essa relação.

Se não existir, criar método preparado para futura verificação sem quebrar a aplicação.

## Bloqueio

O estado `blocked` deve existir, mas não precisa de ser aplicado automaticamente nesta sprint.

## Expiração

O estado `expired` deve existir, mas não precisa de job automático nesta sprint.

---

# Modelo de dados

## Criar entidade `AdhesionRegistration`

Tabela recomendada:

```text
adhesion_registrations
```

## Campos mínimos

```text
id
user_id
status

full_name
email
phone
mobile_phone
document_type
document_number
document_valid_until
nif
birth_date
nationality

address
postal_code
city
parish
municipality

wants_email_notifications
wants_sms_notifications
wants_postal_notifications

accepts_terms
accepts_data_processing
accepted_terms_at
accepted_data_processing_at

submitted_at
cancelled_at
removed_at
blocked_at
expired_at

created_at
updated_at
deleted_at
```

## Notas

- `user_id` obrigatório e único, salvo se a arquitetura permitir múltiplos registos históricos.
- `status` obrigatório.
- `full_name` obrigatório para finalizar.
- `email` obrigatório para finalizar.
- `nif` obrigatório para finalizar.
- `birth_date` obrigatório para finalizar.
- `accepts_terms` obrigatório para finalizar.
- `accepts_data_processing` obrigatório para finalizar.
- Usar soft deletes.
- Não guardar documentos nesta sprint.
- Não guardar ficheiros nesta sprint.
- Não criar integração com Cartão de Cidadão ou Chave Móvel Digital nesta sprint.

---

# Relações

## User

Adicionar relação:

```text
User hasOne AdhesionRegistration
```

ou, se for mais adequado ao histórico:

```text
User hasMany AdhesionRegistration
User hasOne currentAdhesionRegistration
```

Preferência nesta sprint:

```text
User hasOne AdhesionRegistration
```

## AdhesionRegistration

Adicionar relação:

```text
AdhesionRegistration belongsTo User
```

Preparar método futuro:

```text
hasApplications()
```

Se já existir model de candidaturas, verificar relação real.

Se não existir, devolver `false` com nota técnica clara ou preparar sem lógica fictícia perigosa.

---

# Enum de estado

Criar enum, se a versão do PHP permitir:

```text
App\Enums\AdhesionRegistrationStatus
```

Valores:

```text
incomplete
registered
cancelled
removed
blocked
expired
```

Se o projeto não suportar enums PHP, criar classe de constantes equivalente.

---

# Model

Criar model:

```text
App\Models\AdhesionRegistration
```

## Requisitos do model

- Definir fillable/guarded de forma segura.
- Definir casts:
    - booleans;
    - dates;
    - datetime;
    - enum de status, se aplicável.

- Usar SoftDeletes.
- Criar scopes úteis:
    - incomplete;
    - registered;
    - cancelled;
    - active;
    - forUser.

- Criar métodos:
    - canBeFinalized();
    - canBeCancelled();
    - canBeRemoved();
    - markAsRegistered();
    - markAsCancelled();
    - markAsRemoved();
    - completionPercentage();
    - missingRequiredFields();

## Campos obrigatórios para finalizar

Nesta sprint, considerar obrigatórios:

```text
full_name
email
nif
birth_date
address
postal_code
city
municipality
accepts_terms
accepts_data_processing
```

O telefone deve ser recomendado, mas não obrigatório, salvo se a arquitetura decidir o contrário.

---

# Histórico de estado

Criar tabela simples:

```text
adhesion_registration_status_histories
```

## Campos mínimos

```text
id
adhesion_registration_id
from_status
to_status
changed_by
reason
created_at
```

## Regras

- Criar registo sempre que o estado muda.
- `changed_by` pode ser o utilizador autenticado.
- `reason` opcional.
- Não guardar dados sensíveis no campo `reason`.

## Relações

```text
AdhesionRegistration hasMany AdhesionRegistrationStatusHistory
AdhesionRegistrationStatusHistory belongsTo AdhesionRegistration
AdhesionRegistrationStatusHistory belongsTo User as changedBy
```

---

# Consentimentos

Nesta sprint, implementar consentimentos simples no próprio registo:

```text
accepts_terms
accepts_data_processing
accepted_terms_at
accepted_data_processing_at
```

## Regras

- Para finalizar o registo, `accepts_terms` deve ser verdadeiro.
- Para finalizar o registo, `accepts_data_processing` deve ser verdadeiro.
- Guardar timestamp de aceitação.
- Se o utilizador retirar consentimento, não eliminar dados automaticamente nesta sprint.
- Documentar que gestão RGPD avançada fica para Sprint 18.

---

# Controllers

Criar ou atualizar controllers em namespace adequado.

Preferência:

```text
App\Http\Controllers\Candidate
```

Controllers recomendados:

```text
Candidate\DashboardController
Candidate\AdhesionRegistrationController
Candidate\ProfileController
```

## DashboardController

Responsável por:

- Mostrar painel do candidato;
- Mostrar estado do registo;
- Mostrar percentagem de preenchimento;
- Mostrar próximos passos;
- Mostrar placeholders para candidaturas, documentos e notificações.

## AdhesionRegistrationController

Ações recomendadas:

```text
show
create
store
edit
update
finalize
cancel
remove
```

Rotas podem ser ajustadas ao padrão Laravel.

## ProfileController

Se já existir profile controller do Breeze ou equivalente, não duplicar.

Apenas reutilizar ou apontar para a área de perfil existente.

---

# Form Requests

Criar Form Requests:

```text
StoreAdhesionRegistrationRequest
UpdateAdhesionRegistrationRequest
FinalizeAdhesionRegistrationRequest
CancelAdhesionRegistrationRequest
RemoveAdhesionRegistrationRequest
```

## Validações mínimas

### Store/Update

```text
full_name nullable|string|max:255
email nullable|email|max:255
phone nullable|string|max:50
mobile_phone nullable|string|max:50
document_type nullable|string|max:50
document_number nullable|string|max:100
document_valid_until nullable|date
nif nullable|string|max:20
birth_date nullable|date|before:today
nationality nullable|string|max:100
address nullable|string|max:255
postal_code nullable|string|max:20
city nullable|string|max:100
parish nullable|string|max:100
municipality nullable|string|max:100
wants_email_notifications boolean
wants_sms_notifications boolean
wants_postal_notifications boolean
accepts_terms boolean
accepts_data_processing boolean
```

### Finalize

Exigir:

```text
full_name required
email required|email
nif required
birth_date required|date|before:today
address required
postal_code required
city required
municipality required
accepts_terms accepted
accepts_data_processing accepted
```

## Regra de idade

Nesta sprint, validar idade mínima de 18 anos apenas como regra base do Registo de Adesão.

Se não for implementada em Form Request, implementar no model/service com teste.

---

# Services

Criar service para lógica de negócio:

```text
App\Services\Candidate\AdhesionRegistrationService
```

## Responsabilidades

```text
Criar registo inicial
Atualizar registo
Finalizar registo
Cancelar registo
Remover registo
Calcular campos em falta
Calcular percentagem de preenchimento
Registar histórico de estado
Disparar auditoria se existir
```

Evitar lógica pesada no controller.

---

# Policies e autorização

Criar policy:

```text
AdhesionRegistrationPolicy
```

## Regras

- O candidato só pode ver o seu próprio registo.
- O candidato só pode editar o seu próprio registo.
- O candidato só pode finalizar o seu próprio registo.
- O candidato só pode cancelar o seu próprio registo.
- O candidato só pode remover o seu próprio registo se não existirem candidaturas associadas.
- Admin pode consultar para suporte, se a arquitetura permitir.
- Técnicos municipais não devem editar diretamente nesta sprint, salvo se já existir regra administrativa clara.

## Middleware

Rotas da área do candidato devem exigir:

```text
auth
role:candidate
```

ou mecanismo equivalente.

---

# Rotas

Criar rotas protegidas para a área do candidato.

Preferência em português:

```text
GET /area-candidato
GET /area-candidato/registo
GET /area-candidato/registo/criar
POST /area-candidato/registo
GET /area-candidato/registo/editar
PUT/PATCH /area-candidato/registo
POST /area-candidato/registo/finalizar
POST /area-candidato/registo/cancelar
DELETE /area-candidato/registo/remover

GET /area-candidato/perfil
GET /area-candidato/candidaturas
GET /area-candidato/documentos
GET /area-candidato/notificacoes
```

Nomes recomendados:

```text
candidate.dashboard
candidate.registration.show
candidate.registration.create
candidate.registration.store
candidate.registration.edit
candidate.registration.update
candidate.registration.finalize
candidate.registration.cancel
candidate.registration.remove
candidate.profile
candidate.applications.index
candidate.documents.index
candidate.notifications.index
```

Se o projeto já usa rotas em inglês, manter consistência.

---

# Views / Páginas

Se o projeto usa Blade, criar ou atualizar:

```text
resources/views/candidate/dashboard.blade.php

resources/views/candidate/registration/show.blade.php
resources/views/candidate/registration/create.blade.php
resources/views/candidate/registration/edit.blade.php

resources/views/candidate/profile.blade.php
resources/views/candidate/applications/index.blade.php
resources/views/candidate/documents/index.blade.php
resources/views/candidate/notifications/index.blade.php
```

Se usa Inertia/Vue/React, criar equivalentes.

---

# Dashboard do candidato

## Conteúdo obrigatório

Mostrar:

```text
Título: Área do Candidato
Estado do Registo de Adesão
Percentagem de preenchimento
Campos em falta
Próximos passos
Cards para:
- O meu registo
- As minhas candidaturas
- Os meus documentos
- Notificações
- Perfil
```

## Se não existir registo

Mostrar CTA:

```text
Iniciar Registo de Adesão
```

## Se existir registo incompleto

Mostrar CTA:

```text
Continuar preenchimento
```

## Se existir registo registado

Mostrar mensagem:

```text
O seu Registo de Adesão encontra-se finalizado.
Quando existirem concursos disponíveis, poderá iniciar uma candidatura.
```

---

# Formulário de Registo de Adesão

## Secções obrigatórias

```text
Dados pessoais
Identificação
Contactos
Morada
Preferências de notificação
Consentimentos
```

## Dados pessoais

Campos:

```text
Nome completo
Data de nascimento
Nacionalidade
```

## Identificação

Campos:

```text
Tipo de documento
Número do documento
Validade do documento
NIF
```

## Contactos

Campos:

```text
Email
Telefone
Telemóvel
```

## Morada

Campos:

```text
Morada
Código postal
Localidade
Freguesia
Município
```

## Preferências de notificação

Campos:

```text
Pretendo receber notificações por email
Pretendo receber notificações por SMS
Pretendo receber notificações por via postal
```

## Consentimentos

Campos:

```text
Declaro que li e aceito os termos de utilização.
Autorizo o tratamento dos meus dados pessoais para efeitos de gestão do Registo de Adesão e futuras candidaturas a programas municipais de habitação.
```

---

# UX obrigatória

## Regras

- Formulário dividido por secções claras.
- Mostrar campos obrigatórios.
- Mostrar mensagens de erro junto ao campo.
- Botão “Guardar rascunho”.
- Botão “Finalizar Registo” separado.
- Mostrar aviso antes de finalizar.
- Mostrar aviso antes de cancelar.
- Mostrar aviso forte antes de remover.
- Mostrar lista de campos em falta.
- Mostrar estado no topo da página.
- Mostrar data de última atualização.
- Mostrar data de finalização, se existir.

## Copy base

### Estado incompleto

```text
O seu Registo de Adesão ainda não está completo. Preencha os campos obrigatórios para poder finalizar.
```

### Estado registado

```text
O seu Registo de Adesão foi finalizado com sucesso. Poderá utilizar estes dados para futuras candidaturas.
```

### Cancelar

```text
Ao cancelar o Registo de Adesão, este deixará de estar ativo. Poderá ser necessário criar ou atualizar novo registo para futuras candidaturas.
```

### Remover

```text
A remoção do Registo de Adesão elimina o registo da sua área pessoal, quando não existam candidaturas associadas. Esta ação deve ser usada apenas se pretender deixar de manter o registo ativo.
```

---

# Placeholders permitidos

Criar placeholders para:

```text
As minhas candidaturas
Os meus documentos
Notificações
```

## Placeholder candidaturas

```text
A submissão e acompanhamento de candidaturas será disponibilizada numa fase seguinte da plataforma.
```

## Placeholder documentos

```text
A submissão de documentos será disponibilizada quando existir uma candidatura ou pedido documental associado.
```

## Placeholder notificações

```text
As notificações do município ficarão disponíveis nesta área durante a gestão dos processos.
```

Não criar funcionalidades falsas.

---

# Auditoria

Se a Sprint 1 implementou auditoria, auditar:

```text
Criação de Registo de Adesão
Atualização de Registo de Adesão
Finalização de Registo de Adesão
Cancelamento de Registo de Adesão
Remoção de Registo de Adesão
```

Se a auditoria ainda não existir, não implementar sistema paralelo duplicado.
Documentar pendência.

Não guardar dados sensíveis desnecessários nos logs.

---

# RGPD

## Regras nesta sprint

- Guardar consentimento de tratamento de dados.
- Guardar timestamp de consentimento.
- Informar finalidade de tratamento.
- Permitir remoção apenas quando não existirem candidaturas associadas.
- Não eliminar fisicamente dados críticos se a arquitetura exigir retenção administrativa; nesse caso usar `removed_at` e estado `removed`.
- Usar soft delete.
- Não expor dados do candidato a outros candidatos.
- Não criar exportação RGPD nesta sprint.

## Texto informativo mínimo

Incluir junto aos consentimentos:

```text
Os dados recolhidos destinam-se à gestão do Registo de Adesão e à preparação de futuras candidaturas a programas municipais de habitação. O tratamento será realizado nos termos da legislação aplicável de proteção de dados e das finalidades definidas pelo município.
```

---

# Segurança

## Regras obrigatórias

- Guest não acede à área do candidato.
- Utilizador sem role `candidate` não acede à área do candidato.
- Candidato só acede ao seu próprio registo.
- Não expor IDs de outros utilizadores.
- Não permitir alteração de `user_id` via formulário.
- Não permitir alteração direta de `status` via formulário comum.
- Mudanças de estado devem passar pelo service.
- Não aceitar campos inesperados por mass assignment.
- Não guardar passwords ou tokens neste módulo.

---

# Seeders e factories

Criar factory:

```text
AdhesionRegistrationFactory
```

Criar seeder opcional:

```text
AdhesionRegistrationSeeder
```

Dados demo permitidos:

```text
1 candidato com registo incompleto
1 candidato com registo registado
1 candidato sem registo
```

Usar apenas dados fictícios:

```text
Maria Demo
João Exemplo
ana.candidata@example.test
```

Não usar dados pessoais reais.

---

# Testes obrigatórios

Criar ou atualizar testes para cobrir:

## Acesso

```text
guest_cannot_access_candidate_dashboard
candidate_can_access_candidate_dashboard
non_candidate_cannot_access_candidate_dashboard
candidate_can_access_registration_page
candidate_cannot_access_another_candidate_registration
```

## Criação e atualização

```text
candidate_can_start_adhesion_registration
candidate_can_save_incomplete_registration
candidate_can_update_own_registration
registration_starts_as_incomplete
user_cannot_mass_assign_user_id
user_cannot_mass_assign_status
```

## Finalização

```text
candidate_cannot_finalize_registration_with_missing_required_fields
candidate_can_finalize_registration_with_required_fields
finalized_registration_status_is_registered
finalized_registration_has_submitted_at
finalized_registration_stores_consent_timestamps
candidate_must_be_at_least_18_to_finalize_registration
```

## Cancelamento e remoção

```text
candidate_can_cancel_own_registration
cancelled_registration_status_is_cancelled
candidate_can_remove_registration_without_applications
removed_registration_status_is_removed
candidate_cannot_remove_registration_with_applications_when_relation_exists
```

## Histórico

```text
registration_status_change_creates_history_entry
registration_history_records_from_and_to_status
```

## Segurança/RGPD

```text
candidate_registration_requires_data_processing_consent_to_finalize
candidate_registration_requires_terms_acceptance_to_finalize
candidate_data_is_not_visible_to_other_candidates
```

## Auditoria, se existir

```text
creating_registration_generates_audit_log
updating_registration_generates_audit_log
finalizing_registration_generates_audit_log
```

Se auditoria não existir, documentar teste como pendente em vez de criar teste quebrado.

---

# Comandos de validação

No final, executar:

```bash
php artisan route:list
php artisan test
```

Se existirem migrations novas:

```bash
php artisan migrate
```

Se o projeto usar build frontend:

```bash
npm run build
```

Se o projeto usar Pint:

```bash
./vendor/bin/pint
```

Se algum comando falhar, documentar:

```text
Comando executado
Erro obtido
Causa provável
Impacto
Correção recomendada
```

Não afirmar que comandos passaram se não foram executados.

---

# Atualização documental obrigatória

Atualizar, se existirem:

```text
docs/backlog/sprint-4-registo-adesao-area-pessoal.md
docs/backlog/roadmap.md
docs/product/functional-requirements.md
docs/product/process-workflows.md
docs/architecture/data-model-overview.md
docs/security/access-control-matrix.md
docs/security/rgpd-and-audit-strategy.md
docs/qa/acceptance-criteria.md
docs/qa/testing-strategy.md
```

Registar:

```text
O que foi implementado
Tabelas criadas
Models criados
Controllers criados
Requests criados
Policies criadas
Services criados
Views criadas
Rotas criadas
Testes criados
Comandos executados
Pendências para Sprint 5
```

---

# Critérios de aceitação da Sprint 4

A Sprint 4 está concluída quando:

```text
A área do candidato existe
A área do candidato está protegida por autenticação e role candidate
O dashboard do candidato mostra estado do registo
O candidato consegue iniciar Registo de Adesão
O candidato consegue guardar registo incompleto
O candidato consegue atualizar o seu próprio registo
O candidato não consegue ver registo de outro candidato
O candidato consegue finalizar registo com campos obrigatórios completos
O sistema impede finalização com campos obrigatórios em falta
O sistema impede finalização sem consentimentos obrigatórios
O sistema valida idade mínima de 18 anos
O estado passa corretamente para registered
O sistema regista submitted_at na finalização
O candidato consegue cancelar registo quando permitido
O candidato consegue remover registo quando não existem candidaturas associadas
O histórico de estado é criado
A interface mostra campos em falta
A interface mostra percentagem ou progresso de preenchimento
As páginas de candidaturas/documentos/notificações existem como placeholders
Não foi implementada candidatura formal
Não foi implementado upload documental avançado
Não foi implementado motor de elegibilidade
Testes mínimos foram criados
php artisan route:list executa sem erro
php artisan test executa sem erro ou falhas são documentadas
npm run build executa sem erro ou falhas são documentadas
Documentação foi atualizada
```

---

# Resposta final esperada do Codex

No final da execução, responder com:

```text
1. Resumo do que foi implementado na Sprint 4
2. Ficheiros criados
3. Ficheiros alterados
4. Migrations criadas
5. Models criados ou alterados
6. Enums criados
7. Controllers criados ou alterados
8. Form Requests criados
9. Policies criadas
10. Services criados
11. Views/páginas criadas ou alteradas
12. Rotas criadas
13. Seeders/factories criados ou alterados
14. Testes criados ou alterados
15. Resultado dos comandos executados
16. Problemas encontrados
17. Pendências
18. Confirmação de que não foram implementadas funcionalidades fora de âmbito
19. Recomendação objetiva para avançar ou não para Sprint 5
```

Não ocultar erros.

Não afirmar que algo foi testado se não foi realmente testado.

Não avançar para Sprint 5 sem validação explícita.

---

# Definition of Done

A Sprint 4 só está concluída quando o candidato tiver uma área pessoal funcional e um Registo de Adesão inicial, seguro, auditável e preparado para receber, na Sprint 5, os dados completos de agregado familiar, rendimentos e situação habitacional.

Fim da Sprint 4.

---

# Relatório de execução — 10/06/2026

## Estado

Sprint 4 concluída. A Sprint 5 não foi iniciada.

## Implementado

- Role `candidate` automática para novas contas.
- Middleware de separação entre candidatos e backoffice.
- Dashboard candidato, navegação própria e perfil reutilizado do Breeze.
- Registo de Adesão único por utilizador.
- Formulário seccionado para dados pessoais, identificação, contactos, morada, preferências e consentimentos.
- Rascunho, finalização, cancelamento e remoção.
- Estados formais, percentagem, campos em falta e histórico.
- Validação de idade mínima de 18 anos.
- Consentimentos com timestamps.
- Auditoria sem valores pessoais.
- Placeholders de candidaturas, documentos e notificações.
- Bloqueio da eliminação genérica da conta quando existe histórico.

## Ficheiros de domínio

```text
AdhesionRegistration
AdhesionRegistrationStatusHistory
AdhesionRegistrationStatus
AdhesionRegistrationPolicy
AdhesionRegistrationService
```

## Migration

```text
2026_06_10_020000_create_adhesion_registrations_table.php
```

Cria:

```text
adhesion_registrations
adhesion_registration_status_histories
```

## Rotas

Foram criadas 13 rotas sob `/area-candidato`, protegidas por `auth` e `role:candidate`.

## Testes

Criado `tests/Feature/Sprint4AdhesionRegistrationTest.php` e atualizado o teste de registo de conta.

Resultado global:

```text
48 testes
193 asserções
0 falhas
```

## Comandos e resultados

- `php artisan migrate --force`: primeira execução falhou por nome de foreign key MySQL superior a 64 caracteres.
- Inspeção confirmou que as duas tabelas parciais tinham zero linhas.
- As tabelas parciais foram removidas, a constraint recebeu nome explícito curto e a migration passou.
- `php artisan route:list`: 113 rotas.
- `php artisan test`: 48 testes e 193 asserções aprovados.
- `npm run build`: concluído com sucesso.
- `./vendor/bin/pint --test`: concluído com sucesso.
- PHPStan/Psalm: não instalado.
- Browser: dashboard e formulário renderizados sem erros; viewport 390×844 sem overflow horizontal.
- A conta candidata temporária usada na validação visual foi removida.

## Outras ocorrências

- A primeira tentativa de criar a conta temporária via Tinker falhou por expansão de `$user` pelo shell; o comando não alterou dados e foi repetido com escaping.
- Não foram introduzidos dados pessoais reais, passwords reais, tokens ou alterações ao `.env`.

## Pendências para Sprint 5

- Aprovar os campos do agregado familiar e respetiva minimização.
- Definir relação entre candidato, agregado e membros sem reutilização insegura do model CRM legado.
- Aprovar tipologias e períodos de rendimentos.
- Aprovar dados estritamente necessários da situação habitacional.
- Definir regras de edição após o registo estar `registered`.
- Manter a Sprint 5 dependente de validação explícita.
