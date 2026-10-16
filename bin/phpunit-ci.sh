#!/usr/bin/env bash
# Run full PHPUnit suite (used inside wp-env tests-wordpress container).
set -euo pipefail
cd "$(dirname "$0")/.."
vendor/bin/phpunit -c phpunit.unit.xml.dist
vendor/bin/phpunit -c phpunit.xml.dist
