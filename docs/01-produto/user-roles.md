# Perfis de utilizador

## Administrador

Descricao: utilizador interno responsavel pela configuracao global da plataforma e gestao tecnica/funcional de alto nivel.

Responsabilidades:

- gerir utilizadores, roles e permissions;
- configurar municipios, programas e parametros;
- supervisionar auditoria e seguranca;
- apoiar operacao em incidentes.

Modulos acessiveis:

- Dashboard, Utilizadores, Municipios, Programas, Concursos, Configuracoes, Auditoria, RGPD, Relatorios e todos os modulos operacionais quando justificado.

Acoes permitidas:

- `view`, `create`, `update`, `delete`, `approve`, `reject`, `publish`, `export`, `audit`, conforme matriz de permissões.

Acoes proibidas:

- alterar logs de auditoria;
- apagar dados pessoais fora de politica RGPD aprovada;
- aceder a documentos sensiveis sem finalidade operacional;
- usar contas partilhadas.

Riscos associados:

- excesso de privilegio;
- configuracao incorreta de permissoes;
- exportacao indevida de dados;
- alteracao de parametros com impacto processual.

Notas de seguranca:

- exigir MFA quando disponivel;
- rever acessos periodicamente;
- registar todas as acoes administrativas;
- usar principio de dupla validacao para operacoes criticas futuras.

## Tecnico municipal

Descricao: operador interno responsavel pela instrucao, validacao e acompanhamento dos processos.

Responsabilidades:

- consultar candidaturas e perfis;
- validar informacao e documentos;
- gerir estados operacionais;
- comunicar com candidatos;
- preparar processos para decisao.

Modulos acessiveis:

- Dashboard, Programas, Concursos, Registo de adesao, Candidaturas, Agregados, Rendimentos, Documentos, Elegibilidade, Listas, Reclamacoes, Notificacoes e Relatorios operacionais.

Acoes permitidas:

- `view`, `create`, `update`, `approve`, `reject`, `export` limitado, conforme modulo.

Acoes proibidas:

- gerir roles globais;
- publicar listas sem permissao especifica;
- alterar criterios de classificacao aprovados;
- eliminar documentos sensiveis fora de processo;
- consultar processos sem finalidade.

Riscos associados:

- acesso excessivo a dados pessoais;
- erro na validacao documental;
- alteracao indevida de estados;
- tratamento desigual de candidatos.

Notas de seguranca:

- obrigar motivo nas decisoes;
- auditar leitura de documentos sensiveis;
- limitar exportacoes;
- separar preparacao tecnica de decisao do juri.

## Juri

Descricao: perfil responsavel pela apreciacao e decisao em fases formais do concurso, conforme regras municipais.

Responsabilidades:

- analisar processos preparados;
- aprovar ou rejeitar resultados;
- validar classificacao e listas;
- participar em reclamacoes e audiencia.

Modulos acessiveis:

- Candidaturas, Documentos resumidos, Elegibilidade, Classificacao, Listas, Reclamacoes, Relatorios de decisao e Auditoria limitada ao processo.

Acoes permitidas:

- `view`, `approve`, `reject`, `publish` quando formalmente designado, `audit` limitado.

Acoes proibidas:

- alterar dados declarados pelo candidato;
- gerir utilizadores;
- alterar documentos submetidos;
- alterar pagamentos ou manutencao;
- aceder a processos onde exista impedimento.

Riscos associados:

- conflito de interesses;
- decisao sem trilho justificativo;
- acesso a dados para alem do necessario;
- publicacao indevida.

Notas de seguranca:

- registar declaracao de participacao/impedimento no concurso;
- exigir justificacao para decisao;
- usar snapshots de ranking;
- bloquear alteracoes apos publicacao sem procedimento formal.

## Gestor financeiro

Descricao: perfil interno responsavel por rendas, pagamentos, caucoes, incumprimentos e planos financeiros.

Responsabilidades:

- gerir pagamentos e planos;
- acompanhar incumprimentos;
- consultar contratos e rendas;
- produzir relatorios financeiros.

Modulos acessiveis:

- Dashboard financeiro, Contratos, Pagamentos, Relatorios, Notificacoes financeiras e Auditoria limitada.

