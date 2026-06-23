# Workflows de processo

## Atualização Sprint 26 — Fluxo pós-atribuição

```text
Contrato ativo
→ Ativação de área do inquilino
→ Consulta de contrato
→ Emissão de fatura operacional
→ Registo/consulta de pagamento
→ Cobrança interna por período
→ Pedido de manutenção
→ Vistoria/intervenção quando aplicável
→ Comunicação inquilino/município
→ Dashboard operacional do senhorio/município
```

Salvaguardas: a cobrança automática é apenas geração operacional interna de valores; não há movimento bancário sem integração futura configurada; manutenção e vistorias reutilizam módulos existentes com autorização por contrato/habitação.

Este documento descreve o ciclo alvo. Após a Sprint 13, estão implementados o portal/programas, a adesão, a preparação do agregado, rendimentos e habitação atual, a gestão documental, a submissão formal da candidatura, elegibilidade, workflow administrativo, classificação interna, listas, reclamações, audiência, atribuição de habitações, cálculo de renda, contrato processual e caução.

## Ciclo processual final

```text
Registo de adesao
→ Simulacao
→ Candidatura
→ Validacao documental
→ Elegibilidade
→ Classificacao
→ Lista provisoria
→ Reclamacoes / audiencia de interessados
→ Lista definitiva
→ Atribuicao
→ Contrato
→ Pagamentos
→ Manutencao
→ Revisao de renda
→ Encerramento
```

## Workflow 1: Programa e concurso

Estado após Sprint 3: implementado no âmbito de criação, atualização, regras gerais, prazos, júri, publicação auditada e consulta pública. A revisão formal por júri e o versionamento imutável de regras continuam pendentes.

1. Administrador ou tecnico autorizado cria programa.
2. Tecnico configura regras gerais, documentos e prazos.
3. Juri ou responsavel designado e associado ao concurso.
4. Concurso e revisto.
5. Concurso e publicado.
6. Portal publico apresenta informacao, prazos e requisitos.

Controlos implementados:

- estados formais por enum;
- validação de datas;
- policies por módulo;
- publicação apenas de programa com regras e concurso com prazo;
- audit log de criação, alteração, publicação e eliminação;
- ocultação pública de rascunhos, arquivos e soft deletes.

Controlos futuros:

- historico de versoes de regras;
- circuito formal de revisão/aprovação antes da publicação;
- declaração de impedimentos de membros do júri.

## Workflow 2: Adesao e simulacao

Estado após Sprint 4: a conta candidata, o perfil base e o Registo de Adesão estão implementados. A simulação continua fora de âmbito.

1. Candidato cria conta.
2. Candidato aceita informacao de tratamento de dados aplicavel.
3. Candidato preenche dados minimos.
4. Sistema permite simulacao preliminar.
5. Candidato pode iniciar candidatura quando existir concurso aberto.

Controlos implementados:

- role `candidate` obrigatória;
- ownership exclusivo do registo;
- estados `incomplete`, `registered`, `cancelled`, `removed`, `blocked` e `expired`;
- validação de completude, consentimentos e idade mínima;
- histórico de transições;
- auditoria de criação, atualização, finalização, cancelamento e remoção;
- bloqueio da eliminação genérica da conta enquanto existir histórico de adesão.

Controlos futuros:

- validação jurídica final dos textos e bases legais;
- renovação e expiração automática;
- simulação preliminar;
- fluxo operacional de pedidos de titulares.

## Workflow 2A: Preparação do agregado e contexto habitacional

Estado após Sprint 5: implementado como dados preparatórios do Registo de Adesão, sem decisão de elegibilidade.

1. Candidato cria um agregado associado ao próprio registo.
2. Sistema sincroniza o requerente principal.
3. Candidato adiciona e atualiza membros.
4. Candidato declara rendimentos por membro ou justifica ausência de rendimentos.
5. Sistema calcula totais mensais e anuais declarados.
6. Candidato preenche a situação habitacional atual.
7. Dashboard apresenta progresso, campos em falta e resumo declarativo.

Controlos implementados:

- ownership por registo e policies;
- IDs de ownership definidos apenas pelos services;
- membro requerente obrigatório;
- NIF único dentro do agregado;
- normalização mensal/anual de rendimentos;
- soft deletes e auditoria sem valores sensíveis;
- edição apenas em estados `incomplete` e `registered`.

Controlos futuros:

