# Sprint 44B — Atas Alcanena do Procedimento

## Resumo

A Sprint 44B evoluiu o módulo existente de Minutas/Atas do Procedimento para suportar atas específicas do Município de Alcanena, usando a infraestrutura já existente da plataforma:

- `ProcedureTemplate`
- `ProcedureMinute`
- `GeneratedProcedureDocument`

Não foi criado módulo paralelo, nem nova tabela, nem migration. Os dados adicionais da ata passam a ser guardados em `procedure_minutes.payload`, como snapshot processual privado/backoffice.

O objetivo funcional entregue é permitir que o backoffice gere atas do procedimento preenchidas automaticamente com dados reais do concurso, candidaturas, júri, habitações, listas, audiência prévia, reclamações, sorteios, desistências e decisões administrativas, mantendo revisão e aprovação por utilizadores autorizados.

## Âmbito

### Incluído

- Campos administrativos e manuais para geração de atas.
- Payload processual estruturado para atas Alcanena.
- Resolução de variáveis de template a partir do payload.
- UI de geração com seleção de concurso e candidatura.
- UI de consulta da ata com metadados, alerta RGPD e payload colapsável.
- Seeder idempotente com minutas Alcanena.
- Testes feature e unit para geração, aprovação, payload e seeder.
- Compatibilidade com o fluxo já coberto por `Sprint24BackofficeOperationalTest`.

### Excluído

- Novas tabelas ou migrations.
- Novo módulo de atas.
- Alterações a regras de negócio de candidaturas, listas, sorteios, elegibilidade, scoring ou contratos.
- Alterações a policies existentes.
- Exportação PDF/DOCX.
- Publicação pública de atas.
- Alterações ao `TemplateRenderingService`, que continua a escapar variáveis por segurança.

## Ficheiros Alterados

### Request

- `app/Http/Requests/GenerateProcedureMinuteRequest.php`

Adicionados campos:

- `meeting_time`
- `meeting_location`
- `municipal_registry_number`
- `municipal_process_number`
- `external_reference`
- `legal_basis`
- `deliberation_text`
- `observations`

A autorização existente foi preservada:

- `documents.create`

### Controller

- `app/Http/Controllers/Backoffice/ProcedureMinuteController.php`

Alterações:

- `index()` passou a carregar:
  - atas com `contest`, `application.user` e `template`;
  - últimos 100 concursos;
  - últimas 100 candidaturas com utilizador.
- `show()` passou a carregar relações necessárias para evitar N+1 na view.
- `Gate::authorize` foi preservado.

### Services

- `app/Services/ProcedureMinutes/ProcedureMinuteService.php`
- `app/Services/ProcedureMinutes/ProcedureMinutePayloadBuilder.php`
- `app/Services/ProcedureTemplates/TemplateVariableResolver.php`

#### ProcedureMinuteService

O fluxo de geração foi corrigido.

Antes:

1. carregava template;
2. resolvia variáveis diretamente de candidatura/concurso;
3. renderizava conteúdo;
4. construía payload mínimo;
5. gravava ata.

Agora:

1. carrega `ProcedureTemplate`;
2. constrói payload completo com `ProcedureMinutePayloadBuilder`;
3. resolve variáveis a partir do payload com `TemplateVariableResolver::forProcedureMinutePayload`;
4. renderiza conteúdo;
5. grava `ProcedureMinute` com `payload` e `content_snapshot`;
6. exporta HTML privado;
7. regista auditoria `AuditEvents::CREATE`.

Mantido:

- numeração `ATA-YYYY-000001`;
- estado inicial `ProcedureMinuteStatus::Generated`;
- exportação via `ProcedureMinuteExportService`;
- aprovação com `AuditEvents::APPROVE`;
- compatibilidade com templates antigos.

#### ProcedureMinutePayloadBuilder

Foi expandido de payload mínimo para snapshot processual estruturado.

Assinatura atual:

```php
public function build(array $data, ?User $actor = null): array
```

Blocos produzidos:

- `copy`
- `generated_at`
- `generated_by`
- `municipal`
- `meeting`
- `manual_fields`
- `program`
- `contest`
- `application`
- `deadlines`
- `jury`
- `housing_units`
- `applications`
- `provisional_lists`
- `definitive_lists`
- `hearings`
- `complaints`
- `lottery_draws`
- `withdrawals`
- `administrative_decisions`
- `summary`

Dados carregados com eager loading, evitando queries em views e reduzindo risco de N+1.

O builder é defensivo:

