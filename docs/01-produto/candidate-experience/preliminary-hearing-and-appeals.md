# Audiencia Previa, Reclamacoes e Recursos

## Objetivo

Permitir que o candidato acompanhe e responda a etapas de audiencia previa, reclamacao ou recurso dentro do processo, preservando prazos, conteudo submetido e decisao municipal.

## Ambito implementado

- consulta de pedidos de audiencia associados ao candidato;
- formulario de resposta do candidato;
- validacao de texto, declaracao e anexos ja existentes quando aplicavel;
- decisao municipal sobre submissao;
- registo de eventos na timeline;
- consulta municipal das respostas.

## Fluxo

1. Municipio abre audiencia ou etapa equivalente.
2. Candidato consulta a notificacao e a timeline.
3. Candidato submete resposta dentro do prazo.
4. Municipio analisa a resposta.
5. Municipio regista decisao ou encaminhamento.
6. Timeline e estado publico sao atualizados.

## Regras

- respostas fora do prazo devem ser bloqueadas ou sinalizadas;
- o candidato so responde a etapas do seu proprio processo;
- a decisao municipal deve ser registada por perfil autorizado;
- anexos sensiveis devem usar storage privado e controllers autorizados;
- a resposta do candidato deve ficar imutavel apos submissao, salvo regra municipal expressa.

## Limites atuais

- nao existe integracao com assinatura digital qualificada;
- recursos hierarquicos externos e contencioso nao sao automatizados;
- textos e prazos devem ser revistos juridicamente por concurso.