- comprovativos e revisão documental na Sprint 6;
- elegibilidade na Sprint 7;
- snapshots e bloqueio após submissão formal na Sprint 8.

## Workflow 3: Candidatura

Estado após Sprint 8: implementado até à submissão formal, comprovativo, consulta inicial e desistência. A análise administrativa, elegibilidade e aperfeiçoamento continuam pendentes.

1. Candidato seleciona concurso.
2. Candidato confirma agregado, rendimentos e situacao habitacional.
3. Sistema apresenta documentos obrigatorios.
4. Candidato submete candidatura.
5. Candidatura fica bloqueada para edicao livre.
6. Tecnico recebe processo em fila de validacao.

Controlos implementados:

- concurso publicado e dentro da janela de candidatura;
- uma candidatura ativa por candidato e concurso, validada pelo service;
- ownership e autorização por policy;
- checklist documental da Sprint 6 reutilizada sem lógica paralela;
- cinco declarações obrigatórias com versão e timestamp;
- número único e `public_id` não sequencial nas rotas do candidato;
- snapshots dos dados considerados na submissão;
- bloqueio de edição direta depois de `submitted`;
- histórico de estado e auditoria de criação, submissão, snapshot, consulta e desistência;
- comprovativo sem paths internos de documentos.

Controlos futuros:

- restrição concorrente ao nível da base de dados para duplicados ativos;
- motor de elegibilidade versionado implementado na Sprint 7;
- aperfeiçoamento e transições administrativas na Sprint 9;
- preferências apenas após existir `contest_housing_units`;
- classificação e ranking na Sprint 10.

Estados alvo:

- `draft`
- `submitted`
- `document_review`
- `awaiting_correction`
- `eligible_review`
- `scoring`
- `provisional_list`
- `complaint_period`
- `final_list`
- `allocated`
- `contracted`
- `closed`
- `rejected`
- `withdrawn`

## Workflow 4: Validacao documental

Estado após Sprint 6: implementado como gestão documental autónoma e preparatória da candidatura formal. Ainda não existe bloqueio de submissão formal porque a Sprint 8 não foi executada.

1. Sistema identifica documentos em falta.
2. Tecnico valida cada documento.
3. Documento pode ser aceite, rejeitado ou pedir substituicao.
4. Candidato e notificado se existir pedido de correcao.
5. Candidatura avanca apenas quando documentos obrigatorios estiverem tratados.

Controlos implementados:

- logs de acesso a documentos;
- registo de motivo de rejeicao;
- tipologia documental e sensibilidade;
- checklist dinâmica por regras declarativas;
- storage privado e download por controller autorizado;
- histórico de versões e substituição controlada;
- auditoria de upload, substituição, download, validação e rejeição;
- candidato limitado aos próprios documentos.

Controlos futuros:

- prazos de correcao.
- antivírus, OCR, validação automática de autenticidade e assinaturas digitais;
- notificações reais ao candidato;
- bloqueio da submissão formal na Sprint 8 quando documentos obrigatórios estiverem em falta, rejeitados ou expirados.

## Workflow 5: Elegibilidade

1. Candidato ou técnico seleciona programa, concurso ou candidatura.
2. Sistema resolve o rule set ativo, dando prioridade ao concurso.
3. Sistema recolhe valores agregados mínimos e avalia apenas critérios ativos.
4. Cada critério gera resultado, mensagem simples e mensagem técnica restrita.
5. Sistema agrega o resultado, guarda dados em falta, alertas e snapshots mínimos.
6. Candidato consulta resultado indicativo; técnico consulta detalhe técnico.
7. Reexecuções criam novo check e preservam o histórico.
8. A decisão administrativa final e a eventual transição de estado ficam para a Sprint 9.

Controlos implementados:

- explicabilidade;
- versionamento simples por arquivo/duplicação;
- histórico de checks e snapshots;
- bloqueio contra recálculo silencioso;
- separação entre mensagem do candidato e detalhe técnico.

Controlos futuros:

- circuito de aprovação e fundamentação administrativa;
- validação jurídica das regras reais;
- retenção e anonimização dos snapshots;
- transição autorizada da candidatura após decisão.

## Workflow 6: Classificacao e ranking

Estado após Sprint 10: implementado como ranking interno, sem exposição pública de pontuações ao candidato. A lista provisória passou a ser tratada na Sprint 11.

