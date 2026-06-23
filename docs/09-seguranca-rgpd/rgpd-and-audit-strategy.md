# Estrategia RGPD e auditoria

## Atualização Sprint 26 — Pós-atribuição

Dados tratados: identificação indireta do inquilino por `user_id` e contrato, dados financeiros operacionais, comunicações, pedidos de manutenção e vistorias associados à habitação.

Medidas aplicadas:

- isolamento por utilizador nas rotas de inquilino;
- backoffice protegido por roles municipais;
- documentos e anexos continuam em storage privado quando aplicável;
- auditoria em emissão de faturas, registo/confirmação de pagamentos e comunicações;
- ausência de dados pessoais reais e de integrações externas.

Pendências RGPD: validar retenção de faturas operacionais e comunicações pós-atribuição, textos formais de cobrança e exportação/eliminação específica da área do inquilino.

Este documento define a estratégia alvo. A Sprint 1 criou a base de auditoria e a Sprint 3 passou a utilizá-la nos eventos críticos de programas e concursos.

## Aplicação na Sprint 3

- As páginas públicas apresentam apenas dados institucionais de municípios, programas, concursos e prazos.
- Não são recolhidos nem apresentados dados pessoais de candidatos.
- Membros de júri não são apresentados publicamente.
- Rascunhos, registos arquivados, soft deletes e campos administrativos não são expostos.
- Criação, alteração, publicação e eliminação de programas/concursos geram audit logs.
- Os logs guardam contexto técnico, mas não incluem passwords, tokens ou documentos.
- O seeder da sprint usa apenas conteúdo institucional fictício e domínio de email reservado.
- Consentimentos, informação ao titular e finalidades associadas ao registo de adesão permanecem pendentes para a Sprint 4 e validação jurídica.

## Aplicação na Sprint 4

- A recolha está limitada a dados base necessários para preparar a adesão.
- Termos e confirmação da informação de tratamento são guardados com timestamps.
- Os textos identificam a finalidade de gestão da adesão e preparação de futuras candidaturas.
- Preferências de comunicação são guardadas, mas não originam envios reais.
- Os audit logs guardam estados, percentagem e nomes dos campos alterados, não os valores pessoais.
- O candidato não vê dados de outros candidatos.
- A remoção marca o estado `removed`, guarda histórico e aplica soft delete.
- A eliminação direta da conta é bloqueada quando existe histórico, evitando apagar dados processuais sem política.
- A base legal, texto definitivo de informação, retenção e pedidos dos titulares continuam sujeitos a validação municipal/DPO e à Sprint 18.

## Minimizacao de dados

- Recolher apenas dados necessarios a fase atual do processo.
- Antes da candidatura formal, limitar recolha a dados de adesao/simulacao.
- Separar dados de identificacao, dados do agregado, rendimentos, documentos e decisoes.
- Evitar duplicacao de documentos quando uma referencia validada for suficiente.
- Mascarar dados em listas publicas, relatorios e ecras sem necessidade operacional.

## Base legal do tratamento

Bases legais a confirmar com o municipio e DPO:

- execucao de missao de interesse publico;
- cumprimento de obrigacoes legais;
- diligencias pre-contratuais e execucao de contrato quando aplicavel;
- consentimento apenas para finalidades nao cobertas por outra base legal;
- interesse legitimo apenas quando juridicamente validado.

## Finalidades de tratamento

- gestao de adesao;
- simulacao e informacao ao candidato;
- instrucao de candidatura;
- validacao documental;
- verificacao de elegibilidade;
- classificacao e ranking;
- publicacao de listas;
- gestao de reclamacoes e audiencia;
- atribuicao de habitacao;
- celebracao e gestao de contrato;
- cobranca de rendas e gestao de incumprimentos;
- manutencao e vistorias;
- comunicacoes oficiais;
- auditoria, conformidade e relatorios.

## Consentimentos

- Registar consentimentos apenas quando forem base legal adequada.
- Guardar finalidade, texto apresentado, versao, data, estado e retirada.
- Permitir retirada quando legalmente possivel.
- Nao misturar consentimento com aceitacao de termos operacionais obrigatorios.

## Direitos dos titulares

A plataforma deve preparar suporte para:

- acesso aos dados;
- retificacao;
- apagamento quando legalmente admissivel;
- limitacao do tratamento;
- oposicao quando aplicavel;
- portabilidade quando aplicavel;
- informacao sobre finalidades, prazos e entidades envolvidas.

