# Sprint 23 — Relatorio de Qualidade

Data de execucao: 2026-06-19

## Resultado geral

A Sprint 23 foi implementada e validada com testes automatizados, rotas, build frontend e Pint. A validacao PHPStan foi executada antes e depois da implementacao, mas a ferramenta devolveu codigo 1 sem output em stdout/stderr e com ficheiros de relatorio vazios.

## PHPStan

Contexto conhecido antes da sprint:

- passivo legado indicado: 2471 erros PHPStan;
- objetivo da sprint: nao aumentar erros e nao introduzir erros novos nos ficheiros criados/alterados;
- configuracao atual: `phpstan.neon` com `level: 8`;
- versao detetada: PHPStan 2.2.2.

Comandos executados:

- `php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint23-before.json`
- `php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=raw`
- `php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --error-format=json > storage/phpstan/sprint23-after.json`
- `php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon > storage/phpstan/sprint23-after.txt`
- `php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon --no-progress --error-format=table`
- `php -d memory_limit=1G ./vendor/bin/phpstan analyse -c phpstan.neon -vvv --no-progress`
- `php -d memory_limit=1G ./vendor/bin/phpstan diagnose -c phpstan.neon`

Resultado:

- todos os comandos PHPStan terminaram com codigo 1;
- nao foi produzida mensagem de erro no terminal;
- `storage/phpstan/sprint23-before.json`: 0 bytes;
- `storage/phpstan/sprint23-after.json`: 0 bytes;
- `storage/phpstan/sprint23-after.txt`: 0 bytes;
- nao foi possivel calcular uma contagem final fiavel;
- nao foi possivel comparar automaticamente erros novos versus erros legados.

Classificacao:

- o PHPStan continua operacional enquanto binario instalado, mas a execucao local encontra-se anomala para diagnostico nesta sprint;
- a sprint nao alterou configuracao PHPStan, nao reduziu nivel, nao criou baseline e nao adicionou ignores;
- a garantia de nao introducao de erro PHPStan novo fica limitada pela falha silenciosa da ferramenta.

Pendencia recomendada:

- investigar porque `analyse` e `diagnose` devolvem codigo 1 sem output;
- separar `tmpDir` de PHPStan do diretorio usado para outputs manuais de relatorio;
- reexecutar PHPStan apos correcao da anomalia e comparar ficheiros criados/alterados na Sprint 23.

## Testes automatizados

Comandos executados:

- `php artisan test --filter=Sprint23ProcessTrackingTest`
- `php artisan test --filter=Sprint11ListsComplaintsHearingTest::test_candidate_can_submit_own_hearing_submission_and_ready_for_allocation_scope_uses_locked_definitive_list`
- `php artisan test`

Resultados:

- Sprint 23: 6 testes, 24 assercoes, OK;
- regressao Sprint 11: 1 teste, 7 assercoes, OK apos ajuste de compatibilidade;
- suite completa: 202 testes, 1282 assercoes, OK.

## Rotas

Comando executado:

- `php artisan route:list`

Resultado:

- 952 rotas listadas;
- sem erro de carregamento de rotas;
- novas rotas de acompanhamento processual, timeline, notificacoes, documentos adicionais, desistencias e reutilizacao de dados ficaram registadas.

## Build e estilo

Comandos executados:

- `npm run build`
- `./vendor/bin/pint`
- `./vendor/bin/pint --test`

Resultados:

- Vite build OK;
- Pint aplicou inicialmente formatacao em ficheiros da sprint;
- Pint final em modo teste OK.

## Regressao identificada e resolvida

Problema:

- a rota antiga `candidate.hearings.submit.store` passou a usar o Form Request novo da Sprint 23;
- o fluxo legado enviava `submission_text`, enquanto o request novo esperava `body`, `application_id` e `subject`.

Correcao:

- `StorePreliminaryHearingSubmissionRequest` normaliza `submission_text` para `body`;
- o request preenche `application_id` e `subject` a partir da audiencia da rota;
- o `store` do `PreliminaryHearingSubmissionController` permite que o service existente mantenha a validacao de dominio e devolva erro `hearing` no caso de acesso indevido, preservando o teste da Sprint 11.

## Cobertura da Sprint 23

Coberto:

- candidato consulta o proprio processo;
- candidato nao consulta processo de terceiro;
- timeline candidata omite eventos internos;
- backoffice consulta timeline integral autorizada;
- notificacoes podem ser marcadas como lidas e arquivadas;
- desistência controlada exige declaracao e gera evento;
- reutilizacao de dados valida ownership e avisa que documentos nao sao copiados como validos;
- estado publico e derivado do estado interno submetido.

Nao coberto nesta sprint:

- browser/E2E com interacao real;
- comparacao visual das novas views;
- volumes elevados de timeline;
- envio externo real de notificacoes;
- OCR, assinatura digital, AT/ISS/IRN ou outras integracoes externas.

## Riscos residuais

- PHPStan precisa de correcao operacional para prova final de erro zero nos ficheiros da sprint;
- textos juridicos de desistência, audiencia, recursos e reutilizacao exigem validacao municipal;
- configuracao de estados publicos deve ser revista antes de producao;
- reutilizacao de dados esta protegida por confirmacao, mas a aplicacao operacional campo-a-campo deve ser validada por processo.
