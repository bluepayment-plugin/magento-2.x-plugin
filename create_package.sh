#!/usr/bin/env bash
set -euo pipefail

cd -- "$(dirname -- "${BASH_SOURCE[0]}")"

if [[ $# -gt 1 ]]; then
    printf 'Usage: %s [version]\n' "$0" >&2
    exit 1
fi

METADATA_VERSION=''
if [[ -f Model/Metadata.php ]]; then
    METADATA_VERSION=$(sed -nE "s/^[[:space:]]*((public|protected|private)[[:space:]]+)?const[[:space:]]+VERSION[[:space:]]*=[[:space:]]*'([^']+)';[[:space:]]*$/\3/p" Model/Metadata.php)
fi

PACKAGE_VERSION="${1:-$METADATA_VERSION}"
if [[ ! "$PACKAGE_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-p[0-9]+)?$ ]]; then
    printf 'Provide a package version, for example: %s 2.22.3\n' "$0" >&2
    exit 1
fi

if [[ -n "$METADATA_VERSION" && "${PACKAGE_VERSION%%-p*}" != "$METADATA_VERSION" ]]; then
    printf 'Package version %s does not match module metadata %s.\n' "$PACKAGE_VERSION" "$METADATA_VERSION" >&2
    exit 1
fi

PACKAGE_NAME="bm-bluepayment-$PACKAGE_VERSION.zip"
TEMP_DIR=$(mktemp -d "${TMPDIR:-/tmp}/bluepayment-package.XXXXXX")
trap 'rm -rf -- "$TEMP_DIR"' EXIT

zip -q -r "$TEMP_DIR/$PACKAGE_NAME" . \
    -x '*.git*' '*.idea*' '*.vscode*' '*.DS_Store*' '*__MACOSX*' \
    'node_modules/*' '*/node_modules/*' 'vendor/*' '*/vendor/*' \
    'var/*' '*/var/*' 'generated/*' '*/generated/*' \
    '*.ddev*' '*.aws*' '*.codex*' '*.agents*' '*.zenflow*' '*.aider*' \
    '.env*' '*/.env*' 'auth.json' '*/auth.json' 'env.php' '*/env.php' \
    'config.php' '*/config.php' 'local.xml' '*/local.xml' \
    '*create_package.sh' 'AGENTS.md' '*/AGENTS.md' '*bluepayment-package.*' \
    '*.zip' '*.tar' '*.gz' '*.tgz' '*.bz2' '*.xz' '*.7z' '*.rar' \
    '*.doc' '*.docx' '*.patch' '*.bak' '*.log' '*.tmp' '*.swp'

mv -f -- "$TEMP_DIR/$PACKAGE_NAME" "$PACKAGE_NAME"
printf 'Package created: %s/%s\n' "$PWD" "$PACKAGE_NAME"
