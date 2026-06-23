# Matriz de Cobertura de Testes

| Sprint | Ficheiro principal | Cobertura |
| --- | --- | --- |
| 19 | `tests/Feature/Integrated/FullHousingProgramFlowTest.php` | Fluxo integrado e regressão transversal |
| 20 | `tests/Feature/PublicPortal/PublicHousingOfferSprint20Test.php` | Oferta pública, filtros, mapa, detalhe, documentos e autorização backoffice |
| 21 | `tests/Feature/Public/AdvancedSimulatorTest.php` | Simulador público e resultado indicativo |
| 21 | `tests/Feature/Candidate/CandidateSimulationTest.php` | Área do candidato, histórico e isolamento |
| 21 | `tests/Feature/Candidate/ApplicationPrefillTest.php` | Confirmação e aplicação de pré-preenchimento |
| 21 | `tests/Feature/Candidate/RegistrationRenewalTest.php` | Renovação simplificada de registo |
| 21 | `tests/Feature/Security/SimulatorPrivacyTest.php` | Privacidade entre simulações autenticadas e públicas |
| 21 | `tests/Unit/Simulator/*` | Tipologia, renda, impedimentos e recomendação de concursos |
| 23 | `tests/Feature/Sprint23ProcessTrackingTest.php` | Acompanhamento processual, timeline, notificações, desistência e reutilização de dados |
| 24 | `tests/Feature/Sprint24BackofficeOperationalTest.php` | Dashboards operacionais, relatórios por candidatura, dossier documental, alertas, minutas, automação de listas, atas e confirmações |
| 25 | `tests/Unit/Lottery/AuditableLotteryEngineTest.php` | Motor de sorteio determinístico com seed e hash |
| 25 | `tests/Feature/Backoffice/LotteryClosureFlowTest.php` | Sorteio, participantes, validação, vencedor, convocatórias, presenças, ranking, relatório, chaves, transição, fecho e bloqueio de acesso indevido |
| 26 | `tests/Feature/Sprint26TenantPostAwardTest.php` | Área do inquilino, faturas, pagamentos, cobranças, comunicações, manutenção, vistorias e dashboard pós-atribuição |
| 27 | `tests/Unit/DocumentIntelligence/DocumentAiStatusTest.php` | Enum de estados Document AI |
| 27 | `tests/Unit/DocumentIntelligence/DocumentAiPipelineTest.php` | Pipeline, transições, raw JSON, flags, fields, logs, eventos, auditoria e job |
| 27 | `tests/Feature/DocumentIntelligence/DocumentAiUploadIntegrationTest.php` | Upload/substituição documental, análise pending, queue fake e storage privado |
| 30 | `tests/Unit/DocumentIntelligence/DocumentValidationServicesTest.php` | Comparadores, severidade e registry de regras de validação |
| 30 | `tests/Feature/DocumentIntelligence/DocumentCandidateValidationPipelineTest.php` | Pipeline de validação, runs, flags, eventos, auditoria, queue fake e não alteração de candidatura |
| 30 | `tests/Feature/Backoffice/DocumentAiValidationPanelTest.php` | Painel backoffice, autorização, mascaramento e revisão manual |
| 32 | `tests/Feature/PublicPortal/PublicHousingPresentationSprint32Test.php` | Filtros públicos por renda/estado, brochura HTML imprimível e ocultação de morada privada |
| 32 | `tests/Feature/DemoAlcanenaAffordableRentSeederTest.php` | Seeder demo Alcanena idempotente, programa/concurso publicados para demo e quatro fogos públicos |

## Sprint 20

| Área | Cobertura |
| --- | --- |
| Portal público | Guest vê apenas habitações publicadas |
| Filtros | Tipologia e ausência de resultados |
| Concursos | Detalhe lista habitações públicas associadas |
| Mapa | JSON com marcadores públicos e sem morada/path interno |
| Documentos | Download público incrementa contador |
| Segurança documental | Documento não público devolve 404 |
| Backoffice | Candidato bloqueado; administrador atualiza ficha pública |

