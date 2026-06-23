# Pre-release Checklist

## Objetivo

Checklist obrigatoria antes de demonstracao formal, beta municipal, staging ou producao.

## Comandos obrigatorios

Executar e guardar evidencias:

```bash
composer validate
php artisan optimize:clear
./vendor/bin/pint --test
php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml
php artisan route:list --except-vendor
./vendor/bin/phpstan analyse --memory-limit=1G -v
npm run build
```

Resultado minimo:

| Comando | Esperado |
| --- | --- |
| `composer validate` | OK |
| `php artisan optimize:clear` | OK |
| `./vendor/bin/pint --test` | OK |
| `phpunit` | 100% verde |
| `route:list --except-vendor` | OK |
| `phpstan analyse` | 0 erros |
| `npm run build` | OK |

## Checklist funcional

- [ ] Login e logout.
- [ ] Registo de utilizador.
- [ ] Area reservada do candidato.
- [ ] Consulta publica de programas.
- [ ] Consulta publica de concursos.
- [ ] Consulta publica de oferta habitacional.
- [ ] Simulador.
- [ ] Registo de adesao.
- [ ] Agregado familiar.
- [ ] Rendimentos.
- [ ] Situacao habitacional.
- [ ] Checklist documental.
- [ ] Upload documental privado.
- [ ] Substituicao/revisao documental.
- [ ] Criacao de candidatura.
- [ ] Submissao formal.
- [ ] Backoffice de concursos.
- [ ] Workflow administrativo.
- [ ] Elegibilidade.
- [ ] Scoring/ranking.
- [ ] Listas provisoria/definitiva.
- [ ] Reclamacoes/audiencia.
- [ ] Sorteios/ordenacao quando aplicavel.
- [ ] Contratos.
- [ ] Rendas/pagamentos.
- [ ] Area do inquilino.
- [ ] Manutencao.
- [ ] Vistorias.
- [ ] Notificacoes/comunicacoes.
- [ ] Relatorios/dashboard.
- [ ] RGPD.
- [ ] Auditoria.

## Checklist seguranca e RGPD

- [ ] `.env` nao e alterado nem exposto.
- [ ] `APP_KEY`, tokens e passwords reais nao aparecem em docs, seeders ou logs.
- [ ] Contas demo usam apenas credenciais de ambiente local/demo.
- [ ] Documentos permanecem em storage privado.
- [ ] Downloads passam por controller autorizado.
- [ ] Policies de dados pessoais continuam aplicadas.
- [ ] Exportacoes RGPD sao auditadas.
- [ ] Retencao/anonimizacao tem teste ou evidencia funcional.
- [ ] Nao ha `@phpstan-ignore`, baseline ou `ignoreErrors`.

## Checklist dados e migrations

- [ ] Migrations novas sao reversiveis.
- [ ] Nao existe `migrate:fresh` em ambiente com dados.
- [ ] Seeders nao contem dados pessoais reais.
- [ ] Seeders demo sao identificados como ficticios.
- [ ] Dados de demonstracao nao usam passwords reais.

## Checklist frontend

- [ ] `npm run build` passa.
- [ ] Layout principal responde em desktop e mobile.
- [ ] Textos nao se sobrepoem em botoes, cards ou tabelas.
- [ ] Acoes principais sao acessiveis por teclado quando aplicavel.
- [ ] Estados vazios e erros sao compreensiveis.

## Decisao de release

Release so deve avancar quando:

- todos os comandos obrigatorios passam;
- nao ha erro PHPStan;
- nao ha regressao de autorizacao;
- nao ha exposicao de documentos privados;
- riscos residuais estao documentados;
- plano de rollback esta definido para ambiente alvo.
