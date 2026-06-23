# Estrategia futura de testes

## Atualização Sprint 26

Foi criado `tests/Feature/Sprint26TenantPostAwardTest.php` para validar autorização da área do inquilino, isolamento entre inquilinos, emissão de fatura operacional, registo de pagamento, execução de cobrança interna, criação e resposta de comunicação, pedido de manutenção, consulta de vistoria própria e dashboard operacional.

PHPStan não foi executado por instrução explícita do utilizador para ignorar PHPStan nesta etapa.

Este documento define a direção de testes. A Sprint 3 acrescentou testes feature para o portal e para a gestão autorizada de programas/concursos.

Checklist manual local da Sprint 8: [sprint-8-local-test-checklist.md](sprint-8-local-test-checklist.md).

Checklist da configuração de demonstração de Alcanena: [alcanena-demo-seeder-checklist.md](alcanena-demo-seeder-checklist.md).

## Cobertura executada na Sprint 3

- carregamento da homepage e FAQ;
- visibilidade exclusiva de programas e concursos publicados;
- 404 para rascunhos e arquivos;
- CTA de concurso aberto;
- bloqueio do backoffice para guest e candidato;
- criação administrativa de programa e concurso;
- proteção contra mass assignment do estado;
- validação da janela temporal do concurso;
- requisito mínimo antes de publicar;
- audit logs de criação e publicação;
- renderização do conteúdo público sem links administrativos;
- verificação no browser em desktop e viewport móvel de 390×844, sem overflow horizontal.

Resultado final em 10/06/2026: `37` testes e `126` asserções passaram. Os testes específicos da Sprint 3 representam `9` testes e `53` asserções.

## Cobertura executada na Sprint 4

- autenticação e role da área candidata;
- atribuição automática de role no registo;
- bloqueio do candidato no CRM legado;
- criação e atualização do próprio registo;
- isolamento entre candidatos;
- proteção de `user_id` e `status`;
- campos obrigatórios, consentimentos e idade mínima;
- finalização, cancelamento, remoção e soft delete;
- histórico de estados;
- auditoria de criação, atualização e finalização;
- bloqueio da eliminação genérica da conta;
- dashboard, formulário e placeholders;
- verificação no browser em desktop e 390×844, sem overflow ou erros de consola.

Resultado global após Sprint 4: `48` testes e `193` asserções aprovados.

## Cobertura executada na Sprint 5

- autenticação, role candidata e dependência do Registo de Adesão;
- criação única de agregado e sincronização do requerente;
- preservação de ownership contra mass assignment;
- CRUD de membros, contagem e proteção do último requerente;
- validação de NIF, data de nascimento e incapacidade;
- isolamento de membros e rendimentos entre candidatos;
- criação, atualização e remoção de rendimentos;
- cálculo mensal/anual e totais por membro/agregado;
- ausência declarada de rendimentos;
- criação/atualização da situação habitacional;
- validação de renda e anos de residência;
- dashboard/progresso e não exposição pública;
- auditoria dos eventos críticos.

Resultado global após Sprint 5 em 10/06/2026: `58` testes e `290` asserções aprovados. Os testes específicos da Sprint 5 representam `10` testes e `97` asserções.

## Cobertura executada na Sprint 6

- checklist documental dinâmica por dados de adesão, membro, rendimento e habitação;
- bloqueio de guests e isolamento entre candidatos;
- submissão de documentos com storage privado, checksum e versão inicial;
- proteção contra mass assignment de `user_id`, `status` e path;
- não exposição de `storage_path` e checksum na área do candidato;
- acesso direto a `/storage/...` bloqueado;
- substituição de documento com histórico de versões;
- validação e rejeição administrativa com motivo;
- logs de acesso documental para upload, substituição, download e revisão;
- criação administrativa de tipos documentais e regras obrigatórias;
- dashboard candidato com resumo documental;
- regressão dos testes das Sprints 3 a 5.

Resultado global após Sprint 6 em 11/06/2026: `68` testes e `369` asserções aprovados. Os testes específicos da Sprint 6 representam `10` testes e `78` asserções.