1. Sistema calcula pontuacao por criterio.
2. Tecnico revê inconsistencias.
3. Juri valida pontuacao.
4. Sistema gera snapshot de ranking.
5. Lista provisoria e preparada para publicacao.

Controlos implementados:

- apenas processos administrativos `admitted_for_scoring` entram na classificação;
- matriz ativa do concurso prevalece sobre matriz ativa do programa;
- pontuação, detalhes e desempates são preservados em execução e snapshot;
- ranking interno não é publicado diretamente;
- publicação pública exige workflow específico da Sprint 11.

Controlos futuros:

- criterios publicados;
- snapshots imutaveis;
- desempates documentados;
- auditoria de recalculos.

## Workflow 7: Listas, reclamacoes e audiencia

Estado após Sprint 11: implementado até lista definitiva e preparação para atribuição, com publicação versionada, reclamações próprias, informação complementar, audiência e registo de alterações. Não existe ainda atribuição de habitações.

1. Lista provisoria e publicada com dados minimizados.
2. Prazo de reclamacao abre.
3. Candidato submete reclamacao quando aplicavel.
4. Tecnico/juri analisa e decide.
5. Audiencia de interessados e aberta quando exigida.
6. Lista definitiva e publicada.

Controlos implementados:

- lista provisória criada a partir de snapshot interno/bloqueado;
- aprovação obrigatória antes de publicação;
- identificador público pseudonimizado por entrada;
- portal público e área do candidato sem NIF, email, telefone, morada, documentos ou paths;
- reclamação apenas sobre candidatura própria e dentro de prazo ativo;
- decisões de reclamação exigem resumo, fundamentos e estado de aprovação;
- lista definitiva bloqueada se existirem reclamações ou audiências pendentes;
- alterações são registadas em `list_change_logs`;
- notificações oficiais são internas/in-app, sem email/SMS real.

Controlos futuros:

- calendario processual;
- comprovativo de publicacao;
- validação jurídica final do formato público;
- prova externa de notificação oficial na Sprint 16.

## Workflow 8: Atribuicao

1. Sistema identifica fogos disponiveis no concurso.
2. Atribuicao segue ranking, criterios e preferencias admissiveis.
3. Quando aplicavel, sorteio e executado com metodo auditavel.
4. Resultado de atribuicao e registado.
5. Candidato aceita ou recusa nos termos definidos.

Controlos futuros:

- simulacao antes de atribuicao definitiva;
- logs de metodo;
- snapshots de resultado;
- bloqueio de atribuicoes duplicadas.

## Workflow 9: Contrato, pagamentos e renda

1. Contrato e gerado a partir da atribuicao.
2. Renda e calculada por regra versionada.
3. Caucao e registada quando aplicavel.
4. Pagamentos sao geridos por periodo.
5. Incumprimentos geram workflow financeiro.
6. Revisoes de renda ocorrem por calendario ou evento.

Controlos implementados na Sprint 13:

- contrato nasce apenas de atribuição aceite/pronta para contrato;
- cálculo de renda usa regras ativas e configuráveis por programa/concurso;
- snapshots e detalhes preservam o contexto usado no cálculo;
- renda manual exige justificação e aprovação autorizada;
- contrato usa o cálculo aprovado como fonte de verdade para renda e caução;
- documento contratual HTML fica em storage privado e download autorizado;
- validação interna, assinatura manual/registada e caução paga/dispensada são pré-condições de ativação;
- ativação atualiza o estado da habitação.

Controlos futuros:

- cobrança real e reconciliação financeira;
- recibos e faturação;
- revisão periódica de renda;
- comunicação externa formal com prova de envio;
- geração PDF real e assinatura digital externa.

## Workflow 10: Manutencao e vistorias

1. Candidato/tecnico regista pedido.
2. Gestor de manutencao classifica prioridade.
3. Vistoria e agendada quando necessario.
4. Relatorio e anexos sao registados.
5. Pedido e resolvido ou encaminhado.

Controlos futuros:

- acesso minimo a dados do inquilino;
- anexos protegidos;
- SLA;
- historico tecnico do fogo.

## Workflow 11: Encerramento

1. Processo e identificado para encerramento.
2. Sistema verifica pendencias documentais, financeiras e operacionais.
3. Dados passam para politica de retencao aplicavel.
4. Auditoria permanece preservada.
5. Titular e informado quando aplicavel.

Controlos futuros:

- checklist de encerramento;
- retencao por finalidade;
- anonimização quando aplicavel;
- bloqueio de edicao apos encerramento.

## Workflow implementado na Sprint 9: administrativo e aperfeiçoamento

1. Candidatura submetida é recebida pelo backoffice.
2. Técnico autorizado cria processo administrativo único.
3. Processo pode ser atribuído a técnico responsável.
4. Serviços iniciam triagem, análise documental e análise de requisitos.
5. Quando necessário, é criado pedido de aperfeiçoamento em rascunho com itens obrigatórios e prazo.
6. Ao emitir o pedido, o candidato passa a vê-lo na área pessoal.
7. Candidato responde aos itens próprios dentro do prazo com texto e/ou documento existente.
8. Técnico analisa a resposta e aceita ou rejeita.
9. Processo regressa à análise de requisitos quando a resposta é suficiente.
10. Técnico regista decisão fundamentada de admissão para classificação ou não admissão.

Controlos implementados:

- estados formais do processo administrativo;
- histórico de transições;
- visibilidade controlada dos pedidos ao candidato;
- notas internas excluídas da timeline do candidato;
- auditoria de criação, transição, aperfeiçoamento, resposta e decisão;
- scope técnico para Sprint 10 (`admitted_for_scoring`).

## Workflow implementado na Sprint 10: classificação e ranking interno

1. Técnico autorizado confirma que existem candidaturas admitidas para classificação.
2. Sistema resolve a matriz ativa aplicável, dando prioridade à matriz do concurso.
3. Execução cria `ScoringRun` e avalia apenas candidaturas `admitted_for_scoring`.
4. Último `EligibilityCheck` deve estar `eligible` para a candidatura ser pontuada por defeito.
5. Cada critério ativo gera `ApplicationScoreDetail`.
6. Critérios automáticos somam `automatic_score`.
7. Critérios manuais ficam pendentes até avaliação autorizada.
8. Regras de desempate geram `tie_breaker_values`.
9. Sistema atribui `rank_position`, identifica empates persistentes e cria `RankingSnapshot` interno.
10. Backoffice consulta pontuações, detalhes e ranking interno; candidato vê apenas mensagem genérica.

Controlos implementados:

- rankings não têm rota pública;
- snapshots internos permanecem com `published_at = null`;
- pontuação e bloqueio passam por services;
- auditoria de criação/alteração de matriz, execução, pontuação manual e snapshots;
- separação entre classificação interna e listas provisórias da Sprint 11.

## Workflow implementado na Sprint 12: atribuição de habitações

1. Backoffice associa habitações disponíveis ao concurso.
2. Backoffice define regras de adequação de tipologia por concurso ou programa.
3. Candidato pode ordenar preferências de habitação quando aplicável e antes de existir atribuição.
4. Técnico autorizado seleciona lista definitiva aprovada/publicada/bloqueada.
5. Sistema resolve a regra de atribuição ativa aplicável, com precedência do concurso.
6. Execução atribui habitações por ranking, preferências ou sorteio auditável.
7. Sistema cria ofertas de atribuição e regista notificação interna/in-app.
8. Candidato aceita, recusa com motivo ou desiste nos prazos configurados.
9. Recusa, expiração ou desistência libertam a habitação e podem chamar suplente.
10. Aceitação marca a atribuição como pronta para contrato, sem criar contrato.

Controlos implementados:

- listas definitivas são a origem obrigatória da atribuição;
- ranking interno bruto não é usado diretamente;
- tipologia é avaliada por regras configuráveis;
- sorteio preserva seed, algoritmo, participantes, ordem, resultados e hash;
- duplicados ativos por candidatura/habitação são bloqueados;
- aceitação, recusa, expiração, desistência, suplentes e relatórios são auditáveis;
- contratos, rendas, cauções e pagamentos permanecem fora do workflow.

## Workflow implementado na Sprint 13: contrato, renda e caução

1. Técnico autorizado calcula a renda para uma atribuição pronta para contrato.
2. Sistema resolve a regra ativa aplicável e guarda cálculo, detalhes e snapshot.
3. Gestor autorizado aprova o cálculo ou solicita/aprova revisão manual justificada.
4. Técnico seleciona minuta ativa e cria contrato processual.
5. Sistema cria número de contrato, partes, cláusulas snapshot e caução associada.
6. Técnico gera documento contratual HTML em storage privado.
7. Backoffice emite contrato, regista validação interna e assinatura/registo manual.
8. Gestor financeiro regista caução paga ou dispensa justificada.
9. Backoffice ativa contrato quando todas as pré-condições estão cumpridas.
10. Candidato consulta apenas os próprios contratos, documentos e caução.

