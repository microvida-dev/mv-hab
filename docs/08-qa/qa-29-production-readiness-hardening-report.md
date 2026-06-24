# QA-29 Production Readiness, Disaster Recovery & Enterprise Hardening Report

## 1. Executive summary

QA-29 validou a prontidão técnica da MV HAB para demonstração municipal avançada e staging. A sprint manteve code freeze funcional: não foram alteradas regras de elegibilidade, scoring, listas, documentos, contratos, rendas ou workflows administrativos.

Foram adicionados testes dirigidos de production readiness, runbooks operacionais e evidências locais de quality gate, security/RGPD, deploy rehearsal, backup, restore, rollback e smoke tests.

Resultado técnico: todos os gates executados passaram, incluindo PHPUnit completo, PHPStan com 0 erros, Pint, build frontend, route list e smoke QA29.

Decisão final: **READY_FOR_STAGING**.

## 2. Scope

Incluído:

- inventário de release;
- quality gate local;
- smoke tests públicos e privados;
- validação de autenticação em áreas privadas;
- validação de storage privado;
- validação de documentos públicos/privados do portal;
- verificação Security/RGPD por testes e evidências;
- runbook de backup/restore;
- runbook de deploy rehearsal;
- runbook de restore/rollback;
- relatório de decisão de release.

Excluído:

- novas funcionalidades de negócio;
- deploy real em servidor externo;
- restore real sobre cópia produtiva;
- integração de assinatura digital;
- gateway de pagamentos/reconciliação bancária;
- reescrita de relatórios históricos fora do âmbito QA-29.

## 3. QA-27/QA-28 exclusion note

QA-27 e QA-28 foram adiadas. Por isso, esta sprint não declara produção final completa.

Assinatura digital e pagamentos/reconciliação bancária permanecem fora do âmbito validado nesta branch. Se esses módulos forem obrigatórios para o release municipal, a decisão **PRODUCTION_READY** fica bloqueada até validação dedicada.

## 4. Release inventory

Evidência: `storage/qa/qa-29-release-inventory.txt`.

Resumo:

| Item | Resultado |
| --- | --- |
| Branch | `qa/qa-29-production-readiness-hardening` |
| Commit base validado | `4fd15f8 test: validate contracts rent and tenant portal` |
| Remote | `origin` via GitHub SSH |
| Rotas | `php artisan route:list --except-vendor` passou com 1083 rotas |
| Migrations | `php artisan migrate:status` executado no inventário |
| Working tree | controlada com ficheiros QA-29 novos/alterados |
| Ficheiros sensíveis versionados | `.env` não está versionado |

Observação: os relatórios QA-22 a QA-25 referidos no contexto não existem nesta branch; existe relatório QA-26.

## 5. Quality gate results

Evidências:

- `storage/qa/qa-29-composer.txt`
- `storage/qa/qa-29-optimize-clear.txt`
- `storage/qa/qa-29-pint.txt`
- `storage/qa/qa-29-phpunit.txt`
- `storage/qa/qa-29-phpstan.txt`
- `storage/qa/qa-29-build.txt`
- `storage/qa/qa-29-diff-check.txt`

Resultados:

