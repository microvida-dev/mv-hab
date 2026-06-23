# Reutilizacao de Dados em Futuras Candidaturas

## Objetivo

Reduzir repeticao de preenchimento pelo candidato, permitindo reutilizar dados previamente declarados e validados, sempre com confirmacao explicita e aviso de responsabilidade.

## Texto obrigatorio apresentado ao candidato

Os dados reutilizados devem ser revistos e confirmados antes de nova candidatura. Dados desatualizados, incompletos ou alterados podem afetar a elegibilidade, a pontuação ou a análise do processo.

## Dados elegiveis

- perfil de candidato;
- agregado familiar;
- rendimentos declarados;
- situacao habitacional;
- preferencias e historico de simulacao, quando aplicavel.

## Dados nao reutilizados automaticamente como validos

- documentos expirados;
- documentos rejeitados;
- comprovativos sujeitos a prazo;
- decisoes administrativas;
- pontuacoes;
- estados de elegibilidade;
- rankings;
- listas publicadas;
- dados de terceiros sem base de reutilizacao.

## Fluxo

1. Candidato consulta perfil de reutilizacao.
2. Plataforma indica dados disponiveis e alertas.
3. Candidato escolhe candidatura origem e candidatura destino.
4. Candidato confirma declaracao obrigatoria.
5. Plataforma regista snapshot e evento de timeline.
6. Nova candidatura deve ser revista e confirmada pelo candidato antes de submissao.

## Salvaguardas RGPD

- reutilizacao apenas com finalidade compativel;
- minimizacao de campos;
- consentimento/confirmacao com timestamp;
- registo da origem e destino da reutilizacao;
- documentos nao sao promovidos automaticamente a validos;
- candidato mantem responsabilidade de revisao.

## Limites atuais

- a Sprint 23 cria a camada de tracking e confirmacao de reutilizacao;
- a aplicacao operacional campo-a-campo deve respeitar cada fluxo de candidatura e validacao documental;
- integracoes externas de validacao permanecem fora do ambito.
