#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCKER_BIN="${DOCKER:-docker}"
PHP_IMAGE='php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143'
PHPUNIT_URL='https://phar.phpunit.de/phpunit-4.8.36.phar'
PHPUNIT_ASC_URL='https://phar.phpunit.de/phpunit-4.8.36.phar.asc'
PHPUNIT_SHA256='ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e'
PHPUNIT_FPR='D8406D0D82947747293778314AA394086372C20A'
SEED="${SEED:-20261108}"

TEST_COUNT="$(awk '/testCount/ { match($0, /[0-9]+/); print substr($0, RSTART, RLENGTH); exit }' "$REPO_ROOT/tests/expected-suite.php")"
if [ -z "$TEST_COUNT" ] || ! [[ "$TEST_COUNT" =~ ^[0-9]+$ ]]; then
  echo "unable to read testCount from expected-suite.php" >&2
  exit 1
fi

WORK_DIR="$(mktemp -d)"
GNUPGHOME="$(mktemp -d)"
chmod 0700 "$WORK_DIR" "$GNUPGHOME"
export GNUPGHOME
cleanup() {
  rm -rf "$WORK_DIR" "$GNUPGHOME"
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

mkdir -p "$WORK_DIR/tmp"

$DOCKER_BIN pull "$PHP_IMAGE" >/dev/null

$DOCKER_BIN run --rm \
  --network none \
  -v "$REPO_ROOT:/src:ro" \
  -v "$WORK_DIR:/work" \
  -e SEED="$SEED" \
  -e TMPDIR=/work/tmp \
  -w /work \
  "$PHP_IMAGE" \
  bash -lc 'set -euo pipefail
php -r "if (PHP_VERSION !== \"5.6.40\") { fwrite(STDERR, \"unexpected PHP \" . PHP_VERSION . \"\n\"); exit(1);} "
for ext in tokenizer phar; do
  php -m | grep -qi "^$ext\$" || { echo "missing extension $ext" >&2; exit 1; }
done
php /src/tests/generate-suite.php "$SEED" /work/phpunit.xml
php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 \
  /work/phpunit-4.8.36.phar \
  --configuration /work/phpunit.xml \
  --log-junit /work/junit.xml \
  2>&1 | tee /work/phpunit.out
'

OUT="$(cat "$WORK_DIR/phpunit.out")"
grep -q "OK ($TEST_COUNT tests" <<<"$OUT" || { echo "unexpected summary" >&2; exit 1; }
echo "$OUT" | grep -Eq "^(There were|There was) [0-9]+ failures" && { echo "failures present" >&2; exit 1; } || true
echo "$OUT" | grep -Eq "^(There were|There was) [0-9]+ errors" && { echo "errors present" >&2; exit 1; } || true
echo "$OUT" | grep -q "Skipped:" && { echo "skipped present" >&2; exit 1; } || true
echo "$OUT" | grep -q "Risky:" && { echo "risky present" >&2; exit 1; } || true
DIAG_PATTERN="(Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:)"
echo "$OUT" | grep -Eq "$DIAG_PATTERN" && { echo "php diagnostic present" >&2; exit 1; } || true

echo "Suite passed in $PHP_IMAGE with seed $SEED"