Controlos implementados:

- policies por entidade contratual e ownership do candidato;
- estados formais em enums e histórico de transições;
- campos críticos escritos por services com `forceFill`;
- renda e caução do contrato derivadas do cálculo aprovado;
- documentos contratuais privados, sem exposição de path interno;
- notificações oficiais internas/in-app sem envio externo real;
- auditoria de cálculo, revisão, contrato, documento, validação, assinatura, caução e ativação.

## Workflow implementado na Sprint 14: pagamentos, incumprimentos e revisão de renda

1. Gestor financeiro cria conta financeira para contrato ativo.
2. Sistema gera plano de rendas e prestações mensais com referências únicas.
3. Município regista pagamento manual ou importa lote CSV interno.
4. Pagamento confirmado é imputado a uma prestação específica ou à dívida mais antiga.
5. Sistema atualiza prestação, pagamento, conta corrente e extrato financeiro.
6. Backoffice emite comprovativo interno em storage privado.
7. Sistema deteta prestações vencidas em aberto e cria incumprimento.
8. Backoffice emite aviso de incumprimento, tornando-o visível ao candidato.
9. Backoffice cria acordo de regularização e prestações do acordo.
10. Candidato declara alteração de rendimentos ou responde a pedido anual documental.
11. Backoffice pode abrir, calcular, aprovar e aplicar revisão de renda.
12. Aplicação da revisão cria novo plano de rendas e atualiza a renda contratual.

Controlos implementados:

- candidato consulta apenas dados financeiros próprios;
- backoffice financeiro exige role/permissões;
- estados formais em enums para contas, planos, prestações, pagamentos, recibos, incumprimentos, avisos, acordos, revisões e pedidos documentais;
- campos críticos e transições passam por services;
- comprovativos internos ficam em storage privado e download passa por policy/controller;
- notificações são internas/in-app, sem envio externo real;
- importação é CSV interno, sem integração bancária real;
- auditoria regista eventos críticos do domínio financeiro.

## Workflow implementado na Sprint 15: manutenção, vistorias e gestão do imóvel

1. Arrendatário autenticado cria pedido de manutenção para o contrato ativo próprio.
2. Sistema gera número único, associa habitação/contrato/candidato e guarda anexos em storage privado.
3. Backoffice consulta o pedido, faz triagem, classifica urgência técnica e agenda, rejeita ou inicia intervenção.
4. Pedido pode ser atribuído a técnico interno ou fornecedor registado.
5. Backoffice regista intervenção, conclusão técnica, custos e aprovação/rejeição interna desses custos.
6. Pedido resolvido pode ser fechado com notas de encerramento.
7. Backoffice cria vistorias por tipo, aplica checklist, regista itens, fotografias e conclusão.
8. Vistoria validada pode gerar auto HTML em storage privado e ficar visível ao arrendatário quando autorizado.
9. Histórico técnico do imóvel consolida manutenção, intervenções e vistorias.
10. Indicadores e relatórios de custos ficam disponíveis para backoffice autorizado.

Controlos implementados:

- ownership do candidato por `user_id` e contrato ativo;
- backoffice protegido por roles e permissões de manutenção/vistorias;
- campos críticos, números e transições escritos por services;
- anexos e autos privados com download autorizado;
- histórico técnico com flag de visibilidade para arrendatário;
- audit logs para criação, triagem, atribuição, intervenção, custos, vistorias, validação e downloads;
- notificações internas/in-app sem envio externo real;
- fornecedores existem como entidade operacional, sem portal, faturação ou pagamento.

## Workflow implementado na Sprint 16: notificações e documentos oficiais

1. Um evento crítico é emitido pelo domínio ou uma comunicação manual é criada por utilizador autorizado.
2. O sistema resolve regras ativas por `event_code`, destinatário e canal.
3. Regra específica de concurso prevalece sobre programa, município e regra global.
4. O sistema resolve a versão ativa do template e valida variáveis conhecidas, obrigatórias e sensíveis.
5. É criado `CommunicationLog` com número único e snapshot imutável do conteúdo.
6. Cada canal cria `CommunicationDelivery` e regista `CommunicationAttempt`.
7. In-app disponibiliza a notificação e gera comprovativo privado.
8. Email sem configuração externa fica `pending_configuration`; SMS sem gateway fica `disabled`.
9. Envio postal é preparado e confirmado manualmente, com comprovativo opcional.
10. Leitura e tomada de conhecimento geram timestamps, auditoria e comprovativos.
11. Modelos documentais ativos geram documento HTML privado com número e checksum.
12. Downloads passam por policy e controller e são auditados.