Verificação browser Sprint 6:

- servidor Laravel local validado em `http://127.0.0.1:8002`;
- Vite local validado em `http://127.0.0.1:5173`;
- login com utilizador fictício `.test`;
- checklist documental com documentos obrigatórios calculados;
- formulário de submissão documental aberto com campos principais;
- viewport móvel `390x844` sem overflow horizontal no formulário.

## Cobertura executada na Sprint 8

- autenticação, role e ownership das candidaturas;
- criação apenas em concurso aberto e com dados preparatórios completos;
- prevenção de candidatura ativa duplicada;
- proteção contra mass assignment de ownership, estado e número;
- declarações obrigatórias e bloqueio por documentação em falta/rejeitada;
- submissão com número único, timestamps, histórico, documentos, declarações e sete snapshots;
- bloqueio de edição após submissão;
- comprovativo privado e ausência de paths internos;
- desistência com histórico;
- consulta read-only no backoffice e auditoria;
- atualização dos testes de Sprint 3/4 que ainda esperavam placeholders.

Resultado global após Sprint 8 em 11/06/2026: `78` testes e `455` asserções aprovados. Os testes específicos da Sprint 8 representam `10` testes e `85` asserções.

Verificação browser Sprint 8:

- servidor Laravel isolado em `http://127.0.0.1:8003`;
- lista de candidaturas validada em desktop;
- revisão validada em `390x844`, com `scrollWidth` igual a `390`;
- comprovativo validado sem paths privados e sem erros de consola;
- tentativa de captura de screenshot pelo browser integrado excedeu o tempo limite; a validação DOM, viewport e consola foi concluída.

`phpstan` e `psalm` continuam ausentes de `vendor/bin`.

## Cobertura executada na Sprint 7

- autenticação, role, ownership e bloqueio do candidato no backoffice;
- CRUD, ativação, arquivo, validação e auditoria de rule sets;
- unicidade e atualização auditada de critérios;
- precedência concurso/programa e exclusão de draft/archived;
- resultados `eligible`, `ineligible`, `insufficient_data` e `requires_review`;
- critérios de registo, idade, prazo, agregado, rendimentos, habitação e limites;
- documentos submetidos e validados com estados da Sprint 6;
- criação de resultados e oito snapshots mínimos;
- proteção contra mass assignment;
- check formal ligado à candidatura sem transição automática;
- separação entre mensagem simples e detalhe técnico.

Resultado global após Sprint 7 em 11/06/2026: `93` testes e `525` asserções aprovados. Os testes específicos representam `14` testes e `60` asserções.

Verificação browser em `http://127.0.0.1:8001`:

- rule sets validados em desktop;
- área do candidato validada em `390x844`;
- pré-verificação executada por candidato fictício;
- mensagem técnica ausente na área do candidato;
- detalhe técnico e snapshots confirmados no backoffice;
- o browser integrado não tinha painel ativo; a validação terminou com Playwright isolado no mesmo servidor.

`phpstan` e `psalm` não estão instalados em `vendor/bin` e não foram executados.

## Objetivos

- Reduzir regressao em funcionalidades municipais criticas.
- Garantir autorizacao por role e policy.
- Garantir rastreabilidade de decisoes.
- Verificar RGPD e protecao de documentos.
- Cobrir workflows de candidatura a contrato.
- Apoiar entrada em producao com confianca.

## Tipos de teste

### Unitarios

- Regras de elegibilidade.
- Calculo de pontuacao.
- Calculo de renda.
- Validadores de prazo.
- Normalizacao de dados.

### Feature

- Autenticacao e perfil.
- CRUDs existentes.
- Submissao de candidatura.
- Validacao documental.
- Transicoes de estado.
- Publicacao de listas.
- Reclamacoes.
- Atribuicao.
- Contratos e pagamentos.
- Manutencao e vistorias.

### Autorizacao

