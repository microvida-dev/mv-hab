# Arquitetura tecnica

## Snapshot da inspecao inicial

Data da inspecao: 2026-06-10

### Versoes

- Laravel: 13.12.0, confirmado por `php artisan --version`.
- PHP CLI local: 8.5.6, confirmado por `php -v`.
- Requisito Composer declarado: `php` `^8.3`.
- `composer validate`: valido.

### Autenticacao atual

A aplicacao usa autenticacao Laravel Breeze baseada em sessao, com controllers em `app/Http/Controllers/Auth`, rotas em `routes/auth.php`, reset de password, confirmacao de password, verificacao de email, registo, login, logout e perfil.

Nao foi identificado sistema atual de roles, permissions, policies de dominio ou separacao entre utilizadores internos e candidatos.

### Stack frontend atual

- Blade como sistema de views.
- Tailwind CSS.
- Alpine.js.
- Vite com `laravel-vite-plugin`.
- Nao foi identificado Livewire, Inertia, Vue ou React como stack aplicacional.

### Estrutura de rotas

`routes/web.php` redireciona `/` para `/dashboard` e agrupa os modulos aplicacionais sob middleware `auth`.

Rotas resource atuais:

- `citizens`
- `households`
- `housing-units`
- `applications`
- `contracts`
- `payments`
- `maintenance-requests`
- `documents`

Rotas adicionais:

- `dashboard`
- `profile`
- rotas Breeze de autenticacao
- rotas internas Laravel para storage e health/up

`php artisan route:list` apresentou 79 rotas.

### Controllers existentes

Controllers de dominio:

- `DashboardController`
- `CitizenController`
- `HouseholdController`
- `HousingUnitController`
- `HousingApplicationController`
- `ContractController`
- `PaymentController`
- `MaintenanceRequestController`
- `DocumentController`
- `ProfileController`

Controllers de autenticacao:

- `AuthenticatedSessionController`
- `RegisteredUserController`
- `ConfirmablePasswordController`
- `EmailVerificationNotificationController`
- `EmailVerificationPromptController`
- `VerifyEmailController`
- `PasswordController`
- `PasswordResetLinkController`
- `NewPasswordController`

### Models existentes

- `User`
- `Citizen`
- `Household`
- `HousingUnit`
- `HousingApplication`
- `Contract`
- `Payment`
- `MaintenanceRequest`
- `Document`

### Migrations existentes

Migrations Laravel base:

- `users`
- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

Migrations de dominio existentes:

- `citizens`
- `households`
- `housing_units`
- `housing_applications`
- `contracts`
- `payments`
- `maintenance_requests`
- `documents`

### Seeders existentes

- `DatabaseSeeder`
- `UserSeeder`
- `HousingUnitSeeder`
- `CitizenSeeder`
- `DemoDataSeeder`

Observacao: existem seeders de demonstracao com pessoas e moradas ficticias. Antes de ambientes partilhados ou producao, a estrategia de seed deve impedir dados realistas, credenciais de demonstracao e cargas acidentais.

### Factories existentes

- `UserFactory`
- `CitizenFactory`
- `HouseholdFactory`
- `HousingUnitFactory`
- `HousingApplicationFactory`
- `ContractFactory`
- `PaymentFactory`
- `MaintenanceRequestFactory`
- `DocumentFactory`

### Policies existentes

Nao foi encontrada pasta `app/Policies`. Nao foram identificadas policies de dominio.

### Tests existentes

Testes existentes:

- testes de autenticacao Breeze
- testes de perfil
- testes exemplo feature/unit

Nao foram identificados testes de autorizacao por modulo, auditoria, RGPD, documentos, candidatura, contratos, pagamentos ou manutencao.

### Dependencias instaladas/declaradas

Composer:

- `laravel/framework`
- `laravel/tinker`
- `fakerphp/faker`
- `laravel/breeze`
- `laravel/pail`
- `laravel/pao`
- `laravel/pint`
- `mockery/mockery`
- `nunomaduro/collision`
- `phpunit/phpunit`

NPM:

- `vite`
- `laravel-vite-plugin`
- `tailwindcss`
- `@tailwindcss/forms`
- `@tailwindcss/vite`
- `alpinejs`
- `autoprefixer`
- `postcss`
- `concurrently`

Pastas `vendor/` e `node_modules/` estao presentes.

### Modulos ja existentes

- Dashboard
- Autenticacao e perfil
- Munícipes
- Agregados familiares
- Habitações
- Candidaturas simples
- Contratos simples
- Pagamentos simples
- Pedidos de manutencao
- Documentos

### Ficheiros sensiveis presentes

Foram identificados:

- `.env`
- `.env.example`
- `database/database.sqlite`
- `.npmrc`

