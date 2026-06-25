# QA-37 — Release Packaging & Secrets Hardening

## Sumario executivo

Foi reforcado o gate de packaging para impedir inclusao de segredos, ficheiros de ambiente, dumps, backups, chaves, documentos reais, paths locais pessoais e dados pessoais em artefactos versionaveis ou externos.

## Alteracoes

- Reforco de `.gitignore` para artefactos sensiveis.
- Criacao de `SecretPatternScanner`.
- Criacao dos scripts `scripts/check-secrets.php` e `scripts/check-release-artifact-safety.php`.
- Criacao de `docs/11-operacoes/release-packaging-safety.md`.
- Testes QA37 e security para scanner e scripts.

## Regras validadas

- `.env` bloqueado; `.env.example` permitido.
- APP_KEY real bloqueado.
- Debug ativo bloqueado em artefactos de staging/demo.
- DB_PASSWORD real bloqueado.
- Chaves privadas, tokens, dumps, backups, zip nao autorizado, storage privado e paths locais bloqueados.
- Padroes compativeis com NIF, NISS, IBAN e moradas sinalizados.

## Riscos residuais

- Scanner local e conservador; resultados devem ser revistos por tecnico antes de publicar dossier externo.
- Documentacao historica anterior a Phase 1 pode conter termos de checklist e deve ficar fora do dossier externo.

## Evidencia

- `storage/qa/qa-37-tests.txt`: PASS, 3 testes, 24 assercoes.
- `storage/qa/phase-1-secret-scan.txt`: PASS.
- `storage/qa/phase-1-artifact-safety.txt`: PASS.
- `storage/qa/phase-1-diff-check.txt`: PASS.

## Decisao

PASS.
