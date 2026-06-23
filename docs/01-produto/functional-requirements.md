# Requisitos funcionais

Este documento descreve requisitos alvo e o respetivo progresso incremental.

## Estado após Sprint 3

Implementado:

- portal público sem autenticação;
- consulta de programas publicados e respetivas regras;
- consulta de concursos publicados, estados e prazos;
- detalhe público por slug, sem exposição de IDs internos;
- FAQ institucional e orientação não vinculativa;
- criação, edição, publicação e eliminação controlada de programas e concursos no backoffice;
- associação de prazos e membros de júri;
- autorização por policies e auditoria de criação, alteração, publicação e eliminação.

Ainda não implementado:

- registo de adesão, perfil candidato e área pessoal da Sprint 4;
- agregado, rendimentos, situação habitacional, documentos e candidatura formal;
- regras de elegibilidade ou qualquer decisão administrativa automática.

## Estado após Sprint 4

Implementado:

- atribuição automática da role `candidate` a novas contas;
- separação entre área do candidato e rotas internas do CRM;
- dashboard candidato com progresso, campos em falta e próximos passos;
- Registo de Adesão único por utilizador;
- dados pessoais base, identificação, contactos, morada e preferências;
- aceitação de termos e informação de tratamento com timestamps;
- guardar rascunho, finalizar, cancelar e remover quando permitido;
- idade mínima de 18 anos na finalização;
- histórico formal de estados e auditoria sem valores pessoais;
- placeholders de candidaturas, documentos e notificações.

Ainda não implementado:

- agregado familiar, membros, rendimentos e situação habitacional;
- candidaturas formais, documentos e notificações reais;
- verificação administrativa ou elegibilidade;
- execução operacional de pedidos RGPD.

## Estado após Sprint 5

Implementado:

- agregado único por Registo de Adesão, com requerente principal sincronizado;
- gestão de membros, relações familiares, dependência, situação profissional e condições declaradas;
- declaração de ausência de rendimentos e rendimentos por membro;
- cálculo mensal/anual por registo, por membro e por agregado;
- situação habitacional atual, residência/trabalho no município, encargos e indicadores de risco;
- dashboard com progresso das quatro áreas, resumos e próximo passo;
- ownership por policy, proteção contra mass assignment e soft deletes;
- auditoria sem valores pessoais ou financeiros nos payloads;
- navegação responsiva por etapas.

Ainda não implementado:

- comprovativos documentais e revisão técnica;
- elegibilidade, pontuação ou decisão administrativa;
- candidatura formal, snapshots ou bloqueio pós-submissão;
- integrações externas ou notificações reais.

## Estado após Sprint 6

Implementado:

- catálogo administrativo de tipos documentais, com código, categoria, sensibilidade, formatos permitidos, tamanho máximo, validade e retenção prevista;
- regras de documentos obrigatórios por programa, concurso, entidade alvo e condição declarativa;
- checklist documental dinâmica para o candidato, calculada a partir do Registo de Adesão, agregado, membros, rendimentos e situação habitacional;
- submissão de documentos pelo candidato em storage privado, sem exposição de path interno;
- associação de documentos ao Registo de Adesão, candidato, membro do agregado, rendimento, situação habitacional e preparação para candidatura futura;
- estados formais `missing`, `submitted`, `under_review`, `validated`, `rejected`, `expired`, `replaced` e `cancelled`;
- substituição de documentos com histórico de versões;
- revisão administrativa base: marcar em análise, validar ou rejeitar com motivo;
- download autorizado por controller/policy e registo de acesso;
- logs de acesso documental e audit logs para upload, substituição, download e decisão de revisão;
- dashboard do candidato com resumo documental e ligação para a checklist.

Ainda não implementado:

- submissão formal de candidatura e número único;
- bloqueio de candidatura por documentos em falta/rejeitados/expirados;
- notificações reais por email/SMS;
- OCR, antivírus, validação automática de autenticidade ou integrações externas;
- prazos formais de correção documental e workflow de aperfeiçoamento.

## Estado após Sprint 8

Implementado:

- CTA de candidatura em concursos públicos abertos;
- área “As minhas candidaturas” protegida e isolada por candidato;
- criação de um rascunho por concurso, dependente de adesão finalizada, agregado, rendimentos e situação habitacional;
- validação documental contextual por programa e concurso;
- bloqueio da submissão perante documentos obrigatórios em falta, rejeitados, expirados ou cancelados;
- revisão final de dados e cinco declarações obrigatórias versionadas;
- número único de candidatura, `submitted_at`, `locked_at`, histórico de estado e auditoria;
- associação dos documentos considerados no momento da submissão;
- snapshots de adesão, agregado, membros, rendimentos, habitação, documentos e resumo;
- comprovativo HTML e versão preparada para impressão;
- consulta inicial read-only no backoffice;
- desistência controlada de candidaturas em rascunho ou submetidas.

Ainda não implementado:

- decisão administrativa final ou pontuação;
- preferências por habitação, porque ainda não existe associação operacional de fogos ao concurso;
- workflow de correção/aperfeiçoamento e transições administrativas;
- PDF assinado, notificações reais, classificação, ranking ou atribuição.

## Backoffice municipal

- Gerir utilizadores internos e externos.
- Atribuir roles e permissions.
- Gerir municipios, configuracoes e parametros operacionais.
- Criar e publicar programas de arrendamento.
- Criar concursos, prazos, regras e membros de juri.
- Gerir candidaturas por estado e fila de trabalho.
- Validar documentos submetidos.
- Executar verificacoes de elegibilidade.
- Rever e aprovar resultados de elegibilidade.
- Calcular classificacao por criterios versionados.
- Gerar rankings e snapshots.
- Publicar listas provisórias e definitivas.
- Gerir reclamacoes e audiencia de interessados.
- Executar atribuicao de fogos.
- Gerir contratos, rendas, caucoes e revisoes.
- Gerir pagamentos, planos e incumprimentos.
- Gerir pedidos de manutencao, vistorias e relatorios.
- Enviar notificacoes com modelos documentais.
- Consultar relatorios e indicadores.
- Consultar auditoria e eventos RGPD.

## Portal publico

- Consultar programas ativos.
- Consultar concursos publicados.
- Consultar prazos e documentos necessarios.
- Simular requisitos basicos sem criar candidatura formal.
- Aceder a listas publicadas com minimizacao de dados.

## Area do candidato

- Criar conta e perfil.
- Registar adesao.
- Atualizar agregado familiar.
- Declarar rendimentos e situacao habitacional.
- Submeter candidatura.
- Submeter documentos.
- Consultar estado do processo.
- Receber notificacoes.
- Submeter reclamacao ou resposta em audiencia quando aplicavel.
- Consultar contrato, pagamentos e pedidos de manutencao quando aplicavel.
- Exercer direitos RGPD por canal definido.

## Área do inquilino

- Consultar contratos pós-atribuição próprios.
- Consultar faturas/rendas operacionais e respetivos estados.
- Consultar pagamentos registados e comprovativos internos quando existirem.
- Submeter pedidos de manutenção associados ao contrato/habitação próprios.
- Acompanhar estado de pedidos, intervenções e vistorias visíveis.
- Abrir e responder a comunicações internas com os serviços municipais.
- Consultar dashboard com contratos, valores em aberto, manutenção, vistorias e comunicações.

## Gestão pós-atribuição pelo município

- Consultar dashboard operacional do senhorio/município.
- Emitir faturas operacionais de inquilino.
- Registar pagamentos manuais e confirmar pagamentos.
- Executar cobranças internas automáticas sem movimento bancário externo.
- Acompanhar pedidos de manutenção e relatórios operacionais.
- Acompanhar vistorias e comunicações com inquilinos.

## Requisitos de processo

- Cada candidatura deve ter estados formais.
- Transicoes de estado devem ser autorizadas por role/policy.
- Decisoes administrativas devem exigir motivo, utilizador, data e contexto.
- Publicacoes devem ser registadas com snapshot.
- Exportacoes devem exigir permissao e ficar auditadas.
- Documentos sensiveis devem ter controlo de acesso e logs de leitura.
- Prazos devem ser configuraveis por concurso.
- Regras devem ser versionadas para preservar historico.