- Cada role por modulo.
- Candidato limitado ao proprio processo.
- Juri limitado a concursos designados.
- Gestor financeiro sem acesso a avaliacao social.
- Gestor de manutencao sem acesso a rendimentos.
- Auditor sem escrita operacional.

### Auditoria

- Log de acesso a documentos sensiveis.
- Log de alteracao de dados pessoais.
- Log de decisao administrativa.
- Log de publicacao.
- Log de exportacao.

### RGPD

- Pedidos de acesso.
- Retificacao.
- Eliminacao/anonimizacao quando aplicavel.
- Retencao documental.
- Exportacao dos proprios dados.

### Integracao

- Notificacoes.
- Storage privado.
- Filas/jobs.
- Importacao/migracao de dados.
- Exportacoes oficiais.

### Interface

- Formularios responsivos.
- Tabelas e filtros.
- Estados vazios e erros.
- Acessibilidade basica.
- Navegacao por role.

## Dados de teste

- Usar factories e seeders especificos de teste.
- Evitar dados pessoais reais.
- Evitar moradas, documentos ou contactos realistas quando nao necessario.
- Usar emails de dominio reservado.
- Nunca usar passwords reais.

## Cobertura executada na Sprint 19

- criado `Database\Seeders\Testing\IntegratedWorkflowTestSeeder` com cenários fictícios `s19-*@example.test`;
- adicionadas factories financeiras em falta para conta financeira, plano de renda, prestação, pagamento e mora;
- adicionados testes unitários determinísticos para elegibilidade, pontuação, renda/taxa de esforço e mascaramento de auditoria;
- adicionados testes Feature para matriz de permissões, MFA em rotas sensíveis, storage privado/documentos, fluxo integrado e smoke básico de queries;
- criada documentação QA: matriz de cobertura, relatório de qualidade, plano de regressão, quality gates, bug-fix report e revisão de performance/queries.

Resultado da fatia Sprint 19 em 16/06/2026: `17` testes e `169` asserções aprovados. A suite completa deve ser executada no fecho da sprint e antes da Sprint 20.

Pendências de qualidade:

- configurar CI;
- decidir adoção de PHPStan ou Psalm;
- evoluir smoke tests de queries para ensaio com volume de staging;
- manter validação jurídica/RGPD antes de produção.

## Cobertura do seeder de demonstração de Alcanena

- criação idempotente de município, programa e concurso em rascunho;
- prazos processuais, membros fictícios de júri, workflow administrativo e configuração de renda/caução;
- 22 critérios de elegibilidade, incluindo sete impedimentos sujeitos a análise manual;
- limite anual por dimensão do agregado, RMMG de referência, adequação tipológica e taxa de esforço de 35%;
- matriz do Anexo I com quatro critérios, 18 regras de escala e quatro desempates;
- 11 tipos/requisitos documentais, incluindo condições de deficiência, gravidez e dispensa de IRS;
- três habitações fictícias T1, T2 e T3 e regras de ocupação;
- regra de atribuição por classificação, preferências e sorteio de desempate;
- minuta contratual, cláusulas obrigatórias, templates de comunicação e modelos documentais demo;
- cálculo de exemplo com qualificação, idade, dependentes e deficiência;
- verificação de que condições documentais só são ativadas quando aplicáveis;
- execução repetida sem duplicar a configuração.

## Ambientes

- Local: desenvolvimento rapido, dados ficticios.
- Test: execucao automatizada isolada.
- Staging: validacao municipal sem dados reais salvo decisao formal.
- Producao: apenas apos plano de migracao, backup e seguranca.

## Cobertura minima por fase

- Sprint 1: autorizacao base, auditoria base e regressao dos CRUDs existentes.
- Sprint 3 a 6: portal, adesao, agregado e documentos.
- Sprint 7 a 12: elegibilidade, candidatura, classificacao, listas e atribuicao.
- Sprint 13 a 16: contratos, pagamentos, manutencao e notificacoes.
- Sprint 18 a 20: RGPD, seguranca, testes integrados, migracao e go-live.

## Comandos previstos