Nao foi lido o conteudo de `.env`. Estes ficheiros devem ser tratados como sensiveis ou potencialmente sensiveis. `.env` nao deve ser versionado nem exposto.

## Evolução implementada até Sprint 4

- RBAC próprio com roles, permissions, policies e middleware `role`.
- Audit log central utilizado por programas, concursos e Registo de Adesão.
- Portal público Blade/Tailwind.
- Área candidata separada do backoffice.
- Controllers finos e lógica processual em services.
- Estados de adesão representados por enum e histórico relacional.
- Migrations validadas em MySQL; nomes longos de constraints devem ser definidos explicitamente.

## Evolução implementada até Sprint 11

- Módulos processuais de candidatura, elegibilidade, workflow administrativo, classificação interna e listas usam controllers finos, Form Requests, policies e services transacionais.
- A Sprint 11 introduz domínio próprio para listas provisórias, publicações, reclamações, pedidos de informação complementar, audiência, listas definitivas, notificações oficiais internas e logs de alteração de lista.
- As listas públicas usam payload anonimizado no service antes da view; dados pessoais diretos, documentos e paths privados não são renderizados no portal público.
- Publicação de listas exige aprovação prévia e gera `ListPublication` versionada; notificações são registadas internamente, sem transportes reais por email/SMS.
- A passagem para Sprint 12 deve usar listas definitivas publicadas ou bloqueadas, não o ranking interno bruto.

## Evolução implementada até Sprint 12

- O módulo de atribuição segue a arquitetura existente: Blade/Tailwind, controllers finos, Form Requests, policies e services transacionais.
- A atribuição usa `DefinitiveList`/`DefinitiveListEntry` como fonte formal, preservando classificação e listas públicas anteriores.
- `AllocationEngine` coordena execução por ranking, preferências ou sorteio, delegando adequação, ofertas, suplentes, relatórios e notificações para services específicos.
- Campos críticos de estado, numeração, ownership e timestamps são escritos por `forceFill` nos services, não por mass assignment de formulários.
- Sorteios usam service dedicado para seed, valor determinístico por participante, payload auditável e hash.
- Notificações usam o sistema oficial interno existente (`official_notifications`) e não criam canal paralelo.
- A navegação expõe as rotas do Sprint 12 por role, sem abrir backoffice ao candidato.
- A passagem para Sprint 13 deve consumir apenas `Allocation::readyForContract()` e `Application::readyForContract()`.

## Evolução implementada até Sprint 13

- O módulo contratual reutiliza a tabela/model `Contract` existente e adiciona o scope `processual()` para separar contratos novos do CRUD legado.
- Regras de renda, cálculos, revisões manuais, minutas, cláusulas, documentos contratuais, validações, assinaturas, cauções e histórico de estado seguem o padrão de migrations incrementais, models Eloquent, Form Requests, policies e services transacionais.
- `RentCalculationService` é a fonte de cálculo; `LeaseContractService` deriva renda e caução exclusivamente do cálculo aprovado para evitar mass assignment financeiro.
- `LeaseContractStatusService` centraliza transições de estado e histórico contratual.
- `ContractActivationService` valida pré-condições antes de ativar: documento gerado, validação aprovada, assinatura registada e caução paga/dispensada quando aplicável.
- O documento contratual é gerado como HTML em `Storage::disk('local')`, sem URLs públicos permanentes nem exposição de path interno.
- Não existe infraestrutura PDF instalada; a camada `LeaseContractPdfService` explicita essa limitação e mantém ponto de integração futuro.
- Notificações contratuais reutilizam `official_notifications`; não há transporte externo por email/SMS.
- Backoffice e área do candidato continuam em Blade/Tailwind, com navegação por role e isolamento por policy.

## Evolução implementada na Sprint 16

- `OfficialNotificationService` passou a ser a ponte de compatibilidade para notificações existentes e cria automaticamente o registo transversal de comunicação.
- `NotificationEventDispatcher` resolve regras, destinatários, versão ativa, variáveis e entregas por canal.
- `CommunicationLogService`, `CommunicationDeliveryService`, services de canal e `CommunicationReceiptService` separam criação, transporte, tentativa e prova.
- `CriticalNotificationEvent` e `DispatchCriticalNotification` são descobertos automaticamente pelo Laravel.
- Jobs suportam envio de entrega, geração de comprovativo, documento oficial e processamento de pendentes.
- Storage privado `local` guarda comprovativos e documentos HTML; downloads passam por services e policies.
- O mailer `log` ou não configurado não é tratado como envio real.
- SMS é uma abstração desativada sem gateway.
- Postal é manual e não integra CTT/ViaCTT.
- Não foi adicionada dependência Composer/npm.

## Arquitetura alvo

### Camadas propostas

