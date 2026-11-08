#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 2 ]; then
  echo "usage: $0 <test-class> <mutation-description>" >&2
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

WORK_DIR="$(mktemp -d)"
EXPORT_DIR="$(mktemp -d)"
GNUPGHOME="$(mktemp -d)"
chmod 0700 "$WORK_DIR" "$EXPORT_DIR" "$GNUPGHOME"

cleanup() {
  rm -rf "$WORK_DIR" "$EXPORT_DIR" "$GNUPGHOME"
}
trap cleanup EXIT

verify_phar() {
  local phar="$1"
  local asc="$2"
  local actual
  actual="$(sha256sum "$phar" | awk '{print $1}')"
  [ "$actual" = "$PHPUNIT_SHA256" ] || exit 1
  export GNUPGHOME
  gpg --batch --homedir "$GNUPGHOME" --keyserver hkps://keys.openpgp.org --recv-keys "$PHPUNIT_FPR" >/dev/null 2>&1
  VERIFY_LOG="$(gpg --batch --status-fd 1 --homedir "$GNUPGHOME" --verify "$asc" "$phar" 2>/dev/null)"
  echo "$VERIFY_LOG" | grep -q "VALIDSIG $PHPUNIT_FPR"
  echo "$VERIFY_LOG" | grep -q "$PHPUNIT_FPR"
}

curl -fsSL -o "$WORK_DIR/phpunit-4.8.36.phar" "$PHPUNIT_URL"
curl -fsSL -o "$WORK_DIR/phpunit-4.8.36.phar.asc" "$PHPUNIT_ASC_URL"
verify_phar "$WORK_DIR/phpunit-4.8.36.phar" "$WORK_DIR/phpunit-4.8.36.phar.asc"

git -C "$REPO_ROOT" archive HEAD | tar -x -C "$EXPORT_DIR"

case "$MUTATION_NAME" in
  supplier-row-link)
    perl -pi -e 's/getSupplierLink\(\$suppliers\[\$key\]\[\x27id_supplier\x27\]\)/getSupplierLink(\$suppliers[\$key])/' \
      "$EXPORT_DIR/ps_supplierlist.php"
    ;;
  remove-display-guard)
    perl -0pi -e 's/if \(!Configuration::get\(\x27PS_DISPLAY_SUPPLIERS\x27\)\) \{\s*return;\s*\}//s' \
      "$EXPORT_DIR/ps_supplierlist.php"
    ;;
  remove-right-hook)
    perl -pi -e 's/ && \$this->registerHook\(\x27displayRightColumn\x27\)//' \
      "$EXPORT_DIR/ps_supplierlist.php"
    ;;
  *)
    echo "unknown mutation: $MUTATION_NAME" >&2
    exit 2
    ;;
esac

$DOCKER_BIN pull "$PHP_IMAGE" >/dev/null

$DOCKER_BIN run --rm \
  --network none \
  -v "$EXPORT_DIR:/src:ro" \
  -v "$WORK_DIR:/work" \
  -e SEED="$SEED" \
  -w /work \
  "$PHP_IMAGE" \
  bash -lc "set -euo pipefail
php /src/tests/generate-suite.php $SEED /work/phpunit.xml
set +e
php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 \
  /work/phpunit-4.8.36.phar \
  --configuration /work/phpunit.xml \
  --filter '$TEST_FILTER' \
  | tee /work/proof.out
code=\${PIPESTATUS[0]}
set -e
OUT=\"\$(cat /work/proof.out)\"
echo \"\$OUT\" | grep -qi 'There was 1 error' && exit 3
echo \"\$OUT\" | grep -qi 'There were' && echo \"\$OUT\" | grep -qi 'error' && exit 3
echo \"\$OUT\" | grep -Eq 'Failures: [1-9]' || exit 4
[ \"\$code\" -ne 0 ] || exit 5
"

echo "Proof passed for filter $TEST_FILTER mutation $MUTATION_NAME"
