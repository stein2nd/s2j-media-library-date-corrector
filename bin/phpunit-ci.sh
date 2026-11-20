#!/usr/bin/env bash
# Run full PHPUnit suite (used inside wp-env cli container; see .wp-env.tests.json).
set -euo pipefail
cd "$(dirname "$0")/.."
vendor/bin/phpunit -c phpunit.unit.xml.dist
vendor/bin/phpunit -c phpunit.xml.dist
