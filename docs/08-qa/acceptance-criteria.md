# Criterios gerais de aceitacao

## Sprint 26

- Inquilino com contrato ativo acede à área do inquilino.
- Candidato sem contrato pós-atribuição não acede à área do inquilino.
- Inquilino só consulta contratos, faturas, pagamentos, manutenção, vistorias e comunicações próprias.
- Backoffice autorizado emite fatura operacional.
- Backoffice autorizado regista e confirma pagamento operacional.
- Execução de cobranças gera faturas internas sem integração bancária.
- Pedido de manutenção usa contrato/habitação próprios.
- Vistoria só é visível ao inquilino quando `tenant_visible = true`.
- Comunicações não podem ser associadas a contrato de terceiro.
- Dashboard operacional apresenta indicadores pós-atribuição.

## Sprint 0

- Branch/repo de trabalho deve ser confirmado; se nao for possivel, o bloqueio deve ser documentado.
- Repositorio deve ser inspecionado antes de alteracoes.
- Pasta `docs/` deve existir.
- Documentos obrigatorios devem ser criados ou atualizados.
- Visao do produto deve estar documentada.
- Diferenca entre CRM atual e plataforma final deve estar documentada.
- Arquitetura tecnica atual e alvo deve estar documentada.
- Modelo de dados alvo deve estar documentado.
- Perfis de utilizador devem estar documentados.
- Workflows futuros devem estar documentados.
- Matriz de permissoes deve estar documentada.
- Estrategia RGPD e auditoria deve estar documentada.
- Roadmap Sprints 0 a 21 deve existir.
- Sprint 1 deve estar preparada, mas nao executada.
- Sprint 2 deve estar preparada, mas nao executada.
- Nenhuma migration deve ser criada.
- Nenhum controller deve ser criado.
- Nenhum model deve ser criado.
- Nenhuma route aplicacional deve ser alterada.
- Nenhuma dependencia deve ser instalada.
- Nenhum dado pessoal real deve ser introduzido.
- Nenhuma password real, token ou APP_KEY deve ser introduzido.
- `.env` nao deve ser alterado.

## Criterios transversais futuros

- Cada modulo deve ter dono funcional.
- Cada alteracao de estado deve ter autorizacao e historico.
- Cada decisao administrativa deve ter fundamento.
- Cada documento sensivel deve ter controlo de acesso.
- Cada exportacao deve ser auditada.
- Cada fluxo critico deve ter testes automatizados.
- Cada entrega deve declarar comandos executados e resultados.
- Cada sprint deve explicitar fora de escopo.

## Sprint 19

- [x] Repositório e stack de testes foram inspecionados sem executar verificação de branch.
- [x] PHPUnit, `phpunit.xml`, factories, seeders, roles/permissões, storage fake, queue/mail/session de teste e ausência de PHPStan/Psalm/CI foram documentados.
- [x] Matriz de cobertura criada em `docs/qa/test-coverage-matrix.md`.
- [x] Relatório de qualidade criado em `docs/qa/sprint-19-quality-report.md`.
- [x] Plano de regressão criado em `docs/qa/regression-test-plan.md`.
- [x] Quality gates criados em `docs/qa/quality-gates.md`.
- [x] Bug-fix report criado em `docs/qa/bug-fix-report.md`.
- [x] Revisão básica de performance/queries criada em `docs/qa/performance-query-review.md`.
- [x] Seeder integrado de QA criado com dados fictícios e emails `example.test`.
- [x] Factories financeiras em falta foram adicionadas para apoiar testes integrados.
- [x] Testes unitários determinísticos foram criados para elegibilidade, pontuação, renda e auditoria.
- [x] Testes Feature foram criados para fluxo integrado, matriz de permissões, segurança documental e smoke queries.
- [x] A fatia Sprint 19 passou com 17 testes e 169 asserções.
- [x] Nenhum módulo funcional novo foi implementado.
- [x] Sprint 20 não foi executada.

## Sprint 3