Acoes permitidas:

- `view`, `create`, `update`, `export` financeiro, `approve` em planos quando aplicavel.

Acoes proibidas:

- alterar criterios de candidatura;
- validar elegibilidade social;
- publicar listas;
- consultar documentos nao financeiros sem finalidade.

Riscos associados:

- exposicao de dados financeiros;
- erro em registos de pagamento;
- alteracao indevida de renda;
- exportacao excessiva.

Notas de seguranca:

- segregar acesso financeiro de avaliacao social;
- auditar alteracoes de valores;
- controlar exportacoes;
- reconciliar pagamentos com evidencia.

## Gestor de manutencao

Descricao: perfil interno ou operacional responsavel por manutencao, vistorias e estado tecnico dos imoveis.

Responsabilidades:

- gerir pedidos de manutencao;
- agendar vistorias;
- registar relatorios;
- acompanhar estado dos fogos.

Modulos acessiveis:

- Habitações, Manutenção, Vistorias, Notificações operacionais e Relatórios técnicos.

Acoes permitidas:

- `view`, `create`, `update`, `export` limitado a operacao tecnica.

Acoes proibidas:

- consultar rendimentos;
- decidir candidaturas;
- alterar classificacao;
- aceder a documentos sociais ou financeiros sem necessidade.

Riscos associados:

- acesso indevido a dados do agregado;
- exposicao de moradas;
- registos tecnicos incompletos;
- anexos de vistoria com dados pessoais.

Notas de seguranca:

- limitar dados do candidato ao necessario para intervencao;
- auditar anexos e relatorios;
- proteger fotografias e documentos de vistoria;
- aplicar minimizacao nas ordens de servico.

## Candidato

Descricao: utilizador externo que adere ao programa, submete candidatura e acompanha o seu processo.

Responsabilidades:

- manter dados atualizados;
- submeter documentos verdadeiros;
- responder a notificacoes e prazos;
- exercer direitos e pedidos quando aplicavel.

Modulos acessiveis:

- Area pessoal, Registo de adesao, Agregado, Rendimentos, Documentos, Candidaturas, Notificacoes, Reclamacoes, Contratos, Pagamentos e Manutencao referentes ao proprio processo.

Estado após Sprint 4:

- a role `candidate` é atribuída no registo de nova conta;
- a área pessoal e o Registo de Adesão estão operacionais;
- o candidato não acede às rotas internas do CRM;
- cada candidato consulta e altera apenas o próprio registo;
- os restantes módulos aparecem apenas quando implementados ou como placeholders explícitos.

Acoes permitidas:

- `view`, `create`, `update` antes de submissao/bloqueio, `export` dos proprios dados quando disponibilizado.

Acoes proibidas:

- aceder a processos de terceiros;
- alterar candidatura apos submissao formal sem procedimento;
- consultar classificacao interna nao publicada;
- aprovar/rejeitar documentos ou decisoes.

Riscos associados:

- submissao de dados incorretos;
- partilha de conta;
- upload de documentos sensiveis;
- tentativa de acesso a dados de terceiros.

Notas de seguranca:

- acesso limitado ao proprio perfil;
- logs de acesso;
- mensagens claras sobre finalidades;
- protecao de documentos em storage privado.

## Auditor

Descricao: perfil independente ou interno de controlo, com acesso de leitura a evidencias e logs.

Responsabilidades:

- auditar acessos, decisoes e alteracoes;
- verificar cumprimento processual;
- apoiar revisoes RGPD e seguranca;
- produzir relatórios de conformidade.

Modulos acessiveis:

- Auditoria, RGPD, Relatorios, Listas e consulta controlada de processos.

Acoes permitidas:

- `view`, `audit`, `export` controlado para evidencias.

Acoes proibidas:

- alterar dados operacionais;
- decidir candidaturas;
- gerir pagamentos;
- publicar listas;
- apagar logs.

Riscos associados:

- acesso amplo a informacao sensivel;
- exportacao de evidencias com dados pessoais;
- conflito entre auditoria e operacao.

Notas de seguranca:

- acesso read-only;
- exportacoes justificadas;
- logs de consulta do auditor;
- mascaramento sempre que possivel.
