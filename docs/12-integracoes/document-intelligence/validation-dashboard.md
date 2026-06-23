# Document Intelligence — Painel de Validação

## Objetivo

O painel de Validação IA permite ao backoffice consultar cruzamentos entre documentos e candidaturas, identificar divergências e marcar resultados para revisão manual.

## Rotas

- `backoffice.document-ai.validations.index`: lista de execuções.
- `backoffice.document-ai.validations.show`: detalhe da última execução de uma candidatura.
- `backoffice.document-ai.validations.validation`: detalhe de uma verificação.
- `backoffice.document-ai.validations.manual-review`: marcação manual de revisão.
- `backoffice.document-ai.validations.rerun`: reprocessamento assistido de candidatura.

## Filtros

- estado da execução;
- severidade;
- grupo de validação;
- necessidade de revisão;
- número/public ID/nome associado à candidatura;
- intervalo temporal.

## Segurança visual

- Utilizadores com permissão operacional veem labels, estado, severidade e recomendação.
- Valores sensíveis ficam mascarados sem permissão de auditoria.
- Dados de saúde ficam ocultos sem permissão de privacidade/auditoria.
- Candidatos não acedem a qualquer rota de backoffice.

## Ações

O backoffice pode:

- consultar resumo e detalhe;
- marcar uma verificação para revisão manual;
- reprocessar validações de uma candidatura.

O backoffice não pode, por este painel:

- alterar dados declarados;
- aprovar ou rejeitar candidatura;
- validar documento administrativamente;
- alterar elegibilidade, pontuação ou ranking.