- [x] Homepage pública acessível sem autenticação.
- [x] Programas publicados listados e consultáveis por slug.
- [x] Programas em rascunho ou arquivados devolvem 404 no detalhe público.
- [x] Concursos publicados listados com referência, programa, município, estado e prazo.
- [x] Concursos em rascunho ou arquivados devolvem 404 no detalhe público.
- [x] Prazos formais apresentados quando existem.
- [x] FAQ institucional com perguntas mínimas.
- [x] CTAs de autenticação e indicação explícita de que a candidatura formal ainda não está disponível.
- [x] Backoffice protegido por autenticação e policies.
- [x] Publicação auditada e estados críticos não aceites por mass assignment.
- [x] Empty states, headings, labels e layout responsivo base.
- [x] Sem dados pessoais ou links administrativos em páginas públicas para guests.
- [x] Testes, route list, migrations, build e formatter executados.

## Sprint 4

- [x] Área do candidato protegida por autenticação e role.
- [x] Nova conta recebe role `candidate`.
- [x] Candidato não acede ao backoffice legado.
- [x] Dashboard mostra estado, percentagem, campos em falta e próximos passos.
- [x] Candidato inicia e atualiza o próprio Registo de Adesão.
- [x] `user_id` e `status` não são controláveis pelo formulário.
- [x] Finalização exige campos mínimos, consentimentos e idade mínima de 18 anos.
- [x] Finalização regista estado, `submitted_at` e histórico.
- [x] Cancelamento e remoção respeitam regras de estado.
- [x] Remoção preserva histórico e aplica soft delete.
- [x] Auditoria cobre criação, atualização, finalização, cancelamento e remoção.
- [x] Dados de outro candidato não são apresentados.
- [x] Candidaturas, documentos e notificações existem apenas como placeholders.
- [x] Eliminação genérica da conta é bloqueada quando existe histórico.
- [x] Testes, migrations, route list, build e Pint concluídos.

## Sprint 5

- [x] Registo de Adesão é obrigatório antes das áreas de agregado.
- [x] Candidato cria um único agregado e recebe requerente principal sincronizado.
- [x] Agregados CRM legados mantêm compatibilidade.
- [x] Membros podem ser criados, atualizados e removidos quando permitido.
- [x] Existe sempre pelo menos um requerente.
- [x] NIF é único dentro do agregado e datas/percentagens são validadas.
- [x] Rendimentos exigem fonte e valor mensal ou anual.
- [x] O valor em falta é calculado e os totais são atualizados.
- [x] Ausência de rendimentos é suportada e coerente.
- [x] Situação habitacional pode ser criada e atualizada.
- [x] Dashboard apresenta progresso, resumos e campos em falta.
- [x] Policies impedem acesso cruzado entre candidatos.
- [x] Ownership não é controlável por mass assignment.
- [x] Eventos críticos são auditados sem valores sensíveis.
- [x] Dados sensíveis não aparecem publicamente.
- [x] Migration incremental, route list, 58 testes/290 asserções, build e Pint foram executados.
- [x] Não foram implementados documentos, elegibilidade ou candidatura formal.

## Sprint 6

- [x] Sprints 4 e 5 verificadas como dependências existentes.
- [x] Tipos documentais administrativos criados com sensibilidade, formatos, tamanho máximo e retenção prevista.
- [x] Regras de documentos obrigatórios criadas por programa, concurso, entidade alvo e condição.
- [x] Checklist documental dinâmica calcula documentos em falta, submetidos, validados e rejeitados.
- [x] Candidato submete documentos apenas para o próprio Registo de Adesão e respetivos alvos.
- [x] Documentos são guardados em storage privado.
- [x] Paths internos e checksums não aparecem na área do candidato.
- [x] Substituição cria nova versão e marca a anterior como substituída.
- [x] Backoffice pode marcar em análise, validar e rejeitar com motivo.
- [x] Downloads passam por controller autorizado e são bloqueados para outros candidatos.
- [x] Logs de acesso documental e audit logs são criados para eventos críticos.
- [x] Proteção contra mass assignment de ownership, estado e path foi coberta por testes.
- [x] UI candidate/backoffice e dashboard foram atualizados sem implementar candidatura formal.
- [x] Migration incremental, route list, testes, build, Pint e verificação browser foram executados.
- [x] Não foram implementados elegibilidade, classificação, ranking, candidatura formal, notificações reais ou integrações externas.

