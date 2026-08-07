#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(git rev-parse --show-toplevel 2>/dev/null || true)"

fail()
{
    printf 'PRODUCTION_RUNTIME_CONTRACT_VALIDATION=FAIL\nERROR=%s\n' "$1" >&2
    exit 1
}

[[ -n "$ROOT" ]] || fail "Não está num repositório Git."
cd "$ROOT"

LIB="scripts/production/lib/mvhab-production-runtime.sh"
DOC="docs/operations/production-deploy-contract.md"

[[ -f "$LIB" ]] || fail "Biblioteca em falta: $LIB"
[[ -f "$DOC" ]] || fail "Runbook em falta: $DOC"

bash -n "$LIB"

grep -F 'chown -h "$app_user:$app_group" "$temp_link"' "$LIB" >/dev/null \
    || fail "Falta chown -h no symlink temporário."

grep -F 'mv -Tf "$temp_link" "$current"' "$LIB" >/dev/null \
    || fail "Falta rename atómico do symlink."

grep -F 'chown -h "$app_user:$app_group" "$current"' "$LIB" >/dev/null \
    || fail "Falta chown -h no symlink final."

grep -F 'mvhab_assert_owned_symlink' "$LIB" >/dev/null \
    || fail "Falta gate explícito de ownership."

grep -F 'mvhab_assert_web_traversal' "$LIB" >/dev/null \
    || fail "Falta gate de travessia web."

grep -F 'mvhab_prepare_app_runtime_dir' "$LIB" >/dev/null \
    || fail "Falta contrato de runtime APP_USER."

grep -F -- '-m 0700' "$LIB" >/dev/null \
    || fail "Falta mode 0700 no runtime."

grep -F -- '-m 0600' "$LIB" >/dev/null \
    || fail "Falta mode 0600 nos scripts runtime."

grep -F 'root:root' "$LIB" >/dev/null \
    || fail "Falta contrato de evidências root:root."

grep -F 'rollback deve usar exatamente a mesma função' "$DOC" >/dev/null \
    || fail "Runbook não fixa a regra de rollback."

grep -F 'antes de reiniciar PHP-FPM' "$DOC" >/dev/null \
    || fail "Runbook não fixa a ordem do ownership gate."

git diff --check

echo "BASH_SYNTAX=PASS"
echo "OWNED_TEMP_SYMLINK_CONTRACT=PASS"
echo "ATOMIC_RENAME_CONTRACT=PASS"
echo "OWNED_CURRENT_SYMLINK_CONTRACT=PASS"
echo "WEB_TRAVERSAL_CONTRACT=PASS"
echo "APP_RUNTIME_CONTRACT=PASS"
echo "PRIVATE_EVIDENCE_CONTRACT=PASS"
echo "GIT_DIFF_CHECK=PASS"
echo "PRODUCTION_RUNTIME_CONTRACT_VALIDATION=PASS"
