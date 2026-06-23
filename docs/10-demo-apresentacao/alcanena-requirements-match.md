# Matriz Requisitos vs Plataforma — Municipio de Alcanena

## Objetivo

Comparar os requisitos de referencia da plataforma digital com o estado atual da MV HAB, preparando a execucao da Sprint 32 e a apresentacao ao Municipio de Alcanena.

Legenda:

- ✅ Sim: implementado ou demonstravel com seguranca.
- ⚠️ Parcial: existe base funcional, mas requer acabamento, dados demo, validacao visual ou integracao futura.
- ❌ Nao: nao implementado no estado atual ou deliberadamente fora do escopo imediato.

| Experiencia | Ponto | Detalhe | Implementacao MV HAB | Cumpre |
| --- | --- | --- | --- | --- |
| Candidato | Conhecer oferta | Link a partir do site da Camara | Portal publico existe; pode ser ligado pelo municipio. | ✅ Sim |
| Candidato | Conhecer oferta | Concursos abertos e futuros | Existem paginas publicas de concursos e detalhe de concurso. | ✅ Sim |
| Candidato | Conhecer oferta | Mapa dinamico com localizacao dos fogos | Existe endpoint/pagina publica de mapa para oferta habitacional; deve ser validado com dados demo de Alcanena e fallback sem servico externo. | ⚠️ Parcial |
| Candidato | Conhecer oferta | Filtros por freguesia, tipologia e rendas | Interface pública expõe filtros por freguesia, tipologia, renda mínima/máxima, estado e ordenação. | ✅ Sim |
| Candidato | Conhecer oferta | Brochura digital por empreendimento/fogo | Ficha pública do fogo inclui brochura HTML imprimível com dados essenciais e nota de localização. | ✅ Sim |
| Candidato | Registo e elegibilidade | Criacao de utilizador no registo de adesao | Fluxos de autenticacao, area reservada e adesao existem nas sprints anteriores. | ✅ Sim |
| Candidato | Registo e elegibilidade | Formulario e acesso condicionado | Existe area protegida para candidato e fluxos de adesao/candidatura. | ✅ Sim |
| Candidato | Registo e elegibilidade | Simulador de elegibilidade | Simulador existe e foi evoluido nas sprints recentes. | ✅ Sim |
| Candidato | Registo e elegibilidade | Avancar para candidatura se elegivel | Fluxo de candidatura existe; a elegibilidade automatica nao deve substituir decisao administrativa final. | ✅ Sim |
| Candidato | Candidaturas e visitas | Formulario de candidatura | Fluxo de candidatura formal existe. | ✅ Sim |
| Candidato | Candidaturas e visitas | Anexar documentos | Gestao documental privada existe. | ✅ Sim |
| Candidato | Candidaturas e visitas | Inconsistencias entre dados e simulador | Existem modulos de inconsistencias/document AI e validacao cruzada; apresentar como apoio tecnico, nao decisao automatica. | ✅ Sim |
| Candidato | Candidaturas e visitas | Marcar ou adiar visitas | Existem rotas e modulos de visitas; devem ser validados com dados demo. | ⚠️ Parcial |
| Candidato | Candidaturas e visitas | Visibilidade sobre renda mensal | Concursos/fogos suportam renda; demonstrar no detalhe publico e candidatura. | ✅ Sim |
| Candidato | Candidaturas e visitas | Linha de apoio | Existem tickets de apoio; canal real depende da operacao municipal. | ⚠️ Parcial |
| Candidato | Acompanhamento | Estados de candidatura | Estados e acompanhamento processual existem. | ✅ Sim |
| Candidato | Acompanhamento | Audiencia previa e concurso | Workflow administrativo, audiencia e reclamacoes existem. | ✅ Sim |
| Candidato | Acompanhamento | Desistir da candidatura | Fluxos de candidatura incluem estados; validar acao visivel no roteiro. | ⚠️ Parcial |
| Candidato | Acompanhamento | Submeter documentos de audiencia/recurso | Existem fluxos documentais e administrativos; validar caso demo. | ✅ Sim |
| Candidato | Acompanhamento | Guardar dados para nova candidatura | A area pessoal e registo de adesao permitem reutilizacao de dados base. | ✅ Sim |
| Candidato | Acompanhamento | Notificacoes automaticas e FAQ | Notificacoes/modelos existem; canais reais externos devem ser apresentados como configuraveis. | ⚠️ Parcial |
| Candidato | Acompanhamento | Ticket de apoio | Modulo de suporte existe. | ✅ Sim |
| Candidato | Fecho do processo | Notificacoes e alertas | Existe infraestrutura de notificacoes; entrega real por canais externos depende de configuracao. | ⚠️ Parcial |
| Candidato | Fecho do processo | Assinatura digital do contrato | Nao deve ser apresentado como concluido; depende de integracao/decisao futura. | ❌ Nao |
| Candidato | Fecho do processo | Transicao automatica para area de inquilino | Area de inquilino existe apos atribuicao/contrato; validar fluxo demo. | ⚠️ Parcial |
| Candidato | Fecho do processo | Agendamento de entrega de chaves | Pode ser enquadrado em operacao/agenda; nao deve ser vendido como modulo completo se nao estiver demonstravel. | ⚠️ Parcial |
| Inquilino | Area pessoal | Contratos e faturas | Area do inquilino existe com gestao pos-atribuicao; fatura digital depende do modulo financeiro/operacional. | ⚠️ Parcial |
| Inquilino | Area pessoal | Vistorias e manutencao | Modulos de vistorias e manutencao existem. | ✅ Sim |
| Inquilino | Area pessoal | Comunicacoes | Comunicacoes/notificacoes existem; canais externos sao roadmap. | ⚠️ Parcial |
| Inquilino | Area pessoal | Pagamentos digitais | Registo/gestao de pagamentos existe; gateway bancario real nao deve ser apresentado como concluido. | ⚠️ Parcial |
| Backoffice | Inicio do procedimento | Upload de edital | Gestao documental e concursos suportam documentos/minutas. | ✅ Sim |
| Backoffice | Inicio do procedimento | Criacao de concursos | Modulo de concursos existe. | ✅ Sim |
| Backoffice | Inicio do procedimento | Geracao/gestao de minutas | Modulos de modelos documentais e comunicacoes existem. | ✅ Sim |
| Backoffice | Inicio do procedimento | Alertas de assinatura | Apresentar como notificacao/modelo; assinatura qualificada fica no roadmap. | ⚠️ Parcial |
| Backoffice | Analise | Dashboard de metricas | Dashboards e relatorios existem; confirmar indicadores demo. | ✅ Sim |
| Backoffice | Analise | Pontuacao/pre-validacao automatica | Elegibilidade, classificacao/ranking e IA documental de apoio existem. | ✅ Sim |
| Backoffice | Analise | Relatorio por candidatura | Relatorios e acompanhamento processual existem; confirmar caso demo. | ✅ Sim |
| Backoffice | Analise | Standardizacao de ficheiros | Gestao documental e Document Intelligence existem; apresentar como apoio e nao decisao autonoma. | ✅ Sim |
| Backoffice | Audiencia | Primeira auditoria admitidos/excluidos | Workflow, listas, reclamacoes e audiencia existem. | ✅ Sim |
| Backoffice | Audiencia | Segunda auditoria/lista fundamentada | Fluxos de listas e respostas existem; validar roteiro. | ⚠️ Parcial |
| Backoffice | Audiencia | Analise de respostas | Modulo administrativo suporta aperfeicoamento/reclamacoes. | ✅ Sim |
| Backoffice | Sorteios e ordenacao | Gerir sorteios | Modulo de sorteios/ordenacao existe. | ✅ Sim |
| Backoffice | Sorteios e ordenacao | Notificar candidatos | Infraestrutura de notificacoes existe; canais externos reais dependem de configuracao. | ⚠️ Parcial |
| Backoffice | Relatorio final | Relatorio final do procedimento | Relatorios e documentos administrativos existem; validar template demo. | ⚠️ Parcial |
| Backoffice | Relatorio final | Assinatura digital | Fora do escopo atual, roadmap de integracoes. | ❌ Nao |
| Senhorio/Municipio | Area operacional | Dashboards de pagamentos/manutencao | Existem modulos financeiros, manutencao, vistorias e relatorios. | ✅ Sim |
| Senhorio/Municipio | Area operacional | Planeamento e resolucao de intervencoes | Modulo de manutencao/vistorias existe. | ✅ Sim |
| Senhorio/Municipio | Area operacional | Comunicacao com inquilino | Infraestrutura de notificacoes/comunicacoes existe. | ⚠️ Parcial |

## Conclusao operacional

A MV HAB esta bem posicionada para uma demonstracao municipal focada no ciclo processual e administrativo. A Sprint 32 melhorou a oferta publica, dados demo de Alcanena, filtros e brochura simples. A principal atencao antes da apresentacao presencial deve ir para ensaio do roteiro, utilizadores demo e decisao sobre criar candidaturas/listas ficticias pre-carregadas.