## Sprint 21

| Área | Cobertura |
| --- | --- |
| Público | Guest executa simulação e consulta resultado por UUID anónimo |
| Candidato | Simulações exigem autenticação e ficam no histórico do próprio candidato |
| Privacidade | Simulação autenticada não é acessível pela rota pública |
| Pré-preenchimento | Candidato confirma e aplica a dados em rascunho editável |
| Renovação | Candidato inicia, atualiza e submete renovação simplificada |
| Services | Tipologia, renda estimada, impedimentos e recomendações têm testes unitários |

## Sprint 23

| Área | Cobertura |
| --- | --- |
| Processos | Candidato consulta apenas o proprio processo |
| Timeline | Eventos internos ficam ocultos ao candidato e visiveis ao backoffice autorizado |
| Notificacoes | Candidato marca notificacoes como lidas e arquiva comunicacoes proprias |
| Desistencia | Desistencia controlada exige declaracao e cria evento de timeline |
| Reutilizacao | Candidato reutiliza apenas dados proprios e recebe aviso de documentos nao copiados como validos |
| Estado publico | Estado interno submetido gera estado publico compreensivel |
| Regressao | Fluxo legado de audiencia da Sprint 11 continua compativel com `submission_text` |

## Sprint 24

| Área | Cobertura |
| --- | --- |
| Acesso | Guest redirecionado e candidato bloqueado no backoffice operacional |
| Dashboards | Administrador consulta dashboards operacional e executivo |
| Relatórios | Relatório de candidatura gera ficheiro privado com texto obrigatório |
| Dossier documental | Dossier documental é gerado e exportado em storage privado |
| Minutas | Minuta criada, publicada e usada para documento gerado |
| Documentos de procedimento | Documento gerado é aprovado por utilizador autorizado |
| Alertas | Alerta interno é consultado e resolvido |
| Listas | Execução de automação é aprovada apenas após revisão humana |
| Atas | Ata gerada a partir de minuta é aprovada |
| Confirmações | Número de processo é gerado e candidato não acede a rota backoffice |

## Sprint 25

| Área | Cobertura |
| --- | --- |
| Sorteio | Criação, carregamento/bloqueio de participantes, execução e validação |
| Auditoria técnica | Motor com seed determinística gera mesma ordem e mesmo hash |
| Segurança | Guest redirecionado e candidato bloqueado no backoffice |
| Privacidade | Candidato não consulta convocatória de terceiro |
| Pós-sorteio | Vencedor, presenças, ranking pós-sorteio e relatório privado |
| Operação final | Entrega de chaves, transição para inquilino e fecho do concurso |

## Sprint 26

| Área | Cobertura |
| --- | --- |
| Acesso | Guest redirecionado, candidato sem contrato bloqueado e inquilino com contrato ativo autorizado |
| Isolamento | Candidato não consulta faturas, pagamentos ou comunicações de terceiro |
| Financeiro | Backoffice emite fatura operacional, regista pagamento confirmado e atualiza estado da fatura |
| Cobranças | Execução operacional gera faturas internas sem movimento bancário externo |
| Comunicações | Inquilino cria comunicação e backoffice responde |
| Manutenção | Inquilino cria pedido sobre contrato/habitação próprios reutilizando módulo existente |
| Vistorias | Inquilino consulta apenas vistoria própria marcada como visível |
| Dashboard | Backoffice consulta painel de exploração pós-atribuição |

## Sprint 27

| Área | Cobertura |
| --- | --- |
| Infraestrutura | Models, enum, pipeline, job e eventos Document Intelligence |
| Upload | Submissão documental cria análise `pending` sem alterar `DocumentStatus` |
| Substituição | Nova versão documental cria nova análise para a versão corrente |
| Queue | `ProcessDocumentAiJob` é validado com `Queue::fake` e recebe apenas ID da análise |
| Eventos | Start, completed e failed sem `raw_text` ou `raw_ai_json` |
| Auditoria | Criação, processamento, conclusão, falha e flags ficam auditados |
| RGPD | Logs técnicos minimizados e storage privado preservado |
## Sprint 28 — Document Intelligence OCR e Classificação