## Sprint 8

- [x] Candidato consulta “As minhas candidaturas”.
- [x] Concurso aberto apresenta CTA e permite criar rascunho.
- [x] Concurso fechado e dados base incompletos bloqueiam o início.
- [x] Candidatura ativa duplicada é bloqueada pelo service.
- [x] Submissão exige cinco declarações obrigatórias.
- [x] Documentos obrigatórios em falta ou rejeitados bloqueiam a submissão.
- [x] Submissão atribui número único, `submitted_at`, `locked_at` e estado `submitted`.
- [x] Histórico, declarações, documentos associados e sete snapshots são criados.
- [x] Edição direta fica bloqueada depois da submissão.
- [x] Candidato consulta comprovativo e versão de impressão.
- [x] Outro candidato não acede ao detalhe ou comprovativo.
- [x] Backoffice autorizado consulta lista e detalhe read-only.
- [x] Mass assignment de campos críticos está coberto.
- [x] Snapshot e UI não expõem paths privados.
- [x] Auditoria cobre criação, submissão, snapshot, consulta e desistência.
- [x] Migration incremental, 13 rotas, 78 testes/455 asserções, build, Pint e browser foram validados.
- [x] Não foram implementados elegibilidade, classificação, ranking, atribuição, contratos ou notificações reais.

## Sprint 7

- [x] Rule sets configuráveis existem por programa e concurso.
- [x] Rule set ativo do concurso prevalece sobre o programa; draft e archived são ignorados.
- [x] Critérios inativos não são avaliados e códigos são únicos dentro do rule set.
- [x] Pré-check do candidato e check formal da candidatura criam histórico.
- [x] Cada critério cria resultado individual e cada check cria resultado global.
- [x] Agregação respeita a precedência definida.
- [x] Agregado, rendimentos, habitação, documentos e candidatura duplicada estão integrados.
- [x] Snapshots mínimos não incluem ficheiros, paths, NIF ou números de documento.
- [x] Candidato consulta apenas resultados próprios e sem mensagens técnicas.
- [x] Backoffice gere regras/critérios e consulta detalhe técnico.
- [x] Auditoria cobre configuração, execução, reexecução e consulta.
- [x] Resultado formal não altera automaticamente o estado da candidatura.
- [x] Migration, 194 rotas, 93 testes/525 asserções, build, Pint e browser foram validados.
- [x] Não foram implementados classificação, ranking, listas, atribuição ou decisão administrativa final.

## Sprint 9

- [x] Existe processo administrativo associado à candidatura submetida.
- [x] Cada candidatura tem no máximo um processo administrativo ativo.
- [x] Processo administrativo tem número único e estado formal.
- [x] Mudanças de estado criam histórico.
- [x] Backoffice lista, consulta, atribui técnico e inicia triagem/análises.
- [x] Backoffice cria análises e pedidos de aperfeiçoamento com prazo e itens.
- [x] Candidato vê apenas pedidos próprios, emitidos e visíveis.
- [x] Candidato responde aos próprios pedidos com texto e/ou documento existente.
- [x] Backoffice aceita ou rejeita respostas.
- [x] Decisões de admissão/não admissão exigem fundamentação.
- [x] Decisão aprovada atualiza o estado administrativo para `admitted_for_scoring` ou `not_admitted`.
- [x] `Application::admittedForScoring()` devolve apenas candidaturas administrativamente admitidas.
- [x] Notas internas não aparecem ao candidato.
- [x] Backoffice exige role/permissão e candidato não acede ao backoffice.
- [x] Teste específico da Sprint 9 passou com 6 testes/32 asserções.
- [x] Suíte completa passou com 99 testes/557 asserções após Pint.
- [x] `php artisan migrate`, `php artisan route:list`, `npm run build` e `./vendor/bin/pint` foram executados.
- [x] Não foram implementados classificação, ranking, listas provisórias, reclamações, atribuição ou notificações reais.

