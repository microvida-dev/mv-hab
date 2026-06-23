# Security and RGPD Guardrails

## Objetivo

Definir regras permanentes para impedir regressao de seguranca, RGPD, auditoria e protecao documental na plataforma MV HAB.

## Principios

- Documentos sao privados por defeito.
- Dados pessoais so sao tratados quando necessarios para finalidade documentada.
- Acesso sensivel deve ser autorizado, minimizado e auditado.
- Decisoes administrativas devem ser rastreaveis.
- IA local pode apoiar analise documental, mas nao decide automaticamente exclusoes ou atribuicoes.

## Guardrails de autorizacao

- Areas reservadas exigem autenticacao.
- Candidato so acede aos seus proprios dados.
- Inquilino so acede aos seus contratos, pagamentos, pedidos e vistorias.
- Backoffice exige permissao/role adequada.
- Alargamento de permissao exige teste.
- Policies nao devem ser contornadas por queries diretas em controllers.

## Guardrails de documentos privados

- Uploads ficam em disco privado.
- Downloads passam por controller autorizado.
- Paths internos nao sao expostos em views, responses ou logs.
- Download de documento sensivel deve gerar auditoria quando aplicavel.
- Substituicao documental deve preservar historico ou evento auditavel.
- Documentos adicionais sem contexto valido devem falhar com erro controlado.

## Guardrails RGPD

| Area | Regra |
| --- | --- |
| Consentimentos | Guardar finalidade, versao, timestamp e origem quando aplicavel. |
| Pedidos de titular | Exigir autorizacao e trilho de estado. |
| Exportacao | Deve ser auditada e minimizar dados desnecessarios. |
| Anonimizacao | Deve ser testada e irreversivel para campos definidos. |
| Retencao | Execucao exige politica associada e registo auditavel. |
| Logs | Nao guardar PII desnecessaria. |
| IA documental | Guardar JSON bruto protegido e limitar exposicao dos campos extraidos. |

## Guardrails de MFA e autenticacao

- MFA nao pode ser enfraquecido sem decisao explicita e teste.
- Fluxos de reset/password devem manter protecoes Laravel.
- Sessao e CSRF nao devem ser desativados em rotas protegidas.

## Guardrails de auditoria

Devem ser auditadas, quando aplicavel:

- submissao e alteracao de candidatura;
- upload, substituicao e download de documentos sensiveis;
- decisoes de elegibilidade, scoring, ranking, listas e atribuicao;
- publicacoes administrativas;
- contratos, rendas e pagamentos;
- pedidos RGPD, exportacao, anonimizacao e retencao;
- acessos sensiveis no backoffice;
- alteracoes de permissoes.

## Checklist para alteracoes criticas

- [ ] Existe policy ou gate aplicavel.
- [ ] Existe teste de acesso permitido.
- [ ] Existe teste de acesso negado.
- [ ] Dados pessoais nao sao expostos fora do necessario.
- [ ] Storage privado e mantido.
- [ ] Auditoria e preservada.
- [ ] Nao ha logs com PII desnecessaria.
- [ ] PHPStan, Pint e PHPUnit passam.

## Regressao bloqueante

Qualquer uma das seguintes situacoes deve bloquear release:

- documento privado acessivel sem autorizacao;
- candidato a aceder a dados de outro candidato;
- inquilino a aceder a dados de outro inquilino;
- backoffice sem permissao a aceder a dados sensiveis;
- export RGPD sem auditoria;
- anonimizacao sem teste;
- permissao alargada sem cobertura automatizada.