- aceita `contest_id`;
- aceita `application_id`;
- infere concurso pela candidatura quando aplicável;
- devolve arrays vazios quando não existem dados;
- não bloqueia atas em concursos incompletos;
- trunca textos longos em submissões, reclamações e decisões.

#### TemplateVariableResolver

Adicionado:

```php
public function forProcedureMinutePayload(array $payload, ?User $actor = null): array
```

Variáveis suportadas:

- `municipality_name`
- `municipal_department`
- `municipal_registry_number`
- `municipal_process_number`
- `external_reference`
- `meeting_date`
- `meeting_time`
- `meeting_location`
- `meeting_subject`
- `contest_title`
- `contest_code`
- `contest_status`
- `contest_applications_total`
- `contest_housing_units_total`
- `provisional_lists_total`
- `definitive_lists_total`
- `hearings_total`
- `complaints_total`
- `lottery_draws_total`
- `withdrawals_total`
- `jury_members`
- `housing_units_summary`
- `applications_summary`
- `provisional_list_summary`
- `definitive_list_summary`
- `hearing_summary`
- `complaint_summary`
- `lottery_summary`
- `withdrawals_summary`
- `legal_basis`
- `deliberation_text`
- `observations`
- `generated_at`

Compatibilidade preservada com placeholders antigos:

- `process_number`
- `application_number`
- `candidate_name`
- `submitted_at`
- `current_status`
- `ranking_position`
- `total_score`

Os summaries são texto plano para manter compatibilidade com o `TemplateRenderingService`, que escapa variáveis e rejeita placeholders desconhecidos.

### Views

- `resources/views/backoffice/procedure-minutes/index.blade.php`
- `resources/views/backoffice/procedure-minutes/show.blade.php`

#### Index

Melhorias:

- reformatado de Blade minificado para Blade legível;
- seleção de minuta por `<select>`;
- seleção de concurso por `<select>` com `code · title`;
- seleção opcional de candidatura por `<select>` com `application_number · user.name`;
- novos campos administrativos:
  - data da reunião;
  - hora;
  - local;
  - número de registo;
  - número de processo municipal;
  - referência externa;
  - enquadramento legal;
  - deliberação;
  - observações.
- tabela de atas geradas mantém paginação e links existentes.

#### Show

Melhorias:

- botão `Aprovar ata` deixa de aparecer quando a ata já está aprovada;
- download continua disponível quando `file_path` existe;
- metadados visíveis:
  - número;
  - estado;
  - concurso;
  - candidatura;
  - gerada em;
  - aprovada em.
- alerta RGPD sobre o payload interno;
- conteúdo da ata mantido;
- payload de suporte colocado dentro de `<details>`.

### Seeder

- `database/seeders/AlcanenaProcedureTemplateSeeder.php`

Criado seeder idempotente por `template_number`.

Templates criados:

- `ALC-ATA-01-SERIACAO-INICIAL`
- `ALC-ATA-02-RELATORIO-PRELIMINAR`
- `ALC-ATA-03-AUDIENCIA-PREVIA`
- `ALC-ATA-05-DELIBERACAO-SORTEIO`
- `ALC-ATA-06-SORTEIO-PUBLICO`
- `ALC-ATA-07-RELATORIO-FINAL`
- `ALC-ATA-08-REAPRECIACAO-DESISTENCIA`
- `ALC-ATA-09-NOVO-SORTEIO`
- `ALC-ATA-10-RELATORIO-FINAL-ATRIBUICAO`

Estado dos templates:

- `ProcedureTemplateType::ProcedureMinute`
- `ProcedureTemplateStatus::Active`
- `published_at` preenchido
- `published_by` preenchido com administrador existente, primeiro utilizador existente ou utilizador técnico fallback.

O seeder não foi ligado ao `DatabaseSeeder`, porque o seed base do projeto não garante sempre um utilizador disponível para `created_by`. Deve ser executado explicitamente:

```bash
php artisan db:seed --class=Database\\Seeders\\AlcanenaProcedureTemplateSeeder
php artisan optimize:clear
```

### Testes

Criados:

- `tests/Feature/Backoffice/ProcedureMinuteManagementTest.php`
- `tests/Unit/ProcedureMinutes/ProcedureMinutePayloadBuilderTest.php`

#### Feature

Cobre:

- guest redirecionado para login;
- candidato sem acesso ao backoffice de atas;
- administrador vê página de atas;
- administrador gera ata com concurso e candidatura;
- ata gerada contém payload Alcanena;
- `content_snapshot` resolve variáveis;
- ficheiro HTML é criado em storage local;
- administrador aprova ata;
- botão de aprovação deixa de aparecer depois de aprovada;
- seeder Alcanena é idempotente e cria 9 templates ativos.