## Requisitos nao funcionais

- Segurança por menor privilegio.
- Auditabilidade integral dos atos relevantes.
- RGPD por desenho.
- Preparacao para multi-municipio.
- Testabilidade por modulos.
- Interfaces responsivas.
- Separacao de ambiente local, teste, staging e producao.
- Backups e recuperacao definidos antes de producao.

## Fora de ambito da Sprint 0

- Implementar candidatura.
- Implementar elegibilidade.
- Implementar classificacao.
- Implementar atribuicao.
- Implementar contratos avancados.
- Implementar pagamentos avancados.
- Implementar manutencao avancada.
- Alterar rotas, controllers, models, migrations, policies, views ou dependencias.

## Fora de âmbito confirmado na Sprint 3

- Não foi criada candidatura formal.
- Não foram recolhidos dados pessoais do candidato.
- Não foi implementado upload documental.
- Não foi implementado motor de elegibilidade, classificação ou atribuição.

## Fora de âmbito confirmado na Sprint 6

- Não foi criada candidatura formal.
- Não foi implementado motor de elegibilidade.
- Não foi implementada pontuação, ranking, listas, reclamações ou atribuição.
- Não foram criadas notificações reais por email/SMS.
- Não foram criadas integrações com AT, Segurança Social, Autenticação.GOV, OCR, antivírus ou assinatura digital.

## Implementação funcional da Sprint 7

- O candidato autenticado seleciona um programa ou concurso com regras ativas e executa uma pré-verificação indicativa.
- O resultado apresenta mensagens simples, critérios cumpridos, falhados, não aplicáveis ou sujeitos a análise e CTAs para corrigir dados.
- O backoffice autorizado gere conjuntos de regras e critérios, ativa, arquiva e duplica versões e consulta o detalhe técnico dos checks.
- Regras específicas de concurso prevalecem sobre regras gerais do programa.
- O motor integra adesão, maioridade, prazo, agregado, rendimentos, habitação, documentos e candidatura duplicada quando o critério está ativo.
- Checks formais podem ser executados para candidaturas submetidas sem mudar automaticamente o respetivo estado.
- Elegibilidade permanece separada de classificação, pontuação, ranking e decisão administrativa final.

## Implementação funcional da Sprint 9

- Backoffice autorizado recebe candidaturas submetidas e cria um processo administrativo único por candidatura.
- O processo administrativo tem número único, estado formal, técnico responsável opcional e histórico de transições.
- Técnicos podem iniciar triagem, análise documental e análise de requisitos, criar análises e registar itens.
- Técnicos podem criar e emitir pedidos de aperfeiçoamento com prazo, mensagem, instruções e itens obrigatórios.
- Candidato consulta apenas pedidos visíveis e próprios, responde por item com texto e/ou documento já existente no sistema documental.
- Backoffice analisa respostas, aceita, rejeita ou solicita mais informação sem alterar documentos fora do módulo documental.
- Decisões de admissão/não admissão exigem fundamentação, ator e timestamp.
- Apenas processos admitidos para classificação ficam disponíveis para a Sprint 10 através de scope próprio.
- Não foram implementados pontuação, ranking, listas, atribuição, notificações reais ou integrações externas.

## Implementação funcional da Sprint 10

- Backoffice autorizado configura matrizes de classificação por programa ou concurso.
- A matriz ativa do concurso prevalece sobre a matriz ativa geral do programa.
- Critérios podem ser automáticos, manuais, ponderados, exclusionary, ativos/inativos e ordenados.
- Regras de pontuação permitem intervalos, limites, booleanos e valores configuráveis.
- Regras de desempate são aplicadas por prioridade após `total_score desc`.
- A execução de classificação considera apenas candidaturas com processo administrativo `admitted_for_scoring`.
- Quando existe elegibilidade, só candidaturas com último check `eligible` são pontuadas por defeito.
- Cada execução preserva histórico em `ScoringRun`, `ApplicationScore`, detalhes por critério e `RankingSnapshot`.
- Pontuação manual é autorizada, limitada por `max_points`, auditada e bloqueável.
- Candidato não vê pontuação, posição, ranking interno ou critérios técnicos nesta sprint.
- Não foram implementadas listas provisórias, reclamações, audiência, listas definitivas, atribuição, contratos ou pagamentos.