## Sprint 10

- [x] Existem matrizes de classificação configuráveis por programa e concurso.
- [x] Matriz ativa do concurso prevalece sobre matriz ativa do programa.
- [x] Critérios, regras de pontuação e regras de desempate são configuráveis.
- [x] Execução considera apenas candidaturas com processo administrativo `admitted_for_scoring`.
- [x] Último `EligibilityCheck` elegível é exigido por defeito quando há elegibilidade.
- [x] `ScoringRun`, `ApplicationScore`, `ApplicationScoreDetail`, `RankingSnapshot` e `RankingEntry` são criados.
- [x] Pontuação automática, pontuação manual, total e bloqueio são suportados.
- [x] Pontuação manual acima de `max_points` é bloqueada.
- [x] Ranking interno aplica desempates configurados e preserva snapshots.
- [x] Candidato não acede ao backoffice de classificação nem vê ranking/pontuação.
- [x] Auditoria cobre execução e alterações críticas.
- [x] Teste específico da Sprint 10 passou dentro da suíte completa.
- [x] Suíte completa passou com 106 testes/597 asserções após Pint.
- [x] `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build` e `./vendor/bin/pint` foram executados.
- [x] `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.
- [x] Não foram implementadas listas provisórias, reclamações, audiência, atribuição, contratos, pagamentos ou notificações reais.

## Sprint 11

- [x] Backoffice gera lista provisória a partir de snapshot de ranking interno/bloqueado.
- [x] Lista provisória tem entradas com identificador público pseudonimizado.
- [x] Lista provisória exige aprovação antes de publicação.
- [x] Portal público consulta publicação sem nome, email, número de candidatura, documentos ou paths.
- [x] Período de reclamação é configurável e controlado por estado/prazo.
- [x] Candidato cria e submete reclamação apenas sobre a própria entrada.
- [x] Outro candidato não acede nem reclama sobre entrada alheia.
- [x] Backoffice marca receção, inicia análise, decide e aprova decisão de reclamação.
- [x] Pedido de informação complementar e resposta do candidato existem no domínio da reclamação.
- [x] Audiência de interessados pode ser emitida, respondida pelo candidato e revista pelo backoffice.
- [x] Lista definitiva é gerada apenas sem reclamações/audiências pendentes.
- [x] Efeitos de reclamação/audiência são registados em `list_change_logs`.
- [x] Candidaturas em lista definitiva publicada/bloqueada ficam preparadas para Sprint 12.
- [x] Notificações oficiais internas são registadas sem envio real por email/SMS.
- [x] Teste específico da Sprint 11 passou com 6 testes/42 asserções.
- [x] Suíte completa passou com 112 testes/639 asserções após Pint.
- [x] `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build` e `./vendor/bin/pint` foram executados.
- [x] `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.
- [x] Não foram implementados atribuição, sorteio, contratos, pagamentos, notificações externas reais ou alteração automática de ranking.

## Sprint 12

