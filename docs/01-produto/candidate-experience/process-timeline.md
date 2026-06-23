# Timeline Processual

## Objetivo

A timeline processual apresenta o percurso cronologico da candidatura, incluindo submissao, analise, pedidos, notificacoes, audiencias, documentos adicionais, desistencias e reutilizacao de dados.

## Modelo de dados

Tabela principal: `process_timeline_events`.

Campos principais:

- candidatura;
- tipo de evento;
- visibilidade;
- titulo;
- descricao;
- metadata operacional;
- utilizador responsavel;
- entidade relacionada;
- data do evento;
- data de disponibilizacao ao candidato.

## Tipos de evento

- candidatura criada;
- candidatura submetida;
- estado alterado;
- documento solicitado;
- documento submetido;
- documento validado;
- documento rejeitado;
- audiencia aberta;
- resposta a audiencia;
- pedido de aperfeicoamento;
- resposta a aperfeicoamento;
- notificacao emitida;
- notificacao lida;
- visita marcada;
- visita concluida;
- ticket criado;
- ticket atualizado;
- desistência solicitada;
- desistência confirmada;
- reutilizacao de dados solicitada;
- reutilizacao de dados confirmada;
- evento administrativo;
- evento RGPD;
- observacao interna.

## Visibilidade

- `candidate`: visivel ao candidato;
- `backoffice`: visivel ao municipio;
- `internal`: reservado a operacao interna;
- `sensitive`: reservado a perfis autorizados;
- `audit`: reservado a auditoria.

## Regras de apresentacao

- a area do candidato mostra apenas eventos publicos;
- a area municipal mostra eventos publicos, internos e sensiveis conforme autorizacao;
- eventos sao ordenados por data do evento;
- cada evento pode apontar para a entidade de origem atraves de relacao morfica;
- a timeline deve evitar expor caminhos internos de ficheiros, IDs tecnicos desnecessarios ou dados de terceiros.

## Qualidade e auditoria

Cada acao relevante deve ser acompanhada por:

- registo de timeline;
- estado ou resultado associado;
- utilizador que desencadeou a acao, quando aplicavel;
- metadata minimizada e sem dados pessoais desnecessarios;
- compatibilidade com auditoria RGPD.