Controlos implementados:

- candidato vê apenas comunicações e documentos associados ao próprio `user_id`;
- auditor consulta histórico sem ações de escrita;
- estados críticos são escritos por services, não por mass assignment;
- versões usadas não são reescritas;
- payloads técnicos não guardam conteúdo completo de providers;
- variáveis sensíveis são bloqueadas no canal SMS;
- paths privados não são apresentados nas páginas;
- não existe alegação de entrega certificada, email externo, SMS real ou postal automático.

## Workflow implementado na Sprint 17: relatórios e apoio à decisão

1. Utilizador interno abre o catálogo, dashboard operacional ou dashboard executivo autorizado.
2. O sistema normaliza e valida filtros de período, programa, concurso, estado e localização.
3. Policies verificam a permissão global e a permissão específica da definição.
4. O registry confirma que o serviço e método configurados pertencem à allowlist.
5. Indicadores consultam os módulos operacionais e devolvem valor, estado e timestamp de cálculo.
6. A execução de relatório guarda filtros, âmbito, formato, utilizador e estado.
7. Resultados agregados são apresentados sem nomes, contactos, documentos ou paths.
8. Exportação sensível exige confirmação e permissão específica.
9. O ficheiro é gravado em storage privado com nome técnico e expiração.
10. O download passa por controller autorizado e cria log de acesso e de download.

Controlos implementados:

- separação entre consulta, gestão, auditoria e exportação sensível/nominal;
- allowlists para impedir execução arbitrária por configuração;
- mascaramento e remoção de campos pessoais conforme âmbito;
- ficheiros privados, sem exposição do path;
- logs de dashboard, relatório, execução, exportação e download;
- fallback de formatos explicitamente registado.

## Workflow implementado na Sprint 18: RGPD, segurança e auditoria

1. Utilizador backoffice autentica com sessão Laravel.
2. Perfis internos sensíveis são encaminhados para validação MFA na área de segurança.
3. Login, logout, falhas de login e acessos backoffice são registados em `access_logs`.
4. Operações críticas geram `audit_logs` e `audit_events` compatíveis.
5. Acesso a documentos, exportações e dados sensíveis cria `sensitive_data_access_logs`.
6. Regras de alerta avaliam falhas de login e volume de acessos/exportações sensíveis.
7. Município revê permissões e pendências de MFA por utilizador/role.
8. Município gere finalidades RGPD e consentimentos.
9. Titular submete pedido RGPD na área pessoal ou município regista pedido no backoffice.
10. Pedido RGPD pode ser atribuído, documentado, exportado, concluído ou rejeitado.
11. Exportação do titular é gerada em storage privado com checksum e download autorizado.
12. Políticas de retenção são simuladas antes de qualquer execução real.
13. Execução de retenção real exige aprovação e permanece conservadora nesta sprint.
14. Pedido de anonimização exige aprovação antes da execução.
15. Checklist pré-produção bloqueia aprovação se existir item falhado.

Controlos implementados:

- MFA obrigatório para backoffice sensível na área de segurança;
- ownership estrito para pedidos/exportações RGPD do candidato;
- mascaramento de chaves sensíveis em eventos de auditoria;
- paths privados nunca são apresentados como caminho interno de storage;
- recovery codes MFA nunca são guardados em texto claro;
- retenção e anonimização dependem de service e aprovação explícita;
- alerts e logs preservam rastreabilidade sem guardar passwords, tokens ou APP_KEY.
# Atualização Sprint 19 — Validação de workflows

A Sprint 19 validou, por testes e documentação QA, o encadeamento processual já implementado:

```text
Adesão → Agregado/Rendimentos/Habitação → Documentos → Elegibilidade → Candidatura
→ Workflow administrativo → Classificação/Ranking → Listas/Reclamações
→ Atribuição → Contrato/Renda → Pagamentos/Incumprimentos
→ Manutenção/Vistorias → Notificações/Relatórios → RGPD/Auditoria
```

