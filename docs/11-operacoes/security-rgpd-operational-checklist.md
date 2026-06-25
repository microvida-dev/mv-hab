# Security and RGPD Operational Checklist

## Objetivo

Checklist final de seguranca e RGPD para operacao municipal.

## Storage e documentos

- [ ] `storage/app/private` nao esta exposto por `public/storage`.
- [ ] Downloads de documentos passam por controller/policy.
- [ ] Documentos privados nao aparecem em sitemap, rotas publicas ou payloads JSON.
- [ ] Paths internos nao aparecem em views, logs ou respostas publicas.

## Autenticacao e autorizacao

- [ ] Backoffice exige autenticacao.
- [ ] Area candidato exige ownership.
- [ ] Area inquilino exige ownership.
- [ ] MFA esta ativo para perfis sensiveis.
- [ ] Roles/permissoes QA-30 permanecem preservadas.
- [ ] Work Tasks respeitam equipa/perfil.

## Auditoria

- [ ] login success/failed/logout auditados.
- [ ] downloads e visualizacoes documentais auditados.
- [ ] scoring/ranking/listas auditaveis.
- [ ] contratos/rendas/pagamentos administrativos auditaveis.
- [ ] visitas/tickets/FAQ auditaveis quando aplicavel.
- [ ] RGPD/exportacoes/anonimizacao auditaveis.

## RGPD

- [ ] Finalidades documentadas.
- [ ] Retencao documentada.
- [ ] Anonimizacao testada em ambiente seguro.
- [ ] DPO/AIPD sob responsabilidade municipal/juridica quando aplicavel.
- [ ] Logs sem dados pessoais desnecessarios.
- [ ] Evidencias QA sem dados reais.

## Segredos

- [ ] `.env` fora do Git.
- [ ] `APP_KEY` nao aparece em docs/testes/evidencias.
- [ ] `DB_PASSWORD` nao aparece em docs/testes/evidencias.
- [ ] Tokens e chaves privadas nao aparecem no repositorio.

## Bloqueadores

- documento privado publico;
- area privada sem autenticacao;
- export sensivel sem policy/auditoria;
- debug ativo em producao;
- secrets em Git;
- logs com dados pessoais reais desnecessarios.
