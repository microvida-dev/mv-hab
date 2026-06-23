# Acompanhamento Processual do Candidato

## Objetivo

A camada de acompanhamento processual permite ao candidato consultar, num unico ponto, o estado publico da sua candidatura, proximas etapas, acoes pendentes, notificacoes e historico cronologico.

Esta camada nao substitui o workflow administrativo interno. Ela traduz estados, eventos e decisoes internas para uma experiencia compreensivel, auditavel e limitada ao proprio processo do candidato.

## Ambito implementado

- painel de processos do candidato em `candidate.processes.index`;
- detalhe de processo em `candidate.processes.show`;
- timeline publica em `candidate.processes.timeline`;
- mapeamento de estado interno para estado publico em `application_public_status_snapshots`;
- acoes processuais em `process_actions`;
- eventos cronologicos em `process_timeline_events`;
- segregacao entre eventos publicos, internos e sensiveis;
- area municipal para consultar timeline integral e atualizar o estado publico.

## Estados publicos

Os estados publicos sao guardados como snapshot para evitar que alteracoes futuras de nomenclatura mudem retroativamente a leitura do candidato.

Estados previstos:

- rascunho;
- submetida;
- em analise;
- aperfeicoamento necessario;
- documentacao adicional solicitada;
- audiencia previa;
- em reclamacao;
- elegivel;
- nao elegivel;
- em classificacao;
- lista provisoria;
- lista definitiva;
- atribuicao;
- contrato;
- desistida;
- arquivada;
- encerrada.

## Regras de acesso

- o candidato consulta apenas processos associados ao seu utilizador;
- eventos internos nao sao apresentados na area do candidato;
- eventos sensiveis ficam reservados a perfis autorizados de backoffice;
- o backoffice consulta historico integral quando autorizado;
- as acoes criticas devem gerar evento de timeline e manter rastreabilidade.

## Texto publico da timeline

Esta timeline apresenta o historico do seu processo com base nos atos registados na plataforma. Algumas etapas podem depender de validacao documental, analise tecnica ou decisao dos servicos municipais.

## Limites atuais

- a timeline consolida eventos criados pela plataforma e nao importa historico externo;
- a camada de estado publico depende de configuracao e validacao municipal;
- as notificacoes externas reais continuam dependentes da infraestrutura da Sprint 16;
- integracoes externas permanecem fora do ambito.
