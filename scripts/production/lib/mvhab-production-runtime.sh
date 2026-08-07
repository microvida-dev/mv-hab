#!/usr/bin/env bash

# MV-HAB production runtime/deploy helpers.
#
# Este ficheiro é uma biblioteca: deve ser carregado com `source`.
# Não executa qualquer operação por si próprio.

mvhab_fail()
{
    printf 'MVHAB_DEPLOY_CONTRACT=FAIL\nERROR=%s\n' "$1" >&2
    return 1
}

mvhab_assert_linux_tools()
{
    local command_name

    for command_name in \
        ln mv chown stat readlink install runuser id test rm mkdir
    do
        command -v "$command_name" >/dev/null 2>&1 \
            || mvhab_fail "Comando obrigatório em falta: $command_name" \
            || return 1
    done
}

mvhab_symlink_owner()
{
    local link="$1"

    stat -c '%U:%G' "$link"
}

mvhab_assert_owned_symlink()
{
    local link="$1"
    local app_user="$2"
    local app_group="$3"
    local expected="${app_user}:${app_group}"
    local actual

    [[ -L "$link" ]] \
        || mvhab_fail "Não é um symlink: $link" \
        || return 1

    actual="$(mvhab_symlink_owner "$link")"

    printf 'SYMLINK=%s\n' "$link"
    printf 'SYMLINK_OWNER=%s\n' "$actual"
    printf 'EXPECTED_SYMLINK_OWNER=%s\n' "$expected"

    [[ "$actual" == "$expected" ]] \
        || mvhab_fail "Ownership incorreto no symlink $link: $actual" \
        || return 1

    printf 'SYMLINK_OWNER_GATE=PASS\n'
}

mvhab_atomic_owned_symlink_switch()
{
    local current="$1"
    local destination="$2"
    local temp_link="$3"
    local app_user="$4"
    local app_group="$5"

    [[ -d "$destination" ]] \
        || mvhab_fail "Release de destino inexistente: $destination" \
        || return 1

    rm -f -- "$temp_link"

    ln -s "$destination" "$temp_link"

    # Regra Plesk comprovada em produção:
    # o symlink temporário recebe ownership antes do rename atómico.
    chown -h "$app_user:$app_group" "$temp_link"

    mvhab_assert_owned_symlink \
        "$temp_link" \
        "$app_user" \
        "$app_group" \
        || {
            rm -f -- "$temp_link"
            return 1
        }

    mv -Tf "$temp_link" "$current"

    # Defesa em profundidade: confirmar/corrigir o ownership do link final.
    chown -h "$app_user:$app_group" "$current"

    [[ "$(readlink -f "$current")" == "$destination" ]] \
        || mvhab_fail "Cutover atómico não aponta para $destination" \
        || return 1

    mvhab_assert_owned_symlink \
        "$current" \
        "$app_user" \
        "$app_group" \
        || return 1

    printf 'OWNED_ATOMIC_SYMLINK_SWITCH=PASS\n'
}

mvhab_assert_web_traversal()
{
    local current="$1"
    shift

    local web_user
    local failures=0

    for web_user in "$@"
    do
        if ! id "$web_user" >/dev/null 2>&1; then
            printf 'WEB_USER=%s NOT_PRESENT\n' "$web_user"
            continue
        fi

        if runuser -u "$web_user" -- test -x "$current" \
            && runuser -u "$web_user" -- test -x "$current/public" \
            && runuser -u "$web_user" -- test -r "$current/public/index.php"
        then
            printf 'WEB_ACCESS user=%s result=PASS\n' "$web_user"
        else
            printf 'WEB_ACCESS user=%s result=FAIL\n' "$web_user"
            failures=1
        fi
    done

    [[ "$failures" -eq 0 ]] \
        || mvhab_fail "A release ativa não é atravessável pelos utilizadores web." \
        || return 1

    printf 'WEB_TRAVERSAL_GATE=PASS\n'
}

mvhab_prepare_app_runtime_dir()
{
    local runtime_dir="$1"
    local app_user="$2"
    local app_group="$3"
    shift 3

    local source
    local destination

    [[ "$#" -gt 0 ]] \
        || mvhab_fail "Indique pelo menos um script runtime." \
        || return 1

    rm -rf -- "$runtime_dir"

    install \
        -d \
        -o "$app_user" \
        -g "$app_group" \
        -m 0700 \
        "$runtime_dir"

    for source in "$@"
    do
        [[ -f "$source" ]] \
            || mvhab_fail "Script runtime inexistente: $source" \
            || return 1

        destination="${runtime_dir}/$(basename "$source")"

        install \
            -o "$app_user" \
            -g "$app_group" \
            -m 0600 \
            "$source" \
            "$destination"

        runuser -u "$app_user" -- test -r "$destination" \
            || mvhab_fail "O utilizador aplicacional não consegue ler $destination" \
            || return 1
    done

    [[ "$(stat -c '%U:%G' "$runtime_dir")" == "${app_user}:${app_group}" ]] \
        || mvhab_fail "Ownership incorreto no runtime $runtime_dir" \
        || return 1

    [[ "$(stat -c '%a' "$runtime_dir")" == "700" ]] \
        || mvhab_fail "Mode incorreto no runtime $runtime_dir" \
        || return 1

    printf 'APP_RUNTIME_DIR=%s\n' "$runtime_dir"
    printf 'APP_RUNTIME_ACCESS_GATE=PASS\n'
}

mvhab_assert_private_evidence_dir()
{
    local evidence_dir="$1"

    [[ -d "$evidence_dir" ]] \
        || mvhab_fail "Diretório de evidências inexistente: $evidence_dir" \
        || return 1

    [[ "$(stat -c '%U:%G' "$evidence_dir")" == "root:root" ]] \
        || mvhab_fail "Evidências devem permanecer root:root: $evidence_dir" \
        || return 1

    [[ "$(stat -c '%a' "$evidence_dir")" == "700" ]] \
        || mvhab_fail "Evidências devem permanecer mode 700: $evidence_dir" \
        || return 1

    printf 'PRIVATE_EVIDENCE_DIR_GATE=PASS\n'
}