Cada pedido deve ter estado, prazo, responsavel, decisao e resposta final registada.

## Retencao documental

- Definir politicas por tipo documental e finalidade.
- Bloquear eliminacao quando exista obrigacao legal, contencioso ou auditoria ativa.
- Encerrar processos com checklist de pendencias.
- Rever documentos expirados por rotina controlada.
- Documentos de candidatos nao selecionados devem ter retencao propria, diferente de contratos ativos.

## Anonimizacao

- Usar anonimização quando a finalidade estatistica nao exigir identificacao.
- Preservar metricas agregadas sem dados pessoais.
- Garantir que identificadores indiretos nao permitam reidentificacao facil.

## Eliminacao

- Eliminar apenas por politica aprovada.
- Registar pedido, base, responsavel, data e resultado.
- Preferir soft delete apenas quando houver razao operacional; para RGPD pode ser necessario apagamento efetivo ou anonimizacao.
- Nunca eliminar audit logs necessarios a obrigacoes legais sem politica formal.

## Exportacao de dados

- Exportacoes devem exigir permissao `export`.
- Exportacoes devem registar modulo, filtros, finalidade, utilizador, data, IP e formato.
- Exportacoes de dados sensiveis devem ser minimizadas.
- Ficheiros exportados devem ter validade, protecao e canal seguro.

## Logs de acesso

Registar:

- utilizador;
- entidade acedida;
- finalidade ou contexto;
- data/hora;
- IP e user agent;
- resultado da autorizacao.

Logs de leitura devem ser obrigatorios para documentos sensiveis, dados financeiros, dados de rendimentos e processos RGPD.

## Logs de alteracao

Registar:

- entidade;
- campo alterado;
- valor anterior e novo valor quando permitido;
- utilizador;
- data/hora;
- motivo quando aplicavel.

Valores sensiveis podem exigir mascaramento no proprio log.

## Logs de decisao administrativa

Registar:

- decisao;
- fundamento;
- regra ou criterio aplicado;
- utilizador/perfil;
- data/hora;
- versao de regras;
- documentos considerados;
- estado anterior e seguinte.

Decisoes relevantes nao devem ser sobrescritas; devem gerar historico.

## Acesso a documentos sensiveis

- Storage privado por defeito.
- Acesso mediado por controller/policy.
- Proibir URLs publicos permanentes.
- Registar visualizacao, download, substituicao e eliminacao.
- Preparar verificacao anti-malware e checksum.
- Classificar documentos por sensibilidade e finalidade.

## Perfis autorizados

- Administrador: acesso amplo, sempre auditado.
- Tecnico municipal: acesso necessario para instrucao.
- Juri: acesso limitado a processos em decisao.
- Gestor financeiro: dados financeiros e contratuais.
- Gestor de manutencao: dados tecnicos e contactos minimos.
- Candidato: apenas dados proprios.
- Auditor: leitura/auditoria, sem alteracao operacional.

## Registo de comunicacoes

Cada comunicacao deve registar:

- destinatario;
- canal;
- template e versao;
- assunto;
- estado de envio;
- data/hora;
- entidade relacionada;
- comprovativo quando aplicavel.

## Riscos RGPD

- recolha excessiva de dados antes da candidatura;
- exposicao indevida de documentos;
- publicacao de listas com identificacao excessiva;
- ausencia de prazo de retencao;
- falta de logs de acesso;
- exportacoes sem controlo;
- uso de dados de demonstracao em ambientes indevidos;
- acesso de perfis sem necessidade operacional.

## Medidas mitigadoras

- matriz de permissoes por modulo;
- policies por entidade;
- storage privado;
- logs de acesso e alteracao;
- classificacao documental;
- pseudonimizacao em listas;
- politicas de retencao;
- revisoes periodicas de acessos;
- testes de autorizacao;
- checklist RGPD antes de producao;
- validacao com DPO/encarregado de protecao de dados.

## Aplicação na Sprint 5

- A recolha fica limitada aos campos previstos para preparação do processo; não existem documentos nem integrações externas.
- Incapacidade, mobilidade, violência doméstica, ausência de habitação, rendimentos, NIF e morada são tratados como dados de acesso restrito ao titular.
- Logs de criação/alteração guardam ação, entidade, ator, campos alterados e contagem de indicadores, sem copiar valores sensíveis.
- Remoções de membros e rendimentos usam soft delete para retenção processual futura.
- Nenhum dado do agregado aparece no portal público.
- A base legal, os prazos de retenção por entidade e o procedimento de exercício de direitos continuam dependentes de validação jurídica/DPO na Sprint 18.
- A Sprint 6 deve definir sensibilidade, retenção e acesso antes de aceitar qualquer ficheiro.