| Área | Cobertura |
| --- | --- |
| OCR | `DocumentOcrServicesTest`, `DocumentOcrClassificationIntegrationTest` |
| Classificação | `DocumentClassificationServicesTest`, `DocumentClassificationAccuracyTest` |
| Queue/Eventos | `DocumentAiPipelineTest`, `DocumentOcrClassificationIntegrationTest` |
| Backoffice | `DocumentAiClassificationPanelTest` |
| Auditoria | `DocumentAiPipelineTest`, `DocumentAiClassificationPanelTest` |
| Segurança/RGPD | painel protegido, OCR oculto sem permissão de auditoria |

## Sprint 29 — Extração Estruturada de Campos

| Área | Cobertura |
| --- | --- |
| Schemas | `DocumentFieldExtractionServicesTest` valida oito schemas obrigatórios |
| Normalização | Datas, valores monetários, percentagens e NIF |
| Pipeline | `DocumentFieldExtractionPipelineTest` cobre persistência, flags, eventos e auditoria |
| Segurança funcional | Extração não altera `DocumentSubmission` nem candidatura |
| Backoffice | `DocumentAiExtractionPanelTest` cobre listagem, detalhe e marcação para revisão |
| RGPD | Mascaramento de sensíveis e ocultação de dados de saúde |

## Sprint 30 — Validação IA contra Candidatura

| Área | Cobertura |
| --- | --- |
| Regras | Nome aproximado, NIF normalizado, rendimentos e contrato de arrendamento |
| Pipeline | Criação de execução, resultados, flags, eventos e audit logs |
| Queue | Extração estruturada agenda `ValidateDocumentAiAgainstApplicationJob` com `Queue::fake` |
| Segurança funcional | Validação não altera estado da candidatura |
| Backoffice | Guest redirecionado, candidato bloqueado e administrador autorizado |
| RGPD | Valores sensíveis mascarados para técnico sem auditoria documental |

## Sprint 31 — Assistente IA, Score e Aperfeiçoamento

| Área | Cobertura |
| --- | --- |
| Score | `DocumentAiScoreCalculatorTest` cobre componentes, labels e penalizações |
| Explicação | `DocumentAiScoreExplainerTest` cobre sinais positivos, atenção e revisão manual |
| Flags | `DocumentRiskFlagDetectorTest` e `DocumentQualityAnalyzerTest` cobrem divergências e OCR insuficiente |
| Duplicados | `DocumentDuplicateDetectorTest` cobre hash duplicado no âmbito da candidatura |
| Sugestões | `DocumentSuggestionGeneratorTest` e `DocumentSuggestionTemplateRegistryTest` cobrem rascunhos neutros e sem envio automático |
| Pipeline | `DocumentAiAssistantPipelineTest` cobre score, flags, sugestões, eventos, auditoria e não alteração de candidatura |
| Queue | `DocumentAiAssistantIntegrationTest` valida `CalculateDocumentAiScoreJob` com payload mínimo |
| Backoffice | `DocumentAiAssistantDashboardTest` cobre proteção, consulta, edição, aceitação e descarte de sugestões |

## Sprint 32 — Preparação da Demonstração Alcanena

| Área | Cobertura |
| --- | --- |
| Portal público | Filtros visíveis por freguesia, tipologia, renda mínima/máxima e estado |
| Brochura | Página HTML imprimível por fogo com concurso, programa, áreas, renda e nota de localização |
| Privacidade | Brochura não expõe morada interna quando `public_address_visible` está falso |
| Seeder demo | Programa e concurso publicados para demonstração controlada |
| Fogos demo | Quatro fogos fictícios de Alcanena com campos públicos e T2 Monsanto |
