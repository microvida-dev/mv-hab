# MV-HAB — Contrato canónico de deploy em produção

## 1. Objetivo

Este documento fixa invariantes de deploy comprovadas no ambiente Plesk da
MV-HAB. O objetivo é impedir regressões operacionais em cutover, rollback e
execução de ferramentas temporárias.

## 2. `current` é um symlink operacional, não apenas um ponteiro

O document root da aplicação depende de:

```text
/var/www/vhosts/microvida.pt/hab.microvida.pt/current/public
```

No ambiente Plesk, a aplicação pode devolver HTTP 403 quando o symlink
`current` é recriado como `root:root`, mesmo quando a release de destino tem
permissões corretas.

### Invariante

O symlink `current` tem de pertencer a:

```text
APP_USER:APP_GROUP
```

### Cutover obrigatório

Todo o cutover e todo o rollback devem usar exatamente a mesma função de
troca atómica:

```bash
ln -s "$destination" "$temp_link"
chown -h "$APP_USER:$APP_GROUP" "$temp_link"

mv -Tf "$temp_link" "$CURRENT"

chown -h "$APP_USER:$APP_GROUP" "$CURRENT"
```

É obrigatório validar, antes de reiniciar PHP-FPM:

```bash
test "$(stat -c '%U:%G' "$CURRENT")" = "$APP_USER:$APP_GROUP"
test "$(readlink -f "$CURRENT")" = "$destination"
```

Depois, validar travessia/leitura pelos utilizadores web existentes, por
exemplo `www-data`, `nginx` e `psaadm`.

## 3. Rollback

O rollback não pode ter uma implementação alternativa de symlink.

Regra:

> rollback deve usar exatamente a mesma função de cutover com ownership.

Assim evitamos recuperar a release anterior mas deixá-la inacessível ao
servidor web.

## 4. Evidências privadas e runtime `runuser`

Evidências de deploy, backups, manifestos e logs podem permanecer:

```text
root:root
0700
```

Contudo, um script PHP/Shell que será aberto por:

```bash
runuser -u "$APP_USER" -- ...
```

não pode ser executado de dentro de um diretório ancestral `root:root 0700`.

### Padrão canónico

Evidências:

```text
private/.../executions/<timestamp>/
owner: root:root
mode: 0700
```

Runtime temporário:

```text
/tmp/mvhab-<operation>-<timestamp>/
owner: APP_USER:APP_GROUP
mode: 0700
```

Scripts runtime:

```text
owner: APP_USER:APP_GROUP
mode: 0600
```

Os outputs finais regressam ao diretório privado de evidências através do
processo root/orquestrador.

## 5. Ordem mínima de cutover

1. validar SHA/tag/artefacto;
2. validar release candidata e dependências;
3. validar zero migrations inesperadas;
4. preparar rollback;
5. criar symlink temporário;
6. aplicar `chown -h` ao symlink temporário;
7. `mv -Tf` para `current`;
8. aplicar `chown -h` a `current`;
9. validar destino e ownership;
10. validar travessia web;
11. só então reiniciar/recarregar PHP-FPM;
12. reiniciar workers/scheduler conforme o plano;
13. validar HTTP;
14. validar workers na release ativa;
15. validar failed jobs;
16. guardar manifesto da operação.

## 6. Falhas

Se qualquer gate posterior ao cutover falhar:

- executar rollback com a mesma função de symlink;
- validar ownership/travessia antes dos serviços;
- nunca executar migrations/seeders como tentativa de reparar um erro HTTP;
- preservar logs e evidências da falha.

## 7. Biblioteca

A implementação reutilizável está em:

```text
scripts/production/lib/mvhab-production-runtime.sh
```

Funções principais:

- `mvhab_atomic_owned_symlink_switch`
- `mvhab_assert_owned_symlink`
- `mvhab_assert_web_traversal`
- `mvhab_prepare_app_runtime_dir`
- `mvhab_assert_private_evidence_dir`

A biblioteca não executa operações quando é carregada; scripts concretos de
deploy devem fazer `source` e usar estas primitivas.

## 8. Validação do contrato: checkout e release imutável

O validator oficial suporta dois contextos sem enfraquecer as invariantes:

```bash
bash scripts/production/validate-production-runtime-contract.sh --mode=source
bash scripts/production/validate-production-runtime-contract.sh --mode=artifact
```

Em `source`, a raiz tem de ser uma checkout Git e `git diff --check` é obrigatório.
Em `artifact`, a release pode ter sido criada por `git archive`, não recebe um
`.git` artificial e tem de conter `.mvhab-release-sha` com o SHA Git completo.
As validações de ownership, rename atómico, travessia web, runtime do `APP_USER`
e evidências privadas são as mesmas. Sem `--mode`, o modo é detetado automaticamente.