Comandos de teste so devem ser executados quando aprovados pela sprint correspondente:

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Na Sprint 6, `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build` e `./vendor/bin/pint` foram executados. `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 9

- `tests/Feature/Sprint9AdministrativeWorkflowTest.php` cobre acesso backoffice/candidato, criação única de processo, histórico/auditoria, transições válidas e inválidas, pedido de aperfeiçoamento, resposta do candidato, análise da resposta, decisão de admissão e scope para Sprint 10.
- Os testes verificam que draft de aperfeiçoamento não é visível ao candidato e que outro candidato não acede ao pedido.
- A proteção contra exposição de notas internas ao candidato é coberta pela timeline da área pessoal.
- A execução inicial de `php artisan migrate` falhou por nomes automáticos de constraints longos no MySQL; a migration foi corrigida com nomes curtos e repetida com sucesso.
- A suíte completa foi executada após Pint e passou com 99 testes/557 asserções.
- `npm run build` foi executado com Vite e passou.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 10

- `tests/Feature/Sprint10ScoringRankingTest.php` cobre proteção de backoffice, criação/atualização/ativação/arquivo de matriz, precedência concurso/programa, execução de classificação, filtro por admissão administrativa e elegibilidade, criação de pontuações, detalhes, snapshot e entradas de ranking.
- Os testes verificam pontuação manual, limite por `max_points`, atualização de total, bloqueio de pontuação e auditoria.
- Ranking por `total_score desc` e desempate configurado por rendimento per capita foram validados.
- Candidato é bloqueado no backoffice de classificação e não existe rota pública de ranking.
- A suíte completa foi executada após Pint e passou com 106 testes/597 asserções.
- `npm run build` foi executado com Vite e passou.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 11

- `tests/Feature/Sprint11ListsComplaintsHearingTest.php` cobre bloqueio de backoffice para candidato, geração de lista provisória a partir de snapshot interno, publicação apenas após aprovação e renderização pública anonimizada.
- Os testes verificam que nome, email e número formal de candidatura não aparecem no portal público de resultados.
- Candidato submete reclamação própria durante prazo ativo e outro candidato não consegue reclamar sobre entrada alheia.
- Backoffice marca reclamação como recebida, inicia análise, cria decisão, aprova decisão e gera lista definitiva.
- Audiência de interessados cobre emissão, submissão da pronúncia pelo titular e bloqueio de outro candidato.
- A prontidão para Sprint 12 é coberta por scope de candidaturas em lista definitiva publicada/bloqueada, sem implementar atribuição.
- Teste específico da Sprint 11 passou com 6 testes/42 asserções.
- A suíte completa foi executada após Pint e passou com 112 testes/639 asserções.
- `npm run build` foi executado com Vite e passou.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 12

- `tests/Feature/Sprint12AllocationTest.php` cobre proteção de backoffice, associação de habitação ao concurso, bloqueio de duplicado ativo, preferências próprias do candidato, bloqueio de outro candidato, execução por ranking, criação de oferta, lista suplente, relatório e notificação interna.
- Os testes verificam aceitação de oferta pelo titular, bloqueio de consulta por outro candidato e passagem para `ready_for_contract`.
- Sorteio auditável é coberto com seed fixa, participantes, resultados, hash e acesso de auditor à vista de auditoria.
- O teste específico da Sprint 12 passou com 6 testes/33 asserções.
- A suíte completa foi executada após Pint e passou com 118 testes/672 asserções.
- `npm run build` foi executado com Vite e passou.
- `./vendor/bin/pint --test` passou após formatação.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 13

- `tests/Feature/Sprint13ContractsRentDepositTest.php` cobre proteção de backoffice contratual, bloqueio de candidato no backoffice e ownership na área candidata.
- Os testes verificam cálculo de renda por regra ativa, aprovação, detalhes de cálculo, caução e auditoria.
- Criação de contrato é testada a partir de atribuição pronta para contrato e cálculo aprovado.
- A proteção contra mass assignment financeiro é coberta: renda e caução do contrato derivam do cálculo aprovado.
- O fluxo de documento HTML privado, emissão, validação interna, assinatura manual, caução paga e ativação foi validado.
- Ativação atualiza estado da habitação e da unidade associada ao concurso.
- Download de documento contratual é bloqueado para outro candidato e permitido ao titular.
- O teste específico da Sprint 13 passou com 4 testes/49 asserções.
- A suíte completa passou com 122 testes/721 asserções antes da atualização documental final.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 14

- `tests/Feature/Sprint14FinanceTest.php` cobre proteção de ownership do candidato para contas financeiras.
- Os testes verificam criação de conta financeira, geração de plano/prestações, registo e confirmação de pagamento, imputação e emissão de comprovativo interno.
- Incumprimento cobre deteção de prestação vencida, emissão de aviso visível ao candidato e criação de acordo de regularização com prestações.
- Revisão de renda cobre declaração de alteração de rendimentos, aceitação pelo backoffice, criação de revisão, cálculo, aprovação e aplicação da nova renda.
- Atualização documental anual cobre criação de pedido pelo backoffice e submissão pelo candidato.
- O teste específico da Sprint 14 passou com 4 testes/34 asserções.
- A suíte completa passou com 126 testes/755 asserções.
- `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build`, `./vendor/bin/pint` e `./vendor/bin/pint --test` foram executados com sucesso.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 15

- `tests/Feature/Sprint15MaintenanceInspectionTest.php` cobre criação de pedido de manutenção por candidato com contrato ativo próprio.
- Os testes verificam proteção contra mass assignment de campos administrativos no pedido do candidato.
- Os testes verificam bloqueio de outro candidato em pedido de manutenção alheio.
- O fluxo backoffice cobre triagem, atribuição, intervenção, custo, aprovação, resolução, fecho e histórico técnico.
- O fluxo de vistorias cobre template/checklist, criação de vistoria, bloqueio antes da visibilidade, conclusão, validação, geração de auto e consulta do candidato autorizado.
- Dashboard/relatório de custos e bloqueio de candidato no backoffice são cobertos.
- O teste específico da Sprint 15 passou com 4 testes/43 asserções.
- A suíte completa passou com 130 testes/798 asserções após Pint.
- `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build`, `./vendor/bin/pint` e `./vendor/bin/pint --test` foram executados com sucesso.
- `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.