## Aplicação na Sprint 6

- Tipos documentais incluem classificação de sensibilidade e retenção prevista em meses.
- A checklist recolhe apenas documentos exigidos por regras documentais ativas e pelas condições declaradas pelo candidato.
- Ficheiros são guardados em storage privado (`local`) e não são servidos por URLs públicos permanentes.
- Paths internos, checksums e notas internas de revisão não são apresentados ao candidato.
- Downloads passam por controller autorizado e são registados em `document_access_logs`.
- Upload, substituição, download, marcação em análise, validação e rejeição geram auditoria documental.
- Rejeições guardam motivo visível ao candidato e notas internas separadas para reduzir exposição indevida.
- Histórico de versões preserva rastreabilidade sem apagar ficheiros anteriores por defeito.
- Não foram criadas integrações externas, OCR, antivírus ou validação automática de autenticidade.

Pendências RGPD:

- Validar com DPO a base legal e retenção final de cada tipo documental.
- Definir política operacional de eliminação/anonimização de ficheiros expirados, substituídos ou de candidatos não selecionados.
- Introduzir antivírus e quarentena antes de produção.
- Definir procedimento de exportação/portabilidade de documentos do titular.
- Formalizar prazos de correção documental e comunicação ao candidato.

## Aplicação na Sprint 8

- A candidatura referencia dados já recolhidos e não duplica ficheiros físicos.
- Na submissão são criados snapshots mínimos para preservar o contexto administrativo da decisão futura.
- O snapshot documental guarda metadados e checksum, mas não guarda `storage_path`.
- Declarações e aceitação de tratamento são versionadas com timestamp.
- Criação, atualização, submissão, snapshots, comprovativo, consulta backoffice e desistência são auditados.
- O portal público não apresenta dados de candidatos nem números de candidatura.
- O número formal só é atribuído no momento da submissão.
- Dados de demonstração local usam nomes genéricos e domínio reservado `.test`.

## Aplicação na Sprint 7

- Resultados de elegibilidade são dados processuais sensíveis e exigem autenticação, role e policy.
- O candidato consulta apenas os próprios checks e nunca recebe `technical_message`.
- Snapshots guardam contagens, totais, estados e flags necessários; não guardam NIF, número de documento, nomes de ficheiros, conteúdos ou paths.
- Cada execução e reexecução cria novo check, sem sobrescrever o contexto histórico.
- Auditoria regista configuração, execução, reexecução e consulta técnica sem copiar snapshots para o log.
- O motor não decide fraude, autenticidade documental, impedimentos externos ou decisão administrativa definitiva.
- Seeders usam apenas conteúdo institucional fictício e domínio reservado.

Pendências RGPD adicionais:

- definir base legal e retenção de checks, resultados e snapshots;
- validar proporcionalidade de cada critério real;
- definir anonimização após encerramento;
- limitar futura exportação por perfil e finalidade;
- realizar DPIA se houver decisão exclusivamente automatizada.

## Configuração de demonstração de Alcanena

- `DemoAlcanenaAffordableRentSeeder` cria apenas conteúdo institucional, habitações inequivocamente fictícias e utilizadores técnicos fictícios para compor o júri.
- O seeder não cria candidatos, NIF, documentos, rendimentos ou candidaturas. As contas de júri usam emails `example.test` e passwords aleatórias não divulgadas.
- O concurso fica em rascunho e exige revisão administrativa e jurídica antes de publicação.
- Qualificação, deficiência, multideficiência, gravidez e dispensa de IRS são recolhidas apenas quando necessárias à matriz ou à checklist documental configurada.
- Deficiência, multideficiência e gravidez devem ser tratadas como dados de categoria especial, com acesso restrito, logs de acesso e prazo de retenção formalmente aprovado.
- Os impedimentos sem fonte estruturada permanecem em análise manual; o sistema não presume ausência de fraude, dívidas, apoios incompatíveis ou incumprimentos anteriores.
- Os valores legais de rendimento e RMMG guardados na demonstração têm referência temporal de 2026 e devem ser revistos antes de reutilização noutro concurso.
- A checklist de execução e validação está em `docs/qa/alcanena-demo-seeder-checklist.md`.

Pendências RGPD adicionais:

- Validar juridicamente o texto e a base legal das cinco declarações antes de produção.
- Definir retenção e anonimização de snapshots e candidaturas não selecionadas.
- Definir quem pode consultar cada categoria de snapshot nas Sprints 7, 9 e 18.
- Rever se o checksum documental deve ser visível a perfis administrativos específicos.

## Aplicação na Sprint 9

- Processos administrativos referenciam candidatura, candidato, programa e concurso sem duplicar ficheiros físicos.
- Pedidos de aperfeiçoamento guardam mensagem, instruções, prazo, itens e visibilidade; não enviam comunicação externa real nesta sprint.
- Respostas do candidato guardam texto e referência a `document_submission_id`; ficheiros continuam no storage privado documental.
- Notas internas ficam separadas por `visibility` e são excluídas da timeline do candidato.
- Decisões administrativas exigem `summary` e `grounds`, identificam decisor/aprovador e registam timestamps.
- Audit logs cobrem criação de processo, atribuição, transições, análise, pedido de aperfeiçoamento, resposta, revisão e decisão.
- Logs evitam copiar valores pessoais extensos; usam metadados mínimos como IDs e indicação de documento associado.
- O estado `admitted_for_scoring` é preparatório para classificação, sem pontuação, ranking ou publicação.

Pendências RGPD/jurídicas adicionais:

- validar prazo padrão de 10 dias e regras de bloqueio após vencimento;
- aprovar textos-base de pedidos, instruções e fundamentos;
- definir quando decisões podem/ devem ficar visíveis ao candidato;
- formalizar notificações oficiais e prova de comunicação na Sprint 16;
- definir retenção/anonimização de processos, notas e respostas na Sprint 18.

## Aplicação na Sprint 10

- Pontuação e ranking interno são tratados como dados processuais sensíveis.
- O candidato não consulta pontuação, posição ou ranking nesta sprint.
- `raw_value` dos detalhes guarda apenas valores agregados/seguros, sem nomes, NIF, documentos, ficheiros ou paths.
- Snapshots de ranking guardam pontuação, posição, estado e desempates, sem documentos nem storage interno.
- `published_at` dos snapshots permanece nulo; publicação pública fica para Sprint 11.
- Audit logs cobrem criação/alteração/ativação/arquivo/duplicação de matriz, critérios, regras, execução, pontuação manual, bloqueio e snapshots.
- Logs usam metadados mínimos e não copiam dados sensíveis extensos.
- Critérios demo são fictícios e não devem ser usados como matriz legal de produção.

Pendências RGPD/jurídicas adicionais:

- validar proporcionalidade, base legal e publicitação de cada critério real;
- definir pseudonimização/identificador público das listas na Sprint 11;
- definir retenção e anonimização de `ApplicationScore`, detalhes e snapshots;
- avaliar DPIA se a classificação tiver efeito decisório automatizado relevante;
- definir regras de exportação e segregação por júri/concurso.

## Aplicação na Sprint 11

- Listas públicas usam payload anonimizado e identificador público pseudonimizado.
- Portal público não apresenta nome, NIF, email, telefone, morada, número formal de candidatura, documentos, rendimentos, notas internas ou paths de storage.
- Área do candidato apresenta apenas resultados, reclamações, pedidos, audiências e notificações do próprio utilizador.
- Reclamações e pronúncias guardam fundamentos declarados pelo candidato; devem ser tratados como dados processuais sensíveis.
- Anexos de reclamação são referências a `document_submissions`; não há duplicação de ficheiros nem exposure de paths.
- Decisões de reclamação exigem fundamentação e registam proposta, aprovação, ator e timestamps.
- Alterações entre lista provisória e definitiva ficam em `list_change_logs`, sem reescrever ranking original.
- Publicações são versionadas em `list_publications` com canal, tipo, payload e período de visibilidade.
- Notificações oficiais são apenas registos internos/in-app nesta sprint; não há prova externa de envio por email, SMS ou carta.
- Auditoria cobre geração, aprovação, publicação, abertura/fecho de prazo, reclamação, decisão, audiência, pronúncia, lista definitiva, bloqueio e notificações internas.

Pendências RGPD/jurídicas adicionais:

- validar juridicamente o formato público das listas e o grau de pseudonimização;
- aprovar prazos reais de reclamação e audiência por concurso;
- aprovar textos oficiais de publicação, reclamação, audiência e decisão;
- definir retenção e anonimização de listas, reclamações, decisões e pronúncias;
- implementar prova de envio e templates oficiais na Sprint 16;
- rever DPIA antes de produção, dada a combinação de classificação, publicação e decisão administrativa.