- Interface: Blade e componentes reutilizaveis para backoffice; portal publico/candidato a definir em Sprint 2/3.
- HTTP: Controllers finos por caso de uso ou resource.
- Requests: validacao de entrada, autorizacao preliminar e normalizacao.
- Policies/Gates: autorizacao por entidade e acao.
- Actions/Services: regras de negocio processuais.
- Domain events: registo de eventos administrativos relevantes.
- Jobs/Notifications: comunicacoes assincronas e modelos documentais futuros.
- Models/Eloquent: persistencia relacional.
- Audit layer: logs imutaveis de acesso, alteracao, decisao, publicacao e exportacao.
- Reporting layer: indicadores e dashboards, sem expor dados pessoais desnecessarios.

### Modulos alvo

- Gestao municipal e configuracoes
- Utilizadores, roles e permissions
- Programas e concursos
- Portal publico
- Area do candidato
- Registo de adesao
- Agregado familiar, rendimentos e situacao habitacional
- Documentacao
- Elegibilidade
- Candidaturas
- Workflow administrativo
- Classificacao e ranking
- Listas, reclamacoes e audiencia
- Atribuicao
- Contratos e calculo de renda
- Pagamentos e incumprimentos
- Manutencao e vistorias
- Notificacoes e modelos documentais
- Relatorios
- RGPD e auditoria

### Principios de desenho

- Menor privilegio por defeito.
- Estados explicitos em processos administrativos.
- Decisoes reversiveis apenas quando legalmente permitido e sempre auditadas.
- Dados pessoais separados de dados operacionais quando possivel.
- Documentos sensiveis acessiveis apenas por perfis autorizados.
- Exportacoes controladas, justificadas e registadas.
- Retencao e eliminacao por politica formal.
- Preparacao para multi-municipio, mesmo que a primeira instalacao seja single-tenant.

## Riscos tecnicos evidentes

- Nao ha repositorio Git detetado no diretorio atual; a branch nao foi confirmavel.
- Nao ha policies de dominio.
- Todos os Form Requests de dominio inspecionados autorizam genericamente com `return true`.
- O modelo atual e CRM-centric e nao representa ainda o ciclo processual completo.
- Documentos sao associados de forma flexivel, mas sem tipologia obrigatoria, revisao formal, estado documental ou trilho de acesso.
- Nao ha modelo de auditoria.
- Nao ha modelo de roles/permissions.
- Nao ha separacao clara entre operador municipal, candidato e auditor.
- Seeders de demonstracao podem ser perigosos se executados fora de ambiente local.
- Nao foram encontrados testes de dominio para os CRUDs existentes.

## Riscos de seguranca evidentes

- Existencia de `.env` no workspace local exige cuidado operacional.
- Ausencia de policies aumenta risco de acesso indevido entre modulos.
- Upload de documentos existe; apesar de haver validacao de mime/extensao e tamanho, falta estrategia documentada de quarentena, anti-malware, classificacao e logs de acesso.
- Ausencia de auditoria para leitura/alteracao/exportacao de dados pessoais.
- Ausencia de roles torna dificil aplicar segregacao de funcoes.
- Dados de identificacao, rendimentos, moradas, contratos e pagamentos exigem controlos RGPD mais fortes antes de producao.

## Nao executado nesta sprint

Nao foram planeadas alteracoes a migrations, models, controllers, routes, policies, views, seeders, factories, requests, services, jobs, notifications, dependencias ou testes funcionais. Sprint 0 e apenas documental.

## Arquitetura implementada na Sprint 17

- `Services/Reporting/Indicators`: consultas agregadas por domínio operacional.
- `IndicatorRegistry` e `ReportQueryRegistry`: allowlists de classes e métodos executáveis.
- `IndicatorCalculationService`: cálculo, estado e snapshot opcional com hash de filtros.
- `DashboardService`: composição de dashboards a partir de definições/widgets autorizados.
- `ReportRunService`: execução síncrona auditável sem persistir datasets detalhados.
- `ReportExportService`: geração privada, fallback de formato, âmbito e expiração.
- `ReportDownloadService`: autorização, existência/expiração, download e logging.
- `ReportPermissionService`: segregação entre operacional, executivo, financeiro, manutenção, sensível e nominal.
- `SensitiveDataMaskingService`: remoção/mascaramento de campos pessoais pelo âmbito.
- `ReportAccessLogger`: trilho próprio de consulta, execução, exportação e download.

Decisões técnicas:

- queries agregadas usam Eloquent/Query Builder e filtros centralizados;
- definições configuráveis não aceitam execução arbitrária;
- ficheiros ficam no disk privado `local`, nunca no storage público;
- CSV é o formato tabular nativo nesta fase;
- XLSX e PDF dependem de bibliotecas futuras e usam fallback explícito;
- dashboards não recebem datasets nominais.