#### Unit

Cobre:

- payload completo com concurso;
- júri;
- habitações associadas;
- candidaturas;
- lista provisória;
- lista definitiva;
- audiência;
- reclamação;
- sorteio e resultado;
- desistência;
- decisão administrativa;
- summaries principais do `TemplateVariableResolver`.

## Segurança e RGPD

Decisões aplicadas:

- Nenhuma ata é pública.
- Payload fica apenas em backoffice.
- `show.blade.php` apresenta alerta RGPD explícito.
- Não foram expostas rotas públicas novas.
- Não foram alteradas policies.
- `TemplateRenderingService` continua a escapar variáveis, reduzindo risco de XSS em templates.
- Textos longos de submissões/decisões são truncados no payload.
- Download continua dependente da policy existente `download`.

Permissões preservadas:

- `documents.view`
- `documents.create`
- `documents.approve`
- `documents.publish`

Policies preservadas:

- `ProcedureMinutePolicy`
- `ProcedureTemplatePolicy`
- `GeneratedProcedureDocumentPolicy`

## Base de Dados

Não foram criadas migrations.

Não foram criadas tabelas.

Não foram alterados schemas existentes.

Dados novos da sprint usam:

- `procedure_minutes.payload`
- `procedure_minutes.content_snapshot`
- `procedure_minutes.file_path`
- `procedure_templates.content`
- `procedure_templates.variables`

## Como Usar

### 1. Criar minutas Alcanena

```bash
php artisan db:seed --class=Database\\Seeders\\AlcanenaProcedureTemplateSeeder
php artisan optimize:clear
```

### 2. Confirmar minutas

Backoffice:

```text
/backoffice/procedure-templates
```

Ou via Tinker:

```php
App\Models\ProcedureTemplate::query()
    ->where('template_number', 'like', 'ALC-ATA-%')
    ->get(['template_number', 'name', 'status']);
```

### 3. Gerar ata

Backoffice:

```text
/backoffice/procedure-minutes
```

Passos:

1. escolher minuta;
2. escolher concurso;
3. escolher candidatura opcional;
4. preencher data, hora, local e campos administrativos;
5. gerar ata;
6. rever conteúdo;
7. aprovar ata, se correto;
8. descarregar HTML privado.

## Validações Executadas

### Sintaxe

```bash
php -l app/Http/Requests/GenerateProcedureMinuteRequest.php
php -l app/Services/ProcedureMinutes/ProcedureMinuteService.php
php -l app/Services/ProcedureMinutes/ProcedureMinutePayloadBuilder.php
php -l app/Services/ProcedureTemplates/TemplateVariableResolver.php
php -l app/Http/Controllers/Backoffice/ProcedureMinuteController.php
php -l database/seeders/AlcanenaProcedureTemplateSeeder.php
php -l tests/Feature/Backoffice/ProcedureMinuteManagementTest.php
php -l tests/Unit/ProcedureMinutes/ProcedureMinutePayloadBuilderTest.php
php -l resources/views/backoffice/procedure-minutes/index.blade.php
php -l resources/views/backoffice/procedure-minutes/show.blade.php
```

Resultado: PASS.

### Testes focados

```bash
php artisan test --filter=ProcedureMinuteManagementTest
php artisan test --filter=ProcedureMinutePayloadBuilderTest
php artisan test --filter=Sprint24BackofficeOperationalTest
php artisan test --filter=procedure
```

Resultados:

- `ProcedureMinuteManagementTest`: PASS — 5 testes, 24 assertions.
- `ProcedureMinutePayloadBuilderTest`: PASS — 2 testes, 26 assertions.
- `Sprint24BackofficeOperationalTest`: PASS — 6 testes, 48 assertions.
- `procedure`: PASS — 8 testes, 66 assertions.

### PHPStan focado

```bash
./vendor/bin/phpstan analyse \
app/Http/Requests/GenerateProcedureMinuteRequest.php \
app/Http/Controllers/Backoffice/ProcedureMinuteController.php \
app/Services/ProcedureMinutes/ProcedureMinuteService.php \
app/Services/ProcedureMinutes/ProcedureMinutePayloadBuilder.php \
app/Services/ProcedureTemplates/TemplateVariableResolver.php \
database/seeders/AlcanenaProcedureTemplateSeeder.php \
tests/Feature/Backoffice/ProcedureMinuteManagementTest.php \
tests/Unit/ProcedureMinutes/ProcedureMinutePayloadBuilderTest.php \
--memory-limit=1G -v
```