## Cobertura acrescentada na Sprint 16

- `tests/Feature/Sprint16CommunicationsTest.php` cobre autenticação, ownership, bloqueio de backoffice ao candidato e leitura do auditor sem escrita.
- Os testes validam leitura e tomada de conhecimento com comprovativos privados.
- Templates cobrem criação, versão inicial, aprovação, ativação e pré-visualização.
- Dispatch cobre regra inativa, regra ativa, snapshot, entrega in-app, tentativa, comprovativo e notificação.
- Renderer cobre variável em falta, variável desconhecida e bloqueio de variável sensível por SMS.
- Canais cobrem email `pending_configuration` e SMS `disabled` sem falso estado de envio.
- Documentos cobrem template/versionamento, geração HTML privada, checksum, ownership e download autorizado.
- Preferências cobrem manutenção obrigatória do canal in-app.
- Seeders são exercitados para confirmar templates, regras, documentos e aviso jurídico demo.
- Teste específico passou com 9 testes/56 asserções.
- Suíte completa passou com 139 testes/854 asserções.
- `php artisan view:cache` validou a compilação das views Blade.
- `npm run build` validou o bundle Vite.
- Não há executável PHPStan/Psalm instalado.

## Cobertura acrescentada na Sprint 17

- `tests/Feature/Sprint17ReportingDashboardTest.php` cobre guest, candidato, técnico, administrador e auditor.
- Os testes verificam bloqueio do dashboard executivo sem permissão específica.
- Filtros por programa/concurso e snapshots de indicador são exercitados.
- Execução de relatório valida filtros persistidos, access log, audit log e ausência de email do candidato no resultado.
- Exportação valida storage privado, nome/path seguro, download autorizado e download log.
- Relatório financeiro sensível é bloqueado ao técnico e exige confirmação do administrador.
- Fallback XLSX para CSV e PDF para HTML é validado.
- Mass assignment de sensibilidade, permissão e método de query é bloqueado.
- Teste específico passou com 8 testes/44 asserções.
- Suíte completa passou com 147 testes/898 asserções após Pint.
- `npm run build`, `composer validate --no-check-publish`, `php artisan route:list --path=backoffice/reports`, `php artisan view:cache` e `./vendor/bin/pint --test` passaram.
- PHPStan e Psalm não estão instalados.

