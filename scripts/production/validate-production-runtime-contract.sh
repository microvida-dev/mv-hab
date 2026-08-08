#!/usr/bin/env bash
set -Eeuo pipefail

MODE="auto"
for argument in "$@"; do
  case "$argument" in
    --mode=auto) MODE="auto" ;;
    --mode=source) MODE="source" ;;
    --mode=artifact) MODE="artifact" ;;
    *) printf 'PRODUCTION_RUNTIME_CONTRACT_VALIDATION=FAIL\nERROR=Argumento desconhecido: %s\n' "$argument" >&2; exit 1 ;;
  esac
done

fail() {
  printf 'PRODUCTION_RUNTIME_CONTRACT_VALIDATION=FAIL\nERROR=%s\n' "$1" >&2
  exit 1
}

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
GIT_ROOT="$(git -C "$ROOT" rev-parse --show-toplevel 2>/dev/null || true)"
IS_SOURCE=0
[[ -n "$GIT_ROOT" && "$GIT_ROOT" == "$ROOT" ]] && IS_SOURCE=1

case "$MODE" in
  auto) [[ "$IS_SOURCE" -eq 1 ]] && MODE="source" || MODE="artifact" ;;
  source) [[ "$IS_SOURCE" -eq 1 ]] || fail "Modo source exige checkout Git na raiz da aplicação." ;;
  artifact) ;;
  *) fail "Modo de validação inválido: $MODE" ;;
esac

cd "$ROOT"
LIB="scripts/production/lib/mvhab-production-runtime.sh"
DOC="docs/operations/production-deploy-contract.md"
ROUTE_ASSERT="scripts/production/assert-laravel-route.php"
PERMISSION_TEST="scripts/production/test-production-release-permissions.sh"

[[ -f "$LIB" ]] || fail "Biblioteca em falta: $LIB"
[[ -f "$DOC" ]] || fail "Runbook em falta: $DOC"
[[ -f "$ROUTE_ASSERT" ]] || fail "Route assertion helper em falta: $ROUTE_ASSERT"
[[ -f "$PERMISSION_TEST" ]] || fail "Teste de permissões em falta: $PERMISSION_TEST"
bash -n "$LIB"
bash -n "$PERMISSION_TEST"

grep -F 'chown -h "$app_user:$app_group" "$temp_link"' "$LIB" >/dev/null || fail "Falta chown -h no symlink temporário."
grep -F 'mv -Tf "$temp_link" "$current"' "$LIB" >/dev/null || fail "Falta rename atómico do symlink."
grep -F 'chown -h "$app_user:$app_group" "$current"' "$LIB" >/dev/null || fail "Falta chown -h no symlink final."
grep -F 'mvhab_assert_owned_symlink' "$LIB" >/dev/null || fail "Falta gate explícito de ownership."
grep -F 'mvhab_assert_web_traversal' "$LIB" >/dev/null || fail "Falta gate de travessia web."
grep -F 'mvhab_normalize_release_permissions' "$LIB" >/dev/null || fail "Falta normalização canónica de permissões da release."
grep -F 'mvhab_assert_release_permissions' "$LIB" >/dev/null || fail "Falta gate explícito de permissões da release."
grep -F 'RELEASE_ROOT_MODE_GATE=PASS' "$LIB" >/dev/null || fail "Falta gate do mode 755 na raiz da release."
grep -F 'RELEASE_NO_GROUP_OTHER_WRITE_GATE=PASS' "$LIB" >/dev/null || fail "Falta gate contra conteúdo group/other-writable."
grep -F 'tested_users' "$LIB" >/dev/null || fail "Gate web não exige pelo menos um utilizador existente."
grep -F 'getByName($routeName)' "$ROUTE_ASSERT" >/dev/null || fail "Route assertion não consulta a rota diretamente pelo nome."
grep -F 'ROUTE_ASSERTION=PASS' "$ROUTE_ASSERT" >/dev/null || fail "Route assertion não expõe resultado machine-readable."
grep -F 'mvhab_prepare_app_runtime_dir' "$LIB" >/dev/null || fail "Falta contrato de runtime APP_USER."
grep -F -- '-m 0700' "$LIB" >/dev/null || fail "Falta mode 0700 no runtime."
grep -F -- '-m 0600' "$LIB" >/dev/null || fail "Falta mode 0600 nos scripts runtime."
grep -F 'root:root' "$LIB" >/dev/null || fail "Falta contrato de evidências root:root."
grep -F 'rollback deve usar exatamente a mesma função' "$DOC" >/dev/null || fail "Runbook não fixa a regra de rollback."
grep -F 'antes de reiniciar PHP-FPM' "$DOC" >/dev/null || fail "Runbook não fixa a ordem do ownership gate."
grep -F 'Permissões canónicas da release candidata' "$DOC" >/dev/null || fail "Runbook não documenta as permissões canónicas da release."
grep -F 'Travessia web obrigatória antes do cutover' "$DOC" >/dev/null || fail "Runbook não exige travessia web pré-cutover."
grep -F 'não pode depender de `route:list`' "$DOC" >/dev/null || fail "Runbook não proíbe validação machine-readable por output truncável de route:list."

if [[ "$MODE" == "source" ]]; then
  git diff --check
  echo "VALIDATION_MODE=SOURCE_CHECKOUT"
  echo "GIT_DIFF_CHECK=PASS"
else
  RELEASE_SHA_FILE="$ROOT/.mvhab-release-sha"
  [[ -f "$RELEASE_SHA_FILE" ]] || fail "Modo artifact exige .mvhab-release-sha na raiz da release."
  RELEASE_SHA="$(tr -d '[:space:]' < "$RELEASE_SHA_FILE")"
  [[ "$RELEASE_SHA" =~ ^[0-9a-f]{40}$ ]] || fail ".mvhab-release-sha não contém um SHA Git completo válido."
  echo "VALIDATION_MODE=IMMUTABLE_RELEASE"
  echo "RELEASE_SHA=$RELEASE_SHA"
  echo "GIT_DIFF_CHECK=NOT_APPLICABLE_IMMUTABLE_RELEASE"
fi

echo "BASH_SYNTAX=PASS"
echo "OWNED_TEMP_SYMLINK_CONTRACT=PASS"
echo "ATOMIC_RENAME_CONTRACT=PASS"
echo "OWNED_CURRENT_SYMLINK_CONTRACT=PASS"
echo "WEB_TRAVERSAL_CONTRACT=PASS"
echo "RELEASE_PERMISSION_CONTRACT=PASS"
echo "PRE_CUTOVER_WEB_TRAVERSAL_CONTRACT=PASS"
echo "NON_TRUNCATED_ROUTE_ASSERTION_CONTRACT=PASS"
echo "APP_RUNTIME_CONTRACT=PASS"
echo "PRIVATE_EVIDENCE_CONTRACT=PASS"
echo "PRODUCTION_RUNTIME_CONTRACT_VALIDATION=PASS"