## Aplicação na Sprint 12

- A atribuição usa referências a candidaturas, listas definitivas, habitações e utilizadores, evitando duplicar dados pessoais.
- Preferências de habitação são dados processuais do candidato e ficam protegidas por ownership.
- Sorteios guardam identificadores técnicos, seed, algoritmo, ordem e hash; não guardam NIF, contactos, documentos, rendimentos ou paths.
- Relatórios de atribuição guardam resumos agregados e referências processuais, não anexam documentos sensíveis.
- Ofertas, recusas, desistências e chamada de suplentes são registadas com motivo quando aplicável e auditadas.
- Notificações são internas/in-app e não geram prova externa de envio nesta sprint.
- A aceitação marca prontidão para contrato sem criar contrato nem recolher novos dados financeiros.

Pendências RGPD/jurídicas adicionais:

- validar base legal e retenção de preferências, sorteios, ofertas e relatórios;
- validar se seed/hash e payload do sorteio devem ser publicáveis e em que formato;
- aprovar textos oficiais de oferta, recusa, expiração, chamada de suplente e ata;
- definir regras de anonimização após encerramento de concurso;
- rever DPIA antes de produção, dado o impacto administrativo da atribuição.

## Aplicação na Sprint 13

- Contratos processuais reutilizam dados já recolhidos e referenciam candidatura, atribuição, candidato, agregado e habitação.
- Cálculos de renda guardam snapshot operacional dos rendimentos agregados e dados habitacionais necessários ao cálculo, sem copiar documentos, ficheiros ou paths.
- Documentos contratuais HTML ficam em storage privado (`local`) e downloads passam por controller/policy.
- O candidato consulta apenas contratos, documentos e caução associados ao próprio `user_id`.
- Renda, caução, validações, assinaturas e ativação são dados processuais/financeiros sensíveis e exigem backoffice autorizado.
- Audit logs cobrem criação/aprovação/rejeição de cálculo, revisão manual, criação/alteração de contrato, geração/download de documento, validação, assinatura, caução e ativação.
- Notificações contratuais são apenas internas/in-app; não há envio externo nem prova de entrega nesta sprint.
- Não foram introduzidos dados pessoais reais, credenciais, tokens ou APP_KEY.

Pendências RGPD/jurídicas adicionais:

- validar base legal, retenção e minimização dos snapshots de renda;
- aprovar fórmulas, limites, caução, minutas, cláusulas e textos oficiais;
- definir política de retenção e eliminação de documentos contratuais HTML;
- definir canal seguro e prova de entrega para comunicações formais na Sprint 16;
- avaliar necessidade de DPIA reforçada para dados financeiros e contratação;
- definir exportação/portabilidade de contratos e cálculos na Sprint 18.

## Aplicação na Sprint 14

- Dados financeiros de renda, incumprimento, acordos e revisões são tratados como dados processuais sensíveis.
- O módulo referencia contrato, candidato, agregado, habitação e documentos existentes, evitando duplicar dados pessoais quando uma FK é suficiente.
- Comprovativos internos guardam apenas dados mínimos do pagamento e aviso explícito de que não substituem recibo fiscal oficial.
- Ficheiros de comprovativo e importação CSV usam storage privado `local`; downloads passam por controller/policy.
- Importação CSV guarda payload mínimo por linha e erro de processamento quando aplicável; não há integração bancária real nem prova externa de liquidação.
- Candidato só consulta registos próprios por `user_id`; avisos só ficam visíveis após emissão.
- Declarações de alteração de rendimentos e pedidos documentais anuais ficam associados à conta/contrato e devem ser retidos conforme política final da Sprint 18.
- Audit logs cobrem criação de conta, geração de plano, emissão de prestação, registo/confirmação/imputação/estorno de pagamento, emissão/cancelamento de comprovativo, deteção de incumprimento, aviso, acordo, revisão, alteração de rendimento e atualização documental.
- Notificações continuam internas/in-app; não há envio externo, SMS, email, carta registada ou prova de entrega.
- Não foram introduzidos dados pessoais reais, credenciais, tokens ou APP_KEY.

Pendências RGPD/jurídicas/fiscais adicionais:

- validar base legal e retenção de dados financeiros, incumprimentos e acordos;
- aprovar textos de aviso de incumprimento e termos de regularização;
- definir se comprovativos internos devem evoluir para recibos oficiais ou integração fiscal;
- definir política de eliminação/anonimização de importações CSV;
- definir prova de entrega e templates oficiais na Sprint 16;
- rever DPIA antes de produção pela combinação de dados financeiros, incumprimento e decisões administrativas.