## Implementação funcional da Sprint 11

- Backoffice autorizado gera listas provisórias a partir de snapshots de ranking internos ou bloqueados.
- Lista provisória passa por revisão, aprovação, publicação, abertura e fecho de período de reclamação.
- Portal público consulta publicações aprovadas com dados anonimizados por identificador público.
- Candidato autenticado consulta o seu enquadramento e submete reclamação apenas sobre a própria entrada, dentro do prazo ativo.
- Reclamações podem referenciar documentos já submetidos, sem nova lógica documental paralela.
- Backoffice marca receção, inicia análise, cria revisões, solicita informação complementar e decide reclamações com fundamentação.
- Candidato responde a pedidos de informação complementar e submete pronúncia em audiência quando convocado.
- Audiências são emitidas, respondidas, revistas e encerradas com estados formais.
- Lista definitiva é gerada apenas após fecho da lista provisória e sem reclamações/audiências pendentes.
- Alterações decorrentes de reclamações e audiências ficam registadas em `list_change_logs`.
- Notificações oficiais internas/in-app ficam registadas, mas não há envio real por email/SMS.
- Atribuição de habitações, sorteios, contratos, pagamentos e notificações externas reais não foram implementados.

## Implementação funcional da Sprint 12

- Backoffice autorizado associa habitações existentes a programas/concursos e controla disponibilidade operacional.
- Backoffice configura regras de adequação por composição do agregado, tipologia, quartos e acessibilidade.
- Candidato consulta e regista preferências de habitação apenas nas próprias candidaturas prontas para atribuição.
- Atribuição usa listas definitivas aprovadas, publicadas ou bloqueadas, sem usar ranking interno bruto.
- O motor suporta métodos por ranking, preferências e sorteio auditável.
- Cada execução cria `AllocationRun`, `Allocation`, ofertas quando exigidas, lista suplente e relatório preliminar.
- Sorteio preserva participantes, seed, algoritmo, ordem, resultado e hash de auditoria.
- Candidato consulta apenas as próprias ofertas/atribuições e pode aceitar, recusar com motivo ou desistir.
- Aceitação marca a atribuição como `ready_for_contract` e prepara a Sprint 13.
- Recusa, expiração e desistência libertam a habitação e chamam suplente quando a regra assim define.
- Notificações são registos oficiais internos/in-app; não há envio real por email/SMS.
- Não foram implementados contrato, cálculo de renda, caução, pagamentos, manutenção, assinatura digital ou comunicações externas reais.

## Implementação funcional da Sprint 13

- Backoffice autorizado configura conjuntos de regras de renda por programa ou concurso.
- Regra de concurso prevalece sobre regra geral do programa quando ambas estão ativas.
- O cálculo de renda considera rendimentos declarados do agregado, taxa de esforço, mínimos, máximos e caução configurada.
- Cada cálculo guarda snapshot dos dados usados e detalhes dos componentes aplicados.
- Rendimentos inexistentes ou método manual geram `requires_manual_review`.
- Revisão manual exige justificação, valor proposto e aprovação/rejeição autorizada.
- Minutas contratuais e cláusulas são parametrizáveis por programa/concurso.
- Contrato processual é criado apenas a partir de atribuição aceite/pronta para contrato e cálculo aprovado.
- Renda mensal e caução do contrato são derivadas do cálculo aprovado, não de valores enviados diretamente pelo formulário.
- Contrato contém arrendatário, habitação, programa, concurso, prazo, renda, caução, partes e cláusulas snapshot.
- Documento contratual HTML é gerado em storage privado e descarregado apenas por controller autorizado.
- Contrato suporta estados de preparação, emissão, assinatura manual/registada, ativação, suspensão, cessação e cancelamento.
- Ativação exige documento gerado, validação interna aprovada, assinatura/registo manual e caução paga ou dispensada.
- Ativação marca a habitação como ocupada e a habitação do concurso como aceite.
- Candidato consulta apenas os próprios contratos, documentos e caução.
- Notificações oficiais continuam internas/in-app.
- Não foram implementados cobrança real, faturação, recibos, reconciliação financeira, assinatura digital externa, pagamentos ou manutenção.

