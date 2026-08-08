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
2. extrair a release candidata e instalar dependências/assets;
3. normalizar as permissões da release candidata;
4. validar o contrato de permissões da release;
5. validar rotas críticas por objeto de rota, sem parsing de tabelas do console;
6. validar travessia/leitura da release candidata pelos utilizadores web;
7. validar zero migrations inesperadas;
8. preparar rollback;
9. criar symlink temporário;
10. aplicar `chown -h` ao symlink temporário;
11. `mv -Tf` para `current`;
12. aplicar `chown -h` a `current`;
13. validar destino e ownership;
14. repetir a validação de travessia web;
15. só então reiniciar/recarregar PHP-FPM;
16. reiniciar workers/scheduler conforme o plano;
17. validar HTTP;
18. validar workers na release ativa;
19. validar failed jobs;
20. guardar manifesto da operação.

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

## 9. Permissões canónicas da release candidata

Uma release criada a partir de `git archive` não pode assumir que os modes
materializados pelo `tar` são adequados ao Plesk. Foi comprovado em produção
que uma raiz `0750` impede a travessia por `www-data`, `nginx` e `psaadm`, e
que o conteúdo extraído pode manter escrita de grupo (`0775`/`0664`) sem
necessidade operacional.

Antes do cutover, o deploy deve executar:

```bash
mvhab_normalize_release_permissions \
    "$RELEASE" \
    "$APP_USER" \
    "$APP_GROUP"

mvhab_assert_release_permissions \
    "$RELEASE" \
    "$APP_USER" \
    "$APP_GROUP"
```

Contrato:

- raiz da release: `0755`;
- owner da raiz: `APP_USER:APP_GROUP`;
- ficheiros e diretórios da release não podem ser `group/other-writable`;
- bits de execução já existentes são preservados;
- symlinks como `.env` e `storage` não são seguidos pela normalização;
- diretórios que necessitam escrita em runtime continuam escrevíveis pelo
  owner aplicacional.

Não é permitido corrigir permissões apenas depois de um HTTP 403.

## 10. Travessia web obrigatória antes do cutover

A travessia web tem de ser comprovada diretamente na release candidata,
antes de alterar `current`:

```bash
mvhab_assert_web_traversal \
    "$RELEASE" \
    www-data \
    nginx \
    psaadm
```

O gate deve confirmar:

- execução/travessia da raiz da release;
- execução/travessia de `public/`;
- leitura de `public/index.php`;
- pelo menos um dos utilizadores indicados tem de existir no host;
- qualquer utilizador existente que falhe torna o candidato não elegível
  para cutover.

A mesma verificação é repetida sobre `current` imediatamente após o cutover.

## 11. Validação de rotas sem truncamento de console

Uma validação automatizada de rotas não pode depender de `route:list` e de
`grep` sobre a tabela formatada para terminal. A apresentação do Symfony
Console pode truncar o nome da rota sem que a rota esteja ausente.

Para gates machine-readable deve ser usado:

```bash
php scripts/production/assert-laravel-route.php \
    login \
    login \
    GET
```

O helper arranca o Laravel, obtém a rota diretamente de `RouteCollection`
através do nome, compara a URI e exige o método HTTP indicado. O output contém
`ROUTE_NAME`, `ROUTE_URI`, `ROUTE_METHODS` e termina em
`ROUTE_ASSERTION=PASS`.

`php artisan route:list` continua útil para inspeção humana, mas não é uma
fonte de verdade apropriada para decisões automáticas de cutover.