## Cobertura acrescentada na Sprint 18

- `tests/Feature/Sprint18RgpdSecurityAuditTest.php` cobre proteção MFA do backoffice de segurança e acesso do candidato ao centro RGPD próprio.
- Os testes verificam secret MFA encriptado, recovery codes não guardados em texto claro e uso único.
- Falha de login cobre `access_logs`, regra de alerta e criação de `security_alerts`.
- Audit trail cobre mascaramento de dados sensíveis e bloqueio de mutação.
- Pedido RGPD cobre criação pelo candidato, bloqueio de outro candidato e exportação privada com checksum.
- Download de exportação cobre log de acesso.
- Retenção cobre simulação sem alteração automática, bloqueio de execução sem aprovação e execução aprovada conservadora.
- Anonimização cobre aprovação prévia e mascaramento do perfil do titular.
- Revisão de permissões e checklist pré-produção cobrem findings e bloqueio de aprovação com item falhado.
- Teste específico passou com 7 testes/40 asserções antes da validação global da sprint.

## Cobertura acrescentada na Sprint 19

- `Database\Seeders\Testing\IntegratedWorkflowTestSeeder` cria cenários fictícios de fluxo completo para validação integrada.
- Foram adicionadas factories financeiras para conta de arrendatário, plano de renda, prestação, pagamento e incumprimento.
- `AuditEventFormatterTest` valida mascaramento e formatação de eventos de auditoria.
- `EligibilityCalculationDeterministicTest` valida resultados elegível, inelegível e dados insuficientes de forma determinística.
- `ScoringCalculationDeterministicTest` valida cálculo de pontuação e ordenação.
- `RentCalculationDeterministicTest` valida renda normal, renda máxima pela taxa de esforço e rendimento zero com revisão manual.
- `FullHousingProgramFlowTest` valida o ciclo integrado desde concurso/candidatura até lista, atribuição, contrato, financeiro, manutenção, relatório e auditoria.
- `PermissionMatrixTest` valida bloqueios principais de guest/candidato e permissões internas sensíveis.
- `DocumentSecurityFlowTest` valida storage privado, bloqueio de documento alheio, ausência de paths internos e auditoria de download.
- `BasicLoadSmokeTest` valida budgets básicos de query para listagens críticas.
- A fatia Sprint 19 passou com 17 testes/169 asserções.
- A suite completa passou com 174 testes/1164 asserções.
- `php artisan route:list`, `php artisan test`, `npm run build`, `composer validate --no-check-publish`, `php artisan view:cache`, `php artisan view:clear` e `./vendor/bin/pint --test` foram executados.
- `./vendor/bin/pint --test` falhou inicialmente por formatação pendente; `./vendor/bin/pint` foi aplicado e a validação final passou.
- PHPStan e Psalm não estão instalados em `vendor/bin`.

## Cobertura acrescentada na Sprint 20

- `tests/Feature/PublicPortal/PublicHousingOfferSprint20Test.php` cobre portal público de oferta habitacional.
- Os testes validam que guests veem apenas habitações publicadas.
- Os filtros públicos por tipologia são exercitados.
- O detalhe de concurso mostra habitações públicas associadas.
- O endpoint de mapa devolve marcadores públicos sem morada completa nem paths internos.
- O detalhe da habitação mostra documentos públicos sem expor path de storage.
- O download de documento público passa por controller e incrementa contador.
- Documentos não públicos devolvem 404.
- Candidato é bloqueado no backoffice; administrador atualiza ficha pública.
- Teste específico passou com 6 testes/27 asserções antes da validação global da sprint.

