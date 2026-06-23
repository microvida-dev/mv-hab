# Centro de Notificacoes do Candidato

## Objetivo

O centro de notificacoes reune comunicacoes processuais relevantes para o candidato, com estados de leitura, arquivo e ligacao ao processo quando aplicavel.

## Reutilizacao da infraestrutura existente

A Sprint 23 reutiliza `official_notifications` e a infraestrutura da Sprint 16, evitando criar um sistema paralelo de notificacoes.

## Funcionalidades implementadas

- lista de notificacoes do candidato;
- filtros e contadores por estado;
- leitura e arquivo controlados;
- ligacao a candidatura, concurso, contrato ou entidade relacionada quando disponivel;
- registo de leitura na timeline quando a notificacao esta associada a uma candidatura;
- preservacao das rotas oficiais existentes para detalhe e tomada de conhecimento.

## Estados funcionais

- nao lida;
- lida;
- arquivada;
- expirada;
- requer acao.

## Regras de seguranca

- o candidato so visualiza notificacoes dirigidas ao proprio utilizador;
- a tomada de conhecimento continua sujeita as regras da notificacao oficial;
- notificacoes nao devem expor documentos sensiveis sem controller autorizado;
- dados de terceiros nao devem constar no conteudo visivel ao candidato.

## Limites atuais

- email, SMS e postal real dependem da configuracao da Sprint 16;
- o centro nao prova rececao externa certificada;
- comunicacoes juridicamente relevantes devem ser validadas pelo municipio antes de producao.