Resultado: PASS — 0 erros.

### Pint focado

```bash
./vendor/bin/pint --test \
app/Http/Requests/GenerateProcedureMinuteRequest.php \
app/Http/Controllers/Backoffice/ProcedureMinuteController.php \
app/Services/ProcedureMinutes/ProcedureMinuteService.php \
app/Services/ProcedureMinutes/ProcedureMinutePayloadBuilder.php \
app/Services/ProcedureTemplates/TemplateVariableResolver.php \
database/seeders/AlcanenaProcedureTemplateSeeder.php \
tests/Feature/Backoffice/ProcedureMinuteManagementTest.php \
tests/Unit/ProcedureMinutes/ProcedureMinutePayloadBuilderTest.php
```

Resultado: PASS.

### Build

```bash
npm run build
```

Resultado: PASS.

### Diff

```bash
git diff --check
```

Resultado: PASS.

## Validações Globais com Risco Residual

### PHPUnit global

```bash
php artisan test
```

Resultado: falhou por limite de memória de 128 MB no PHP CLI.

Foi tentado:

```bash
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
```

Resultado: executou 727 testes, mas falhou em 13 testes fora do âmbito desta sprint. Exemplos observados:

- `Tests\Unit\DocumentIntelligence\DocumentDuplicateDetectorTest::test_it_detects_duplicate_hash_inside_same_application_scope`
- `Tests\Feature\UX\AccessibilitySmokeTest::test_dashboard_has_labels_focus_and_headings`

Estes erros não foram introduzidos diretamente nos ficheiros alterados da Sprint 44B e devem ser tratados em hardening separado.

### PHPStan global

```bash
./vendor/bin/phpstan analyse --memory-limit=1G -v
```

Resultado: falha global por dívida técnica pré-existente em múltiplos módulos fora da Sprint 44B.

Os ficheiros alterados nesta sprint passam PHPStan focado com 0 erros.

### Pint global

```bash
./vendor/bin/pint --test
```

Resultado: falha global por formatação em múltiplos ficheiros fora da Sprint 44B.

Os ficheiros alterados nesta sprint passam Pint focado.

## Compatibilidade

Compatibilidade preservada com:

- fluxo antigo de `Sprint24BackofficeOperationalTest`;
- templates com placeholders antigos;
- numeração de atas existente;
- exportação HTML privada;
- aprovação de atas;
- policies existentes;
- rotas existentes:
  - `backoffice.procedure-minutes.index`
  - `backoffice.procedure-minutes.generate`
  - `backoffice.procedure-minutes.show`
  - `backoffice.procedure-minutes.download`
  - `backoffice.procedure-minutes.approve`
  - `backoffice.procedure-templates.*`
  - `backoffice.generated-documents.*`

## Riscos Residuais

1. O seeder Alcanena é explícito e não corre automaticamente no `DatabaseSeeder`.
2. O export continua em HTML; se o Município exigir DOCX/PDF formal, será necessária sprint própria.
3. O payload é abrangente, mas depende de relações existentes e dados efetivamente registados no concurso.
4. Summaries são texto plano; não há tabelas formatadas dentro do template nesta fase para evitar alterar o renderer e o modelo de escaping.
5. A suite global tem falhas fora do âmbito desta sprint que devem ser analisadas separadamente.
6. PHPStan/Pint globais continuam a evidenciar dívida técnica pré-existente fora dos ficheiros alterados.

## Recomendação para Próxima Iteração

1. Ligar o `AlcanenaProcedureTemplateSeeder` ao fluxo de instalação/demo apenas se houver garantia de utilizador `created_by`.
2. Criar templates mais ricos por tipo de ata, com conteúdo jurídico final revisto pelo Município.
3. Adicionar export DOCX/PDF com paginação e assinatura manual/digital quando o requisito estiver fechado.
4. Criar view de preview de variáveis antes da geração.
5. Tratar falhas globais de PHPUnit/PHPStan/Pint em sprint técnica separada.

## Decisão Final

`PASS_WITH_ACCEPTED_RISKS`

A Sprint 44B entrega a geração de atas Alcanena sobre o módulo existente, sem migrations, sem novo módulo, com testes focados a passar e compatibilidade preservada com o fluxo operacional anterior. Os riscos aceites estão limitados a validações globais já com dívida técnica fora do âmbito desta sprint e à execução explícita do seeder de minutas.
