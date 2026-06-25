# Staging Restore Validation Checklist

## Escopo

Checklist para validar restore em ambiente nao produtivo. Nao usar dados reais.

## Antes do restore

- [ ] Ambiente confirmado como staging ou descartavel.
- [ ] `.env` fora do Git.
- [ ] Backup DB validado por checksum.
- [ ] Backup de `storage/app/private` validado por checksum.
- [ ] Workers parados ou controlados.
- [ ] Release atual registada.
- [ ] Plano de rollback disponivel.

## Comandos de apoio

```bash
php artisan down
php artisan optimize:clear
php artisan migrate:status
php artisan route:list --except-vendor
php artisan queue:restart
php artisan up
```

## Smoke pos-restore

- [ ] homepage responde.
- [ ] concursos publicos respondem.
- [ ] mapa/oferta habitacional responde.
- [ ] login funciona.
- [ ] candidatura e area candidato funcionam.
- [ ] documentos privados apenas passam por controller autorizado.
- [ ] backoffice exige autenticacao.
- [ ] listas, contratos e rendas manuais respondem.
- [ ] visitas, tickets e FAQ respondem.
- [ ] auditoria e RGPD respondem.
- [ ] storage privado nao fica exposto publicamente.

## Criterios para abortar

- Falha de login.
- Backoffice sem autenticacao.
- Documentos privados expostos.
- `route:list` falha.
- Erro critico repetido em logs.
- Smoke municipal falha.
- Necessidade de rollback.

## Evidencia

Registar resultado em `storage/qa/qa-36-restore-test.txt` ou `storage/qa/phase-2-dr-rehearsal.txt`, sem segredos e sem dados pessoais.
