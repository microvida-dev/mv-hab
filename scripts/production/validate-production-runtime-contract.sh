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

[[ -f "$LIB" ]] || fail "Biblioteca em falta: $LIB"
[[ -f "$DOC" ]] || fail "Runbook em falta: $DOC"
bash -n "$LIB"

grep -F 'chown -h "$app_user:$app_group" "$temp_link"' "$LIB" >/dev/null || fail "Falta chown -h no symlink temporário."
grep -F 'mv -Tf "$temp_link" "$current"' "$LIB" >/dev/null || fail "Falta rename atómico do symlink."
grep -F 'chown -h "$app_user:$app_group" "$current"' "$LIB" >/dev/null || fail "Falta chown -h no symlink final."
grep -F 'mvhab_assert_owned_symlink' "$LIB" >/dev/null || fail "Falta gate explícito de ownership."
grep -F 'mvhab_assert_web_traversal' "$LIB" >/dev/null || fail "Falta gate de travessia web."
grep -F 'mvhab_prepare_app_runtime_dir' "$LIB" >/dev/null || fail "Falta contrato de runtime APP_USER."
grep -F -- '-m 0700' "$LIB" >/dev/null || fail "Falta mode 0700 no runtime."
grep -F -- '-m 0600' "$LIB" >/dev/null || fail "Falta mode 0600 nos scripts runtime."
grep -F 'root:root' "$LIB" >/dev/null || fail "Falta contrato de evidências root:root."
grep -F 'rollback deve usar exatamente a mesma função' "$DOC" >/dev/null || fail "Runbook não fixa a regra de rollback."
grep -F 'antes de reiniciar PHP-FPM' "$DOC" >/dev/null || fail "Runbook não fixa a ordem do ownership gate."

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
echo "APP_RUNTIME_CONTRACT=PASS"
echo "PRIVATE_EVIDENCE_CONTRACT=PASS"
echo "PRODUCTION_RUNTIME_CONTRACT_VALIDATION=PASS"
