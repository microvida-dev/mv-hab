# Disaster Recovery Rehearsal Runbook

## Objetivo

Ensaiar, em ambiente nao produtivo e descartavel, a capacidade de recuperar a MV HAB a partir de backup e de reverter uma release municipal sem perda de rastreabilidade.

Este runbook e para staging controlado. Nao executar sobre base real de producao.

## Regras de seguranca

- Usar apenas ambiente nao produtivo.
- nao usar dados reais de cidadaos.
- Nao copiar `.env`, `APP_KEY`, `DB_PASSWORD`, tokens ou chaves para evidencias.
- Nao guardar dumps ou backups no repositorio.
- Nunca executar migrate:fresh em dados reais.
- Nunca executar restore sem confirmacao do ambiente alvo.
- Registar evidencia sanitizada em `storage/qa`.

## Pre-condicoes

- Backup de base de dados disponivel fora do Git.
- Backup de `storage/app/private` disponivel fora do Git.
- `.env` do ambiente alvo preparado fora do Git.
- Release/tag atual e release/tag anterior identificadas.
- Workers parados ou reiniciaveis.
- Janela de ensaio aprovada.

## Ensaio seco local

```bash
php artisan mvhab:operations:dr-rehearsal --dry-run
```

O comando valida apenas runbooks e checklists. Nao executa backup, restore, rollback, migracoes nem alteracoes de storage.

## Restore rehearsal em staging descartavel

1. Confirmar que o ambiente e nao produtivo.
2. Confirmar que nao existem dados reais.
3. Entrar em maintenance mode.
4. Restaurar base de dados a partir de backup sanitizado.
5. Restaurar `storage/app/private` a partir de backup sanitizado.
6. Executar `php artisan optimize:clear`.
7. Executar `php artisan migrate:status`.
8. Executar `php artisan route:list --except-vendor`.
9. Reiniciar workers com `php artisan queue:restart`.
10. Executar smoke municipal.
11. Registar resultado e riscos.

## Rollback rehearsal em staging descartavel

1. Identificar commit/tag atual e commit/tag anterior.
2. Entrar em maintenance mode.
3. Fazer checkout de `<previous_release_ref>`.
4. Executar `composer install --no-dev --optimize-autoloader`.
5. Executar `npm ci` e `npm run build`.
6. Executar `php artisan optimize:clear`.
7. Executar `php artisan config:cache`, `route:cache` e `view:cache`.
8. Executar `php artisan queue:restart`.
9. Executar smoke municipal.
10. Sair de maintenance mode se smoke passar.

## Criterios de falha

- Restore nao permite login.
- Homepage, concursos ou backoffice falham.
- Documentos privados ficam publicos.
- Area autenticada deixa de exigir login.
- `route:list` falha.
- Workers nao arrancam.
- Smoke municipal falha.
- Logs mostram erro critico repetido.

## Evidencia

Registar em `storage/qa/phase-2-dr-rehearsal.txt`:

- ambiente usado;
- commit/tag atual;
- commit/tag anterior;
- hora de inicio/fim;
- operador;
- comandos executados;
- resultado de smoke;
- decisao final.