## Implementação funcional da Sprint 14

- Backoffice autorizado cria ou localiza conta financeira para contrato ativo.
- O sistema gera planos mensais de renda e prestações com referência única, vencimento, valor pago e valor em aberto.
- Gestor financeiro regista pagamento manual, confirma e imputa a prestação específica ou à dívida mais antiga.
- O sistema recalcula saldos, totais emitidos, pagos, vencidos e dispensados.
- Comprovativos internos são gerados em storage privado e descarregados apenas por utilizador autorizado.
- Importação CSV cria lote, linhas, correspondência por referência da prestação e pagamentos internos quando processado.
- Deteção de incumprimento identifica prestações vencidas em aberto, cria `Arrear` e marca a prestação como em atraso.
- Backoffice emite aviso de incumprimento visível ao candidato apenas após emissão.
- Backoffice cria acordo de regularização com prestações e associa incumprimentos ao acordo.
- Backoffice cria, calcula, aprova e aplica revisão de renda; a aplicação cria novo plano de renda e atualiza a renda contratual.
- Candidato consulta apenas a própria situação financeira, pagamentos, comprovativos, avisos, acordos e revisões.
- Candidato declara alteração de rendimentos; backoffice aceita/rejeita e, quando aceite, abre revisão de renda.
- Backoffice solicita atualização documental anual; candidato submete referências a documentos próprios existentes.
- Não foram implementados cobrança real, recibo fiscal oficial, comunicação à AT, gateway, integração bancária real, email/SMS real, manutenção ou vistorias.

## Implementação funcional da Sprint 15

- Arrendatário autenticado cria pedido de manutenção apenas para contrato ativo próprio.
- Pedido de manutenção recebe número único, categoria, urgência, localização, descrição e anexos privados opcionais.
- Candidato consulta apenas os seus pedidos, anexos autorizados, vistorias visíveis e histórico técnico visível.
- Backoffice autorizado consulta pedidos, classifica urgência técnica, agenda, rejeita, resolve, fecha ou cancela.
- Mudanças de estado de manutenção são registadas em histórico próprio e audit log.
- Backoffice atribui pedidos a técnico interno ou fornecedor registado, sem disponibilizar área de fornecedor.
- Backoffice regista intervenções, conclusão técnica, custos, aprovação/rejeição de custos e relatório operacional.
- Backoffice cria vistorias iniciais, periódicas, finais, extraordinárias ou de manutenção com checklist configurável.
- Vistorias têm itens, fotografias/anexos privados, conclusão, validação e auto HTML privado.
- Autos de vistoria e anexos são descarregados apenas por controllers autorizados, sem exposição de paths internos.
- Histórico técnico do imóvel consolida pedidos, intervenções e vistorias com visibilidade separada para candidato/backoffice.
- Notificações permanecem internas/in-app; não há email/SMS/carta registada, faturação de fornecedor, contratação pública, pagamento a fornecedor ou integração externa.

## Implementação funcional da Sprint 16

- Candidato consulta notificações próprias, marca leitura, regista tomada de conhecimento e arquiva notificações.
- Candidato consulta o histórico das próprias comunicações, documentos oficiais autorizados e preferências de contacto.
- Backoffice autorizado consulta dashboard, histórico, estados, entregas, tentativas, erros e comprovativos.
- Backoffice gere templates por canal, versões, variáveis e regras de comunicação por evento.
- Alterar um template cria nova versão; uma versão usada mantém o snapshot histórico.
- Eventos críticos podem gerar múltiplas comunicações para destinatários e canais configurados.
- Notificações criadas pelos módulos existentes passam pelo `OfficialNotificationService`, que cria também comunicação, entrega, tentativa e comprovativo in-app.
- Email sem mailer externo válido fica `pending_configuration`; não é marcado como enviado.
- SMS sem gateway fica `disabled`, com tentativa `skipped`; nenhum SMS real é enviado.
- Postal é preparado e o envio/comprovativo são registados manualmente.
- Backoffice gere modelos documentais versionados e gera documentos oficiais HTML privados.
- Downloads de comprovativos e documentos passam por controller e policy.
- Templates demo usam apenas dados fictícios e aviso de validação municipal/jurídica.