- [x] Backoffice associa habitações a concurso/programa.
- [x] Backoffice configura regras de adequação de tipologia.
- [x] Candidato regista preferências próprias de habitação.
- [x] Outro candidato não altera preferências nem consulta ofertas alheias.
- [x] Execução por ranking usa lista definitiva e cria atribuição, oferta, lista suplente e relatório.
- [x] Habitações indisponíveis/duplicadas são bloqueadas pelo domínio.
- [x] Sorteio guarda participantes, seed, algoritmo, ordem, resultado e hash de auditoria.
- [x] Auditor autorizado consulta auditoria do sorteio sem escrita operacional.
- [x] Candidato aceita oferta e a atribuição fica pronta para contrato.
- [x] Recusa, expiração e desistência ficam modeladas com libertação e chamada de suplente quando configurada.
- [x] Notificações oficiais internas são criadas sem envio real por email/SMS.
- [x] Teste específico da Sprint 12 passou com 6 testes/33 asserções.
- [x] Suíte completa passou com 118 testes/672 asserções após Pint.
- [x] `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build`, `./vendor/bin/pint` e `./vendor/bin/pint --test` foram executados.
- [x] `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.
- [x] Não foram implementados contratos, renda, caução, pagamentos, manutenção ou notificações externas reais.

## Sprint 13

- [x] Existe motor de cálculo de renda configurável por programa/concurso.
- [x] Regra ativa do concurso prevalece sobre regra ativa do programa.
- [x] Renda é calculada com base nos rendimentos declarados do agregado.
- [x] Taxa de esforço, renda mínima, renda máxima e caução são calculadas quando configuradas.
- [x] Cálculo guarda snapshot dos dados usados e detalhes por componente.
- [x] Dados sem rendimento ou método manual geram revisão manual.
- [x] Revisão manual exige justificação e aprovação/rejeição autorizada.
- [x] Minutas e cláusulas contratuais são parametrizáveis por programa/concurso.
- [x] Contrato é criado apenas a partir de atribuição aceite/pronta para contrato e cálculo aprovado.
- [x] Renda e caução do contrato derivam do cálculo aprovado e não de mass assignment.
- [x] Contrato contém arrendatário, habitação, prazo, renda, caução, partes e cláusulas snapshot.
- [x] Documento contratual HTML é gerado em storage privado.
- [x] Download do contrato exige autorização e candidato só vê os próprios contratos.
- [x] Caução fica registada e pode ser marcada como solicitada, paga, dispensada ou cancelada por backoffice autorizado.
- [x] Emissão, validação interna, assinatura/registo manual e ativação contratual funcionam.
- [x] Ativação atualiza o estado da habitação e da habitação associada ao concurso.
- [x] Backoffice exige permissões; candidato é bloqueado no backoffice.
- [x] Auditoria cobre eventos críticos do módulo contratual.
- [x] Teste específico da Sprint 13 passou com 4 testes/49 asserções.
- [x] Suíte completa passou com 122 testes/721 asserções antes da atualização documental final.
- [x] `php artisan migrate`, `php artisan route:list` e `php artisan test` foram executados com sucesso.
- [x] Não foram implementadas cobrança real, faturação, recibos, assinatura digital externa, pagamentos, manutenção ou gestão pós-contratual avançada.
- [x] PDF real está documentado como limitação por ausência de infraestrutura/biblioteca instalada.

## Sprint 14

- [x] Existe conta financeira por contrato ativo.
- [x] O sistema gera plano mensal de rendas e prestações mensais.
- [x] Município regista pagamentos manuais e confirma pagamentos.
- [x] Município imputa pagamentos a prestações específicas ou à dívida mais antiga.
- [x] Sistema atualiza valores pagos, em aberto, saldos e extrato financeiro.
- [x] Sistema gera comprovativo interno em storage privado.
- [x] Candidato consulta apenas a própria situação financeira e comprovativos próprios.
- [x] Outro candidato é bloqueado em dados financeiros alheios.
- [x] Backoffice exige permissões/role interna.
- [x] Sistema deteta incumprimentos por prestações vencidas em aberto.
- [x] Backoffice emite aviso de incumprimento e controla visibilidade ao candidato.
- [x] Backoffice cria acordo de regularização com prestações.
- [x] Alteração de rendimentos pode originar revisão de renda.
- [x] Nova renda só é aplicada após aprovação.
- [x] Pedido anual de atualização documental pode ser solicitado e submetido.
- [x] Auditoria é usada nos eventos críticos do módulo.
- [x] Teste específico da Sprint 14 passou com 4 testes/34 asserções.
- [x] Suíte completa passou com 126 testes/755 asserções.
- [x] `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build`, `./vendor/bin/pint` e `./vendor/bin/pint --test` foram executados com sucesso.
- [x] `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.
- [x] Não foram implementadas cobrança real, faturação fiscal oficial, comunicação à AT, gateway, integração bancária real, email/SMS real, manutenção ou vistorias.

## Sprint 15