A validação é técnica e usa dados fictícios. Não substitui validação jurídica, operacional ou de produção.

## Workflow implementado na Sprint 20: consulta pública da oferta

1. Backoffice cria ou seleciona uma habitação existente.
2. Backoffice edita ficha pública, localização, SEO, imagens e documentos públicos.
3. Backoffice publica a ficha pública ou mantém em rascunho/oculta.
4. Cidadão acede a `/oferta-habitacional`.
5. Cidadão filtra por tipologia, freguesia, renda, estado e concurso.
6. Cidadão consulta mapa/fallback e abre uma ficha pública.
7. Cidadão consulta documentos públicos por download controlado.
8. Cidadão abre o concurso associado e, se aplicável, segue para a área de candidatura já existente.

Controlos:

- publicação depende de estado público e timestamps;
- documentos privados e dados pessoais não entram no fluxo público;
- morada completa depende de autorização editorial;
- backoffice é protegido por roles e policies existentes.
## Sprint 21 — Fluxo do simulador e registo inteligente

```text
Consultar simulador
→ preencher dados mínimos
→ calcular resultado indicativo
→ consultar impedimentos e recomendações
→ guardar simulação autenticada
→ criar perfil de reutilização de dados
→ confirmar pré-preenchimento
→ aplicar a rascunho de candidatura
→ continuar candidatura formal
```

Fluxo de renovação:

```text
Aceder à área do candidato
→ iniciar renovação de registo
→ confirmar dados atuais
→ alterar apenas campos necessários
→ submeter confirmação
→ atualizar Registo de Adesão
```

## Sprint 24 — Backoffice operacional e gestão do procedimento

```text
Consultar dashboard operacional
→ identificar prazos, documentos, alertas e volumes
→ abrir candidatura/processo
→ gerar relatório por candidatura
→ gerar dossier documental
→ resolver ou dispensar alertas
→ preparar minuta
→ gerar documento ou ata
→ rever e aprovar
→ gerar lista assistida
→ validar humanamente antes de publicação
→ emitir confirmação de processo
```

Controlos:

- relatórios, dossiers, atas e documentos ficam em storage privado;
- listas geradas pela automação assistida não são publicadas automaticamente;
- atas e documentos exigem revisão/aprovação humana;
- número de processo é único e a regeneração exige pedido explícito;
- candidatos não acedem às rotas backoffice.

## Sprint 28 — Fluxo de OCR e classificação documental

```text
Documento submetido
→ análise IA pendente criada
→ job em queue
→ OCR local
→ classificação por keywords/layout/IA local opcional
→ score de confiança
→ painel backoffice
→ confirmação ou revisão manual pelo técnico
```

Controlos:

- o upload documental continua funcional mesmo quando OCR/IA falha;
- OCR indisponível gera flag e revisão manual;
- resultados não alteram estados funcionais do documento nem da candidatura;
- texto OCR e JSON bruto não são enviados para eventos ou auditoria;
- candidatos não acedem ao painel de classificação IA.

## Sprint 29 — Fluxo de extração estruturada documental

```text
Documento classificado
→ selecionar schema por tipo documental
→ extrair campos por regex e IA local opcional
→ normalizar valores
→ calcular confiança
→ guardar JSON estruturado
→ guardar campos normalizados
→ gerar flags
→ painel backoffice
→ revisão humana quando necessário
```

Controlos:

- valores extraídos não alteram candidatura nem documento funcional;
- dados sensíveis são mascarados no painel quando aplicável;
- dados de saúde exigem permissão mais restrita;
- eventos e auditoria não transportam valores;
- validação cruzada com candidatura fica para Sprint 30.

## Sprint 30 — Fluxo de validação IA contra candidatura

```text
Documento com campos extraídos
→ resolver candidatura associada
→ agendar validação em queue
→ carregar dados declarados
→ carregar campos extraídos
→ aplicar regras por tipo documental
→ calcular estado, confiança e severidade
→ gravar run, resultados, hashes e flags
→ auditar execução
→ painel backoffice
→ revisão manual quando necessário
```

Controlos:

- a validação não altera dados declarados ou estados funcionais;
- divergências críticas apenas sinalizam revisão;
- candidatos não acedem ao painel;
- valores sensíveis e de saúde são mascarados/ocultos conforme permissões;
- reprocessamento é ação autorizada e auditada.