## Implementação funcional da Sprint 17

- Utilizador interno autorizado consulta dashboard operacional com métricas reais dos módulos existentes.
- O dashboard executivo exige `reports.view_executive` e apresenta apenas informação agregada.
- Relatórios e indicadores aceitam filtros de período, programa, concurso, estado e localização.
- Definições de indicadores e relatórios referenciam apenas serviços e métodos incluídos em allowlists.
- Execuções guardam filtros, âmbito, formato, estado, utilizador, datas e número de linhas.
- Exportações ficam em storage privado e downloads passam por controller, Policy e log próprio.
- Relatórios sensíveis mostram aviso e exigem confirmação explícita antes da exportação.
- Âmbitos nominais ou completos exigem `reports.export_nominal`.
- CSV é gerado nativamente com proteção contra CSV formula injection.
- XLSX usa fallback auditado para CSV e PDF usa fallback para HTML imprimível enquanto não houver bibliotecas instaladas.
- Candidato não acede ao módulo de reporting.
- Não foram implementados BI externo, data warehouse, dados pessoais em dashboards ou Sprint 18.

## Implementação funcional da Sprint 18

- Backoffice autorizado consulta painel de segurança e RGPD.
- Backoffice configura e valida MFA antes de aceder a operações sensíveis da área de segurança.
- O sistema regista falhas de login e cria alertas quando a regra ativa atinge o limiar.
- O sistema mantém audit trail append-only e mascara chaves sensíveis em old/new values e metadata.
- Município consulta logs de acesso, logs de acesso sensível e eventos de auditoria.
- Município cria revisões de permissões e conclui a análise com findings/recomendações.
- Município gere finalidades de tratamento e políticas de retenção.
- Município regista, atribui, conclui, rejeita e exporta pedidos RGPD do titular.
- Exportações RGPD são JSON em storage privado com checksum e nome técnico sem dados pessoais no path.
- Retenção suporta simulação e execução controlada dependente de aprovação; não há eliminação massiva automática nesta sprint.
- Anonimização exige aprovação e mascara perfil de utilizador quando o scope o permite.
- Município consulta registry de campos sensíveis e campos bloqueados por requisitos de pesquisa/login.
- Município regista revisões de backup e gere checklist pré-produção.
- Candidato consulta finalidades, consentimentos, pedidos RGPD próprios e exportações próprias.
- Candidato não acede a pedidos, consentimentos ou exportações de outros candidatos.
# Atualização Sprint 19 — Qualidade

A Sprint 19 não introduziu requisitos funcionais novos. A entrega acrescentou validação automatizada e documental dos requisitos já implementados:

- fluxo integrado de candidatura até auditoria/RGPD com dados fictícios;
- permissões por role e bloqueio de acessos indevidos;
- storage privado e auditoria de documentos;
- cálculos determinísticos de elegibilidade, pontuação e renda;
- plano de regressão e quality gates antes de migração/produção.

Qualquer novo requisito funcional deve continuar a ser tratado em sprint própria e não por testes de regressão.

## Implementação funcional da Sprint 20

- Cidadão consulta a oferta habitacional em `/oferta-habitacional`.
- Cidadão filtra habitações por pesquisa, tipologia, freguesia, estado público, renda, concurso e estado de concurso.
- Cidadão consulta concursos publicados em `/oferta-habitacional/concursos`.
- Cidadão consulta ficha pública de habitação em `/oferta-habitacional/imoveis/{slug}`.
- Ficha pública mostra apenas dados editoriais autorizados, localização pública, características, galeria e documentos públicos.
- Download de brochuras/fichas públicas passa por controller e exige publicação válida.
- Backoffice autorizado configura a publicação pública de uma habitação.
- Backoffice autorizado gere imagens, documentos, settings e links institucionais.
- Mapa público usa endpoint JSON e fallback operacional sem dependência externa.
- Não foram implementadas candidaturas, elegibilidade, classificação, listas, atribuição, contratos ou pagamentos.
## Atualização Sprint 21 — Simulador avançado