## Aplicação na Sprint 15

- Pedidos de manutenção, vistorias e histórico técnico são dados processuais associados à ocupação contratual da habitação.
- O módulo referencia contrato, candidato e habitação por FK, evitando duplicar dados pessoais quando a referência é suficiente.
- Anexos de manutenção/vistoria ficam em storage privado (`local`) e downloads passam por controller/policy.
- Autos de vistoria HTML ficam em storage privado e não expõem path interno nas views.
- Candidato só consulta pedidos próprios e eventos técnicos marcados como visíveis ao arrendatário.
- Dados de fornecedores são operacionais e não incluem faturação, NIB/IBAN, pagamentos ou contratação pública nesta sprint.
- Custos registados são administrativos/operacionais e não substituem fatura, ordem de compra ou pagamento.
- Audit logs cobrem criação de pedido, triagem, atribuição, intervenção, resolução, fecho, anexos, custos, vistorias, validação, autos e downloads.
- Notificações continuam internas/in-app; não há envio externo, SMS, email, carta registada ou prova de entrega.
- Não foram introduzidos dados pessoais reais, credenciais, tokens ou APP_KEY.

Pendências RGPD/jurídicas adicionais:

- validar base legal e retenção de fotografias/anexos de manutenção e vistoria;
- aprovar modelo oficial de auto de vistoria e regras de visibilidade ao arrendatário;
- definir critérios de minimização para fotografias de interiores e notas técnicas;
- definir prova de entrega e templates oficiais na Sprint 16;
- formalizar retenção, anonimização, exportação e resposta a titulares na Sprint 18;
- avaliar DPIA antes de produção pela combinação de dados habitacionais, imagens, incidentes técnicos e histórico contratual.

## Aplicação na Sprint 16

- Comunicação guarda apenas destinatário e contactos necessários ao canal, evento, estado e snapshot do conteúdo efetivamente usado.
- Candidato só consulta comunicações e documentos próprios; backoffice e auditor dependem de permissões.
- Exemplos e seeders usam domínio reservado e dados fictícios.
- Variáveis marcadas como sensíveis são bloqueadas por defeito no canal SMS.
- Paths privados, payloads completos de providers e credenciais não são expostos.
- Tentativas guardam apenas resumos limitados de pedido/resposta e erro.
- Leitura, tomada de conhecimento, downloads, templates, versões, regras, preferências e documentos geram audit logs.
- Preferências opcionais guardam timestamp de consentimento/revogação; in-app permanece disponível para comunicação processual.
- Comprovativos e documentos usam storage privado e nomes técnicos sem NIF, email, telefone ou nome completo.
- Templates e minutas demo têm aviso expresso de validação municipal/jurídica.

Pendências RGPD/jurídicas:

- definir base legal por evento/canal e quando uma preferência pode ser afastada por obrigação legal;
- definir retenção e eliminação de comunicações, tentativas, comprovativos, contactos snapshot e documentos;
- validar efeito jurídico da leitura e tomada de conhecimento;
- aprovar textos, prazos e modelos antes de produção;
- avaliar comunicação certificada, assinatura e carimbo temporal apenas em projeto próprio;
- consolidar pedidos de titular, exportação, anonimização e DPIA na Sprint 18.

## Aplicação na Sprint 17

- Dashboards e relatórios predefinidos usam dados agregados ou referências processuais pseudonimizadas.
- O âmbito `aggregated` é o padrão; `nominal` e `full` exigem permissão específica.
- O masking remove ou oculta chaves pessoais conhecidas conforme o âmbito.
- Relatórios sensíveis exigem aviso e confirmação explícita de exportação.
- Filtros, utilizador, formato pedido/real, âmbito, data, IP e user agent ficam registados.
- Exportações usam UUID, nomes técnicos, storage privado e expiração de sete dias.
- Downloads não recebem nem mostram paths internos.
- O exportador CSV neutraliza células iniciadas por `=`, `+`, `-` ou `@`.
- Resultados detalhados não são persistidos no histórico da execução.
- `report_access_logs`, `report_download_logs` e `audit_logs` preservam rastreabilidade.

Pendências RGPD para Sprint 18:

- definir retenção e eliminação de snapshots, execuções, exportações e logs;
- validar permissões nominais e necessidade/proporcionalidade de cada relatório;
- formalizar anonimização estatística para pequenos universos;
- incluir reporting e cruzamento de domínios na DPIA;
- definir procedimento de extração para pedidos de titular sem expor terceiros.

## Aplicação na Sprint 18

Controlos implementados:

- minimização por referência a entidades existentes e exportação gerada apenas a pedido;
- finalidades de tratamento configuráveis em `consent_purposes`;
- consentimentos com snapshot de texto, timestamp, origem, IP/user agent e retirada quando opcional;
- pedidos de titular com prazo, estado, atribuição, ações e conclusão/rejeição;
- exportação JSON privada com checksum, expiração e download autorizado;
- audit trail append-only em `audit_events`, com mascaramento de `password`, `token`, `secret`, `nif`, documentos e paths;
- logs de login/logout/falha e acesso backoffice em `access_logs`;
- logs de acesso documental, exportação e download em `sensitive_data_access_logs`;
- alertas configuráveis para falhas de login e acessos/exportações sensíveis;
- políticas de retenção com simulação e aprovação antes de execução;
- anonimização controlada com aprovação prévia;
- registry de campos sensíveis para planear encriptação sem quebrar login/pesquisa;
- checklist pré-produção e revisão de backups.

Pendências RGPD/DPO:

- validar base legal, prazos de retenção e texto das finalidades com o encarregado de proteção de dados;
- definir procedimento municipal de verificação de identidade antes da entrega de exportações;
- aprovar política de eliminação/anonimização antes de execução destrutiva;
- documentar DPIA final com mapas de risco, medidas técnicas e organizativas;
- testar restore de backups e evidência operacional antes de produção;
- definir SIEM/monitorização externa se o município exigir centralização de logs.
# Atualização Sprint 19 — Regressão RGPD e auditoria

A Sprint 19 reforçou a validação automatizada sem recolher dados reais:

- seeder integrado usa emails `example.test` e identificadores fictícios;
- documentos de teste usam `Storage::fake('local')` nos testes de upload/download;
- teste documental valida bloqueio de acesso cruzado e ausência de storage público;
- teste de auditoria valida mascaramento recursivo de chaves sensíveis;
- quality gates exigem ausência de falhas conhecidas de autorização e exposição documental antes de avançar.

Pendência obrigatória: validação por responsável RGPD/DPO e revisão de infraestrutura antes de qualquer produção.

## Atualização Sprint 20 — Portal público

Controlos RGPD e segurança:

- páginas públicas não incluem dados pessoais de candidatos;
- documentos de candidatura continuam fora do portal público;
- documentos públicos usam entidade própria e download por controller;
- o endpoint de mapa não expõe morada completa nem paths internos;
- a morada completa só é mostrada se `public_address_visible` estiver ativo;
- publicação depende de `is_public`, `public_visibility_status`, `published_at` e `unpublished_at`;
- backoffice usa roles e permissões existentes sobre `housing_units` e `settings`;
- eventos de publicação e atualização de ficha pública são registados via `AuditLogger`;
- imagens e brochuras devem ser revistas editorialmente antes de publicação.

Pendências:

- procedimento municipal de aprovação editorial;
- DPIA deve incluir exposição pública de imóveis, imagens e localização aproximada;
- definir retenção de documentos públicos arquivados.
## Atualização Sprint 21 — Simulador e reutilização de dados

- Simulações anónimas não guardam utilizador nem dados identificativos diretos.
- IP e user-agent são persistidos apenas como hash técnico.
- Simulações autenticadas ficam associadas ao candidato e são protegidas por policy de ownership.
- Pré-preenchimentos exigem confirmação explícita antes de aplicação.
- Renovações mantêm `previous_snapshot` e `updated_snapshot` para transparência.
- Logs de auditoria são emitidos para criação autenticada de simulação e conversão para pré-preenchimento.
- A retenção das sessões é configurável e deve ser validada pelo DPO antes de produção.
- O simulador apresenta aviso de caráter indicativo e não recolhe credenciais nem documentos.

## Atualização Sprint 24 — Backoffice operacional

Controlos RGPD e auditoria:

- dashboards usam métricas agregadas por defeito;
- relatórios por candidatura ficam em storage privado e exigem controller autorizado para download;
- dados nominais em relatórios dependem de permissão sensível (`reports.view_sensitive`);
- dossiers documentais não expõem paths internos de ficheiros submetidos;
- documentos gerados por minutas preservam payload e conteúdo snapshot para rastreabilidade;
- listas automatizadas ficam em execução assistida, sem publicação automática;
- atas e documentos exigem validação/aprovação humana;
- alertas internos não alteram automaticamente estados administrativos;
- confirmações de processo geram número único e registam auditoria/timeline quando disponível.