- [x] Arrendatário cria pedido de manutenção para contrato ativo próprio.
- [x] Pedido fica associado a habitação, contrato e candidato próprios.
- [x] Pedido tem número único, categoria, urgência, descrição e anexos privados.
- [x] Outro candidato é bloqueado em pedidos e anexos alheios.
- [x] Backoffice consulta, revê, agenda, rejeita, resolve, fecha e cancela pedidos.
- [x] Mudanças de estado ficam registadas em histórico e auditoria.
- [x] Backoffice atribui pedido a técnico interno ou fornecedor registado.
- [x] Backoffice regista intervenção e conclusão técnica.
- [x] Backoffice regista custos e aprova/rejeita custos operacionais.
- [x] Indicadores e relatório de custos existem para backoffice autorizado.
- [x] Backoffice cria vistorias com checklist e tipos previstos.
- [x] Vistorias têm itens, estado, validação, anexos privados e auto HTML privado.
- [x] Candidato consulta apenas vistorias/autos visíveis e próprios.
- [x] Histórico técnico consolidado existe por habitação e respeita visibilidade ao arrendatário.
- [x] Teste específico da Sprint 15 passou com 4 testes/43 asserções.
- [x] Suíte completa passou com 130 testes/798 asserções após Pint.
- [x] `php artisan migrate`, `php artisan route:list`, `php artisan test`, `npm run build`, `./vendor/bin/pint` e `./vendor/bin/pint --test` foram executados.
- [x] `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.
- [x] Não foram implementadas faturação de fornecedores, contratação pública, pagamento a fornecedores, portal de fornecedor, PDF real ou notificações externas reais.

## Sprint 16

- [x] Existe centro de comunicações no backoffice.
- [x] Candidato consulta apenas notificações e comunicações próprias.
- [x] Candidato marca leitura, toma conhecimento quando exigido e arquiva.
- [x] Templates por canal são configuráveis e versionados.
- [x] Versões são aprováveis/ativáveis e versões usadas preservam histórico.
- [x] Variáveis desconhecidas ou em falta são rejeitadas.
- [x] Variáveis sensíveis são bloqueadas em SMS.
- [x] Regras por evento resolvem destinatário, canal e contexto.
- [x] Comunicações guardam número, evento, destinatário, prioridade, estado e snapshot.
- [x] Entregas, tentativas, falhas e reenvios são registados.
- [x] Email sem configuração fica `pending_configuration`.
- [x] SMS sem gateway fica `disabled` e não simula envio.
- [x] Postal pode ser registado manualmente.
- [x] Comprovativos de envio, leitura e tomada de conhecimento ficam privados.
- [x] Modelos documentais e versões são configuráveis.
- [x] Documentos oficiais HTML são numerados, guardados em storage privado e descarregados com autorização.
- [x] Auditor tem leitura sem escrita.
- [x] Teste específico passou com 9 testes/56 asserções.
- [x] Suíte completa passou com 139 testes/854 asserções após Pint.
- [x] `php artisan migrate --force`, `php artisan route:list`, `php artisan test`, `npm run build`, `composer validate`, `php artisan view:cache` e `./vendor/bin/pint` foram executados com sucesso.
- [x] `phpstan` e `psalm` não existem em `vendor/bin`, por isso não foram executados.
- [x] Não foram implementados email certificado, SMS real, postal automático, ViaCTT, assinatura digital, PDF real ou Sprint 17.

## Sprint 17

- [x] Dashboard operacional apresenta indicadores reais dos módulos existentes.
- [x] Dashboard executivo exige permissão específica.
- [x] Candidato e guest são bloqueados no backoffice de reporting.
- [x] Indicadores aceitam filtros e podem criar snapshots auditáveis.
- [x] Catálogo de relatórios cobre candidaturas, elegibilidade, documentos, reclamações, habitação, finanças, manutenção e resumo executivo.
- [x] Execuções guardam filtros, formato, âmbito, utilizador, estado e contagem.
- [x] CSV é guardado em storage privado e descarregado por controller autorizado.
- [x] Exportações sensíveis exigem confirmação e permissões específicas.
- [x] Exportação nominal/completa exige permissão dedicada.
- [x] Consultas, execuções, exportações e downloads ficam registados.
- [x] Campos de sensibilidade, query, estado e path estão protegidos contra mass assignment.
- [x] XLSX e PDF têm fallback explícito e auditado.
- [x] Teste específico passou com 8 testes/44 asserções.
- [x] Suíte completa passou com 147 testes/898 asserções.
- [x] Migration, seeders, routes, Blade, Vite, Composer e Pint foram validados.
- [x] Não foram implementados BI externo, data warehouse, XLSX/PDF nativos ou Sprint 18.

## Definition of Done futura

- Codigo implementado apenas dentro do escopo aprovado.
- Migrations revistas antes de execucao.
- Policies/permissions cobrem novas entidades.
- Testes automatizados relevantes passam.
- Acessibilidade basica verificada.
- Logs/auditoria tratados quando houver dados pessoais ou decisoes.
- Documentacao atualizada.
- Sem secrets em codigo ou documentacao.

## Sprint 18

- [x] Backoffice de segurança/RGPD exige autenticação e role interna.
- [x] A área `/backoffice/security` exige validação MFA para perfis sensíveis.
- [x] MFA guarda secret encriptado e recovery codes hashed.
- [x] Falhas de login geram `access_logs` e podem gerar `security_alerts`.
- [x] Audit trail novo mascara campos sensíveis e bloqueia update/delete pelo model.
- [x] Logs de acesso e logs de acesso sensível existem.
- [x] Revisão de permissões sinaliza perfis privilegiados e backoffice sem MFA.
- [x] Finalidades RGPD e consentimentos existem.
- [x] Candidato cria pedido RGPD próprio e outro candidato é bloqueado.
- [x] Exportação RGPD é privada, com checksum e path técnico sem email/nome.
- [x] Retenção é simulada e execução real exige aprovação.
- [x] Anonimização exige aprovação e executa por service.
- [x] Checklist pré-produção não aprova itens falhados.
- [x] Teste específico da Sprint 18 passou com 7 testes/40 asserções.
- [x] Não foram implementados SIEM, MFA externo, eliminação massiva automática, Autenticação.GOV, email/SMS real ou Sprint 19.

## Sprint 19

- [x] Suite integrada de regressão foi acrescentada sem criar nova funcionalidade de negócio.
- [x] Seeder integrado fictício cobre cenários elegível, inelegível, documentação, aperfeiçoamento, reclamação, atribuição, contrato, finanças, manutenção, relatórios e auditoria.
- [x] Testes unitários determinísticos cobrem elegibilidade, classificação, renda e auditoria.
- [x] Testes Feature cobrem fluxo integrado, matriz de permissões, documentos privados e smoke básico de queries.
- [x] Factories financeiras em falta foram adicionadas para suportar testes e seeders.
- [x] Models financeiros receberam `HasFactory` sem alteração funcional de domínio.
- [x] `php artisan route:list` passou com 830 rotas.
- [x] `php artisan test` passou com 174 testes/1164 asserções.
- [x] `npm run build` passou.
- [x] `composer validate --no-check-publish` passou.
- [x] `php artisan view:cache` passou e `php artisan view:clear` limpou a cache depois da validação.
- [x] `./vendor/bin/pint --test` passou após aplicação de `./vendor/bin/pint`.
- [x] PHPStan/Psalm estão documentados como ausentes.
- [x] Relatório de qualidade, quality gates, matriz de cobertura, plano de regressão, relatório de bugs e revisão de performance foram criados.
- [x] Não foram implementadas funcionalidades de Sprint 20, migração, formação, produção, integrações externas reais ou dados reais.

## Sprint 20

- [x] `/oferta-habitacional` existe e renderiza para guest.
- [x] `/oferta-habitacional/concursos` usa o nome `public.contests.index`.
- [x] `/oferta-habitacional/concursos/{slug}` usa o nome `public.contests.show`.
- [x] `/oferta-habitacional/imoveis` lista habitações publicadas.
- [x] `/oferta-habitacional/imoveis/{slug}` mostra ficha pública.
- [x] `/oferta-habitacional/mapa` devolve marcadores públicos JSON.
- [x] Documentos públicos são descarregados por controller.
- [x] Documentos privados não são descarregáveis pela rota pública.
- [x] Habitações não publicadas não aparecem.
- [x] Mapa não expõe morada completa nem paths internos.
- [x] Backoffice bloqueia candidato e permite administrador.
- [x] Teste específico passou com 6 testes/27 asserções.
- [x] Não foram implementadas candidaturas, elegibilidade, ranking, atribuição, contratos, pagamentos ou manutenção.

## Sprint 24

- [x] Dashboard operacional existe e bloqueia guest/candidato.
- [x] Dashboard executivo existe para backoffice autorizado.
- [x] Relatório por candidatura é gerado em storage privado e inclui texto de validação municipal.
- [x] Dossier documental padronizado é gerado em storage privado.
- [x] Alertas internos podem ser consultados e resolvidos.
- [x] Minutas de procedimento podem ser criadas, publicadas e renderizadas.
- [x] Documentos de procedimento podem ser gerados e aprovados.
- [x] Automação assistida de listas exige revisão/aprovação humana e não publica automaticamente.
- [x] Atas podem ser geradas por minuta e aprovadas.
- [x] Confirmações de processo geram número único e bloqueiam candidato no backoffice.
- [x] Teste específico passou com 5 testes/47 asserções.
- [x] Não foram implementadas integrações externas, decisão automática, publicação automática ou dados reais.

## Sprint 29

- [x] Extração estruturada existe para oito tipos documentais prioritários.
- [x] Campos extraídos são persistidos em `document_ai_fields` com fonte, confiança e estado de revisão.
- [x] JSON estruturado fica persistido em `document_ai_analyses.extraction_json`.
- [x] Eventos de extração não transportam valores pessoais.
- [x] Backoffice de extração bloqueia guest e candidato.
- [x] Valores sensíveis são mascarados para perfis sem auditoria documental.
- [x] Campo extraído pode ser marcado para revisão manual por utilizador autorizado.
- [x] Teste focado passou com 25 testes/142 asserções.
- [x] Não foram implementadas validação cruzada, decisão automática, elegibilidade, pontuação, fraude ou APIs pagas.

## Sprint 30

- [x] `document_ai_validation_runs` e `document_ai_validations` foram criadas.
- [x] Validação IA é executável por documento e por candidatura.
- [x] Extração estruturada agenda job de validação quando há candidatura associada.
- [x] Identificação, rendimentos, habitação e atestado multiusos têm regras documentadas.
- [x] Divergências são classificadas por severidade.
- [x] Flags são criadas para validações que exigem revisão manual.
- [x] Eventos não transportam valores pessoais.
- [x] Backoffice de validação bloqueia guest/candidato.
- [x] Valores sensíveis são mascarados para perfis sem auditoria.
- [x] Dados de saúde ficam ocultos sem permissão adequada.
- [x] Reprocessamento e marcação manual ficam autorizados e auditados.
- [x] Teste focado inicial passou com 8 testes/34 asserções.
- [x] Suite completa passou via `php -d memory_limit=512M vendor/bin/phpunit` com 264 testes/1640 asserções.
- [x] A validação IA não altera candidatura, documento funcional, elegibilidade, pontuação, ranking, listas, contratos ou workflow.
## Sprint 31 — Score de Confiança e Aperfeiçoamento

- Score IA é calculado e persistido por análise documental.
- Indicadores de risco são registados com severidade, origem, confiança e impacto.
- Sugestões são rascunhos internos e não são enviadas automaticamente.
- Candidato não acede ao painel backoffice.
- O painel não expõe OCR bruto, JSON bruto da IA, `extraction_json` ou paths internos.
- O score não altera candidatura, elegibilidade, ranking, workflow, listas ou decisões.
- Eventos e auditoria existem para cálculo, consulta e gestão das sugestões.
- Testes focados da Sprint 31 passam.