- O cidadão pode executar uma simulação indicativa antes de iniciar candidatura formal.
- O candidato autenticado pode guardar simulações no histórico da área pessoal.
- O sistema recomenda tipologia, renda estimada e concursos publicados compatíveis.
- O sistema identifica impedimentos declarados e dados em falta.
- O candidato pode converter uma simulação em pré-preenchimento controlado de rascunho.
- O candidato pode renovar dados base do Registo de Adesão sem recriar todo o processo.
- O backoffice pode consultar estatísticas agregadas e configurar parâmetros gerais do simulador.
- A simulação não substitui elegibilidade formal, validação documental, classificação, renda contratual ou decisão administrativa.

## Implementação funcional da Sprint 24

- Utilizador interno autorizado consulta dashboard operacional com métricas de candidaturas, documentos, prazos, visitas, tickets e alertas.
- Utilizador interno autorizado consulta dashboard executivo com indicadores agregados para decisão municipal.
- Backoffice gera relatório individual por candidatura em storage privado.
- Backoffice gera dossier documental padronizado, com identificação de documentos em falta, rejeitados, expirados e validados.
- Backoffice consulta, resolve ou dispensa alertas internos.
- Backoffice cria, edita e publica minutas de procedimento.
- Backoffice gera documentos a partir de minutas, preservando payload e conteúdo snapshot.
- Backoffice executa automação assistida de listas provisórias/definitivas, sem publicação automática.
- Backoffice gera atas a partir de minutas e aprova após revisão humana.
- Backoffice gera confirmação automática com número de processo único.
- Candidato não acede ao backoffice operacional.
- Não foram implementadas decisões automáticas, publicação automática de listas, envio externo real ou integrações externas.

## Implementação funcional da Sprint 28

- Sempre que um documento submetido tem análise documental pendente, a queue pode executar OCR local controlado.
- O sistema classifica automaticamente o tipo provável do documento com OCR, palavras-chave, layout e IA local opcional.
- O backoffice consulta Documento, Classificação IA, Confiança, Estado e OCR disponível.
- O backoffice pode marcar uma classificação para revisão manual.
- Texto OCR sensível fica oculto por defeito e exige permissão de auditoria documental.
- A classificação automática não valida, rejeita, aprova, exclui, pontua ou altera candidaturas.
- Não foram usadas APIs pagas ou serviços externos de OCR/IA.

## Implementação funcional da Sprint 29

- Após OCR e classificação, o sistema extrai campos estruturados para oito tipos documentais prioritários.
- O backoffice consulta campos extraídos, valores normalizados, fonte, confiança e revisão manual.
- Campos sensíveis são mascarados quando o utilizador não tem permissão de auditoria documental.
- Dados de saúde podem ficar ocultos sem permissão adequada.
- Técnicos autorizados podem marcar campos para revisão manual.
- Valores extraídos não preenchem nem alteram automaticamente candidatura, agregado, rendimentos, elegibilidade, pontuação, contratos ou workflows.
- A extração usa regex determinístico e IA local Ollama opcional, sem APIs pagas.

## Implementação funcional da Sprint 30

- Após extração estruturada, documentos associados a candidatura podem gerar validação IA em queue.
- O sistema cruza dados declarados da candidatura, adesão, agregado, rendimentos e situação habitacional com campos extraídos.
- O backoffice consulta execuções, grupos, severidade, confiança, mensagens e recomendações.
- O backoffice pode reprocessar uma candidatura e marcar validações para revisão manual.
- Valores sensíveis são mascarados para utilizadores sem permissão de auditoria documental.
- Dados de saúde ficam ocultos sem permissão de privacidade/auditoria.
- Divergências críticas criam flags e eventos técnicos.
- A validação IA não altera candidatura, documentos funcionais, elegibilidade, pontuação, ranking, listas, contratos ou workflow.
- Não foram usadas APIs pagas, integrações externas ou decisões automáticas.