Pendências RGPD/DPO:

- definir retenção específica para relatórios, dossiers, atas, documentos gerados, alertas e confirmações;
- validar textos obrigatórios de relatórios, listas e atas;
- validar perfis com acesso nominal a relatórios e dossiers;
- incluir novos artefactos na DPIA e na matriz de exportação do titular;
- definir anonimização ou expurgo de alertas após encerramento do procedimento.

## Atualização Sprint 28 — OCR e classificação documental

Controlos RGPD e auditoria:

- OCR e classificação usam apenas ferramentas locais/self-hosted.
- `ocr_text`, `raw_text` e `raw_ai_json` são tratados como dados sensíveis.
- Eventos de OCR/classificação não transportam texto documental.
- Logs e auditoria guardam apenas metadados, IDs, estado, confiança e códigos técnicos.
- Painel backoffice exige `documents.view`; texto OCR exige `documents.audit` ou auditoria equivalente.
- Candidatos não acedem a resultados de OCR/classificação.
- Classificação automática nunca decide validação, elegibilidade, exclusão ou pontuação.

Pendências RGPD/DPO:

- validar retenção específica de `ocr_text` e `raw_ai_json`;
- confirmar se a exportação RGPD do titular deve incluir análises IA;
- validar avisos internos para técnicos sobre uso assistivo e não decisório da IA;
- testar dataset municipal anonimizado antes de qualquer uso operacional alargado.

## Atualização Sprint 29 — Campos extraídos por IA local

Controlos RGPD e auditoria:

- campos extraídos são tratados como dados sensíveis por defeito;
- dados de saúde do Atestado Multiusos são marcados em metadata e ocultados sem permissão adequada;
- painel de extração bloqueia candidatos;
- valores sensíveis são mascarados para perfis sem auditoria documental;
- eventos de extração transportam apenas IDs, tipo documental, estado e confiança;
- audit logs e processing logs não incluem valores extraídos, OCR bruto, JSON integral ou paths internos;
- marcação manual de campo para revisão é auditada;
- a extração nunca altera candidaturas, validações documentais, elegibilidade, pontuação ou decisões.

Pendências RGPD/DPO:

- definir prazo de retenção de `extraction_json` e `document_ai_fields`;
- decidir se campos extraídos entram na exportação RGPD do titular;
- validar base legal e informação aos candidatos sobre tratamento assistido por IA local;
- rever acesso a dados de saúde e necessidade de duplo controlo em produção.

## Atualização Sprint 30 — Validação IA contra candidatura

Controlos RGPD e auditoria:

- validações IA são assistivas e não decisórias;
- eventos transportam IDs, contadores, grupos e chaves, sem valores pessoais;
- flags não incluem valores declarados ou extraídos;
- o painel backoffice bloqueia candidatos;
- valores sensíveis ficam mascarados sem permissão de auditoria documental;
- dados de saúde ficam ocultos sem permissão de privacidade/auditoria;
- marcação manual e reprocessamento são auditados;
- falhas técnicas são registadas como códigos controlados;
- a validação não altera candidatura, documento funcional, elegibilidade, pontuação, ranking, listas, contratos ou workflow.

Pendências RGPD/DPO:

- validar retenção de `document_ai_validations`;
- decidir se valores em claro devem ficar ativos por configuração em produção;
- incluir validações IA na exportação RGPD quando juridicamente aplicável;
- validar texto informativo sobre cruzamento automatizado assistivo;
- rever perfis autorizados a consultar dados de saúde.
## Sprint 31 — Score IA e Apoio ao Aperfeiçoamento

Medidas RGPD:

- score e sugestões guardam metadados operacionais mínimos;
- eventos de queue transportam apenas IDs;
- audit logs não copiam OCR bruto, JSON bruto da IA nem campos extraídos sensíveis;
- painel backoffice bloqueia candidatos e guests por policy;
- interface não expõe paths internos dos documentos.

Medidas de auditoria:

- cálculo iniciado;
- score calculado;
- falha controlada;
- consulta do assistente;
- edição, aceitação e descarte de sugestões.

Princípio de decisão:

- indicadores IA não excluem candidatos;
- qualquer pedido formal, decisão ou alteração de estado exige intervenção técnica autorizada.