## Cobertura acrescentada na Sprint 24

- `tests/Feature/Sprint24BackofficeOperationalTest.php` cobre o backoffice operacional.
- Os testes validam proteção de dashboards contra guest e candidato.
- Relatório por candidatura e dossier documental são gerados em storage privado.
- Minuta de procedimento é criada, publicada e usada para documento gerado.
- Documento gerado e ata de procedimento exigem aprovação humana.
- Alerta interno é consultado e resolvido.
- Execução de automação de lista é aprovada por service, sem publicação automática.
- Confirmação de processo é gerada com número único e candidato é bloqueado em rota backoffice.
- Teste específico passou com 5 testes/47 asserções antes da validação global da sprint.

## Cobertura acrescentada na Sprint 29

- `tests/Unit/DocumentIntelligence/DocumentFieldExtractionServicesTest.php` cobre registry de schemas, extração por regex, normalização e scoring de obrigatórios em falta.
- `tests/Feature/DocumentIntelligence/DocumentFieldExtractionPipelineTest.php` cobre persistência de extração, audit logs, eventos, não alteração de documento funcional, revisão manual e tipo não suportado.
- `tests/Feature/Backoffice/DocumentAiExtractionPanelTest.php` cobre proteção do painel, bloqueio de candidato, mascaramento de dados sensíveis, audit log de consulta e marcação de campo para revisão.
- Foram adicionadas fixtures sintéticas em `tests/Fixtures/document-intelligence/extraction`.
- Teste focado passou com 25 testes/142 asserções antes da validação global da sprint.

## Cobertura acrescentada na Sprint 30

- `tests/Unit/DocumentIntelligence/DocumentValidationServicesTest.php` cobre comparação de nomes, identificadores, rendimentos, severidade e registry de regras.
- `tests/Feature/DocumentIntelligence/DocumentCandidateValidationPipelineTest.php` cobre criação de run, validações, flags, eventos, auditoria, queue fake e ausência de alteração do estado da candidatura.
- `tests/Feature/Backoffice/DocumentAiValidationPanelTest.php` cobre proteção do painel, bloqueio de candidato, mascaramento de dados sensíveis e marcação para revisão manual.
- Teste focado inicial passou com 8 testes/34 asserções antes da validação global da sprint.
- Suite completa confirmada com `php -d memory_limit=512M vendor/bin/phpunit`: 264 testes/1640 asserções.

## Cobertura acrescentada na Sprint 31

- `tests/Unit/DocumentIntelligence/DocumentAiScoreCalculatorTest.php` cobre cálculo ponderado e penalizações configuráveis.
- `tests/Unit/DocumentIntelligence/DocumentAiScoreExplainerTest.php` cobre explicações operacionais e linguagem não acusatória.
- `tests/Unit/DocumentIntelligence/DocumentRiskFlagDetectorTest.php` cobre mapeamento de divergências de validação para flags de risco.
- `tests/Unit/DocumentIntelligence/DocumentDuplicateDetectorTest.php` cobre deteção de duplicados por hash no âmbito da candidatura.
- `tests/Unit/DocumentIntelligence/DocumentQualityAnalyzerTest.php` cobre OCR insuficiente e ausência de exposição de path interno.
- `tests/Unit/DocumentIntelligence/DocumentSuggestionGeneratorTest.php` e `DocumentSuggestionTemplateRegistryTest.php` cobrem rascunhos internos e linguagem neutra.
- `tests/Unit/DocumentIntelligence/DocumentAiAssistantPipelineTest.php` cobre persistência, eventos, auditoria e ausência de alteração funcional.
- `tests/Feature/DocumentIntelligence/DocumentAiAssistantIntegrationTest.php` cobre queue fake e decisão final humana.
- `tests/Feature/Backoffice/DocumentAiAssistantDashboardTest.php` cobre painel protegido e gestão interna de sugestões.
- Teste focado passou com 14 testes/91 asserções antes da validação global da sprint.
