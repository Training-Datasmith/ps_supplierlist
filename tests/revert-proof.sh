#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 2 ]; then
  echo "usage: $0 <test-class-or-filter> <mutation-description>" >&2
  exit 2
fi

TEST_FILTER="$1"
MUTATION_NAME="$2"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCKER_BIN="${DOCKER:-docker}"
PHP_IMAGE='php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143'
PHPUNIT_URL='https://phar.phpunit.de/phpunit-4.8.36.phar'
PHPUNIT_ASC_URL='https://phar.phpunit.de/phpunit-4.8.36.phar.asc'
PHPUNIT_SHA256='ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e'
PHPUNIT_FPR='D8406D0D82947747293778314AA394086372C20A'
SEED="${SEED:-20261108}"

CLASS_NAME="${TEST_FILTER%%::*}"
TEST_FILE="/src/tests/Unit/${CLASS_NAME}.php"

WORK_DIR="$(mktemp -d)"
EXPORT_DIR="$(mktemp -d)"
GNUPGHOME="$(mktemp -d)"
chmod 0700 "$WORK_DIR" "$EXPORT_DIR" "$GNUPGHOME"
export GNUPGHOME

cleanup() {
  rm -rf "$WORK_DIR" "$EXPORT_DIR" "$GNUPGHOME"
}
trap cleanup EXIT

verify_phar() {
  local phar="$1"
  local asc="$2"
  local actual
  actual="$(sha256sum "$phar" | awk '{print $1}')"
  if [ "$actual" != "$PHPUNIT_SHA256" ]; then
    echo "phpunit sha256 mismatch: $actual" >&2
    exit 1
  fi
  gpg --batch --homedir "$GNUPGHOME" --keyserver hkps://keys.openpgp.org --recv-keys "$PHPUNIT_FPR" >/dev/null 2>&1
  VERIFY_LOG="$(gpg --batch --status-fd 1 --homedir "$GNUPGHOME" --verify "$asc" "$phar" 2>/dev/null)"
  local validsig_count
  validsig_count="$(echo "$VERIFY_LOG" | grep -c '^\[GNUPG:\] VALIDSIG ' || true)"
  if [ "$validsig_count" -ne 1 ]; then
    echo "expected exactly one VALIDSIG line, got $validsig_count" >&2
    exit 1
  fi
  local validsig_line
  validsig_line="$(echo "$VERIFY_LOG" | grep '^\[GNUPG:\] VALIDSIG ')"
  local field3 field_last
  field3="$(echo "$validsig_line" | awk '{print $3}')"
  field_last="$(echo "$validsig_line" | awk '{print $NF}')"
  if [ "$field3" != "$PHPUNIT_FPR" ] || [ "$field_last" != "$PHPUNIT_FPR" ]; then
    echo "phpunit VALIDSIG fingerprint mismatch" >&2
    exit 1
  fi
  echo "$VERIFY_LOG" | grep -q "VALIDSIG $PHPUNIT_FPR" || { echo "phpunit signature verification failed" >&2; exit 1; }
}

curl -fsSL -o "$WORK_DIR/phpunit-4.8.36.phar" "$PHPUNIT_URL"
curl -fsSL -o "$WORK_DIR/phpunit-4.8.36.phar.asc" "$PHPUNIT_ASC_URL"
verify_phar "$WORK_DIR/phpunit-4.8.36.phar" "$WORK_DIR/phpunit-4.8.36.phar.asc"

git -C "$REPO_ROOT" archive HEAD | tar -x -C "$EXPORT_DIR"

TARGET_FILE="$EXPORT_DIR/ps_supplierlist.php"
if [ ! -f "$TARGET_FILE" ]; then
  echo "missing export target $TARGET_FILE" >&2
  exit 1
fi

BASELINE_BEFORE="$(sha256sum "$TARGET_FILE" | awk '{print $1}')"

run_phpunit_export() {
  local export_root="$1"
  local outfile_label="$2"
  $DOCKER_BIN run --rm \
    --network none \
    -v "$export_root:/src:ro" \
    -v "$WORK_DIR:/work" \
    -e SEED="$SEED" \
    -e TMPDIR=/work/tmp \
    -w /work \
    "$PHP_IMAGE" \
    bash -lc "set -euo pipefail
php /src/tests/generate-suite.php $SEED /work/phpunit.xml
set +e
php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 \
  /work/phpunit-4.8.36.phar \
  --configuration /work/phpunit.xml \
  '$TEST_FILE' \
  --filter '$TEST_FILTER' \
  2>&1 | tee /work/${outfile_label}.out
code=\${PIPESTATUS[0]}
set -e
printf '%s' \"\$code\" > /work/${outfile_label}.code
"
}

mkdir -p "$WORK_DIR/tmp"
run_phpunit_export "$EXPORT_DIR" baseline
BASE_OUT="$(cat "$WORK_DIR/baseline.out")"
BASE_CODE="$(cat "$WORK_DIR/baseline.code")"
echo "$BASE_OUT" | grep -q 'OK (' || { echo "baseline did not pass" >&2; exit 6; }
[ "$BASE_CODE" -eq 0 ] || { echo "baseline phpunit exit $BASE_CODE" >&2; exit 6; }

case "$MUTATION_NAME" in
  supplier-row-link)
    perl -pi -e 's/getSupplierLink\(\$suppliers\[\$key\]\[\x27id_supplier\x27\]\)/getSupplierLink(\$suppliers[\$key])/' \
      "$TARGET_FILE"
    ;;
  remove-display-guard)
    perl -0pi -e 's/if \(!Configuration::get\(\x27PS_DISPLAY_SUPPLIERS\x27\)\) \{\s*return;\s*\}//s' \
      "$TARGET_FILE"
    ;;
  remove-right-hook)
    perl -pi -e 's/ && \$this->registerHook\(\x27displayRightColumn\x27\)//' \
      "$TARGET_FILE"
    ;;
  *)
    echo "unknown mutation: $MUTATION_NAME" >&2
    exit 2
    ;;
esac

BASELINE_AFTER="$(sha256sum "$TARGET_FILE" | awk '{print $1}')"
if [ "$BASELINE_BEFORE" = "$BASELINE_AFTER" ]; then
  echo "mutation did not change export" >&2
  exit 7
fi

case "$MUTATION_NAME" in
  supplier-row-link)
    grep -q 'getSupplierLink(\$suppliers\[$key])' "$TARGET_FILE" || { echo "mutation marker missing" >&2; exit 7; }
    ;;
  remove-display-guard)
    grep -q "if (!Configuration::get('PS_DISPLAY_SUPPLIERS'))" "$TARGET_FILE" && { echo "display guard still present" >&2; exit 7; } || true
    ;;
  remove-right-hook)
    grep -q 'displayRightColumn' "$TARGET_FILE" && { echo "displayRightColumn hook still present" >&2; exit 7; } || true
    ;;
esac

run_phpunit_export "$EXPORT_DIR" proof
PROOF_OUT="$(cat "$WORK_DIR/proof.out")"
PROOF_CODE="$(cat "$WORK_DIR/proof.code")"
echo "$PROOF_OUT" | grep -Eq '^(There was|There were) [0-9]+ errors?:' && exit 3
echo "$PROOF_OUT" | grep -Eq 'Failures: [1-9]' || exit 4
[ "$PROOF_CODE" -ne 0 ] || exit 5

echo "Proof passed for filter $TEST_FILTER mutation $MUTATION_NAME"