| Comando | Resultado |
| --- | --- |
| `composer validate` | PASS |
| `php artisan optimize:clear` | PASS |
| `./vendor/bin/pint --test` | PASS |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml` | PASS, 293 testes, 1880 asserções |
| `php -d memory_limit=-1 ./vendor/bin/phpunit --configuration phpunit.xml --filter QA29` | PASS, 4 testes, 47 asserções |
| `php artisan route:list --except-vendor` | PASS, 1083 rotas |
| `./vendor/bin/phpstan analyse --memory-limit=1G -v` | PASS, 0 erros |
| `npm run build` | PASS |
| `git diff --check` | PASS |

## 6. Security/RGPD gate results

Evidência: `storage/qa/qa-29-security-rgpd.txt`.

Validado:

- áreas privadas redirecionam guest para login;
- portal público carrega sem expor morada interna de habitação pública marcada como não visível;
- ficha pública não expõe caminhos internos dos documentos;
- documento público descarrega apenas quando marcado como público;
- documento público marcado como privado devolve 404;
- storage privado não é exposto por URL direto;
- disco local aponta para área privada;
- link público aponta apenas para área pública;
- `.env` não está versionado;
- não há `vendor`, `node_modules`, logs runtime ou framework runtime versionados, exceto ficheiros keepers do Laravel;
- contagem de ficheiros de log com padrões sensíveis devolveu 0.

Caveat: a verificação ampla pedida para `docs/08-qa` encontra matches antigos em relatórios históricos e matches esperados em nomes de rotas de autenticação dentro de `route:list`. Não foram identificados valores reais de credenciais nos novos artefactos QA-29.

## 7. Backup readiness

Runbook criado: `docs/08-qa/qa-29-backup-restore-runbook.md`.

Cobertura:

- backup de base de dados MySQL/MariaDB;
- backup de storage privado;
- backup do ficheiro de ambiente fora do Git;
- build frontend por reconstrução ou artefacto;
- retenção;
- encriptação;
- localização fora do servidor aplicacional;
- responsáveis;
- comandos exemplo de backup e restore;
- validação pós-restore.

Estado: pronto para ensaio operacional em staging.

## 8. Restore readiness

Runbooks criados:

- `docs/08-qa/qa-29-backup-restore-runbook.md`
- `docs/08-qa/qa-29-restore-rollback-runbook.md`

Cobertura:

- restore de base de dados;
- restore de storage privado;
- validação de migrations;
- smoke tests pós-restore;
- critérios de falha;
- validação de permissões e acesso documental.

Estado: documentado e pronto para ensaio. Restore real não foi executado para evitar manipulação de dados privados.

## 9. Rollback readiness

Runbook criado: `docs/08-qa/qa-29-restore-rollback-runbook.md`.

Cobertura:

- rollback de código por commit/tag;
- rollback de build frontend;
- restore DB quando necessário;
- restore de storage privado;
- validação pós-rollback;
- critérios para abortar release;
- tempo máximo aceitável para staging/pre-produção;
- responsáveis.

Estado: pronto para rehearsal.

## 10. Smoke test results

Evidência: `storage/qa/qa-29-smoke-tests.txt`.

Coberto por `tests/Feature/QA29ProductionReadinessTest.php`:

- homepage pública;
- programas públicos;
- listagem de concursos públicos;
- detalhe de concurso;
- oferta habitacional;
- listagem e ficha de habitação;
- mapa habitacional;
- download público autorizado;
- bloqueio de documento privado;
- redirect de guest em dashboard, área candidato, documentos, área inquilino e backoffice;
- storage privado não exposto diretamente;
- guardrails críticos de configuração.

Resultado: PASS, 4 testes, 47 asserções.

## 11. Known risks

- QA-27 não foi executada: assinatura digital não está validada para release final.
- QA-28 não foi executada: gateway de pagamentos e reconciliação bancária não estão validados para release final.
- Restore real e rollback real ainda precisam de rehearsal num ambiente isolado.
- Queue workers, scheduler e permissões do servidor devem ser validados no ambiente alvo.
- A verificação ampla de documentação histórica contém matches legados que devem ser limpos antes de publicação externa dos relatórios.
- `route:cache` está documentado no runbook, mas não foi executado como gate obrigatório desta sprint.

## 12. Accepted risks

Aceites para staging:

- assinatura digital fora do âmbito;
- pagamentos/reconciliação fora do âmbito;
- restore/rollback documentados, mas ainda não ensaiados contra cópia real;
- matches legados em documentação histórica tratados como risco documental, não como exposição nova QA-29;
- staging sem tráfego público real até rehearsal operacional.

Não aceites para produção final:

- ausência de QA-27/QA-28 se assinatura digital ou pagamentos forem obrigatórios;
- release sem restore testado;
- release sem confirmação de configuração real do servidor;
- release sem validação de filas/scheduler no ambiente alvo.

## 13. Blocking issues

Para staging: nenhum blocker técnico encontrado nos gates locais.

Para produção final: bloqueado até decisão formal sobre âmbito de assinatura digital e pagamentos/reconciliação, e até execução de restore/rollback rehearsal em ambiente controlado.

## 14. Release decision

**READY_FOR_STAGING**

Justificação:

- quality gate completo passou;
- PHPStan está a 0 erros;
- testes QA29 cobrem smoke/security mínimo;
- storage privado e ownership básico continuam protegidos;
- runbooks de backup, deploy, restore e rollback foram criados;
- não houve alteração funcional de domínio;
- QA-27/QA-28 foram excluídas, impedindo declaração de produção completa.

Próximo passo recomendado: executar deploy rehearsal em staging com backup e restore testados antes de qualquer decisão de produção municipal.
