#!/usr/bin/env bash
#
# Run PHPUnit locally without wp-env/Docker.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

export WP_TESTS_DIR="${WP_TESTS_DIR:-${PLUGIN_DIR}/tests/.wordpress-tests-lib}"
export WP_CORE_DIR="${WP_CORE_DIR:-${PLUGIN_DIR}/tests/.wordpress}"

if [ -f "${PLUGIN_DIR}/.env.tests" ]; then
	# shellcheck disable=SC1091
	set -a
	source "${PLUGIN_DIR}/.env.tests"
	set +a
fi

DB_NAME="${WP_TESTS_DB_NAME:-wordpress_test}"
DB_USER="${WP_TESTS_DB_USER:-root}"
DB_PASSWORD="${WP_TESTS_DB_PASSWORD:-root}"
DB_HOST="${WP_TESTS_DB_HOST:-localhost}"
WP_VERSION="${WP_TESTS_WP_VERSION:-latest}"
SKIP_DB_CREATE="${WP_TESTS_SKIP_DB_CREATE:-false}"

# Local の開発 DB 名 (多くのサイトで "local") には接続しない。
if [ "${DB_NAME}" = "local" ]; then
	echo "Refusing WP_TESTS_DB_NAME=local (Local site database)." >&2
	echo "Use a separate test database, e.g. wordpress_test (see .env.tests.example)." >&2
	exit 1
fi

assert_mysql_for_tests() {
	local DB_HOSTNAME="$DB_HOST"
	local DB_SOCK=""
	if [[ "$DB_HOST" == *:* ]]; then
		DB_HOSTNAME="${DB_HOST%%:*}"
		DB_SOCK="${DB_HOST#*:}"
		if [ ! -S "$DB_SOCK" ]; then
			echo "MySQL socket not found: ${DB_SOCK}" >&2
			echo "Flywheel Local で該当サイトを Start してください (停止中は mysqld.sock がありません)。" >&2
			echo ".env.tests の WP_TESTS_DB_HOST が Site → Database の socket と一致するか確認してください。" >&2
			exit 1
		fi
	fi

	if ! command -v mysql >/dev/null 2>&1; then
		echo "mysql client not found in PATH; skipping DB reachability check." >&2
		return 0
	fi

	local MYSQL_ARGS=( --user="$DB_USER" --password="$DB_PASSWORD" )
	if [ -n "$DB_SOCK" ]; then
		MYSQL_ARGS+=( --socket="$DB_SOCK" )
	elif [ "$DB_HOSTNAME" != "localhost" ]; then
		MYSQL_ARGS+=( --host="$DB_HOSTNAME" --protocol=tcp )
	fi

	if ! mysql "${MYSQL_ARGS[@]}" --execute="SELECT 1;" >/dev/null 2>&1; then
		echo "Could not connect to MySQL for integration tests (database: ${DB_NAME})." >&2
		echo "Check .env.tests and that Local MySQL is running." >&2
		exit 1
	fi
}

sync_wp_tests_config() {
	local CONFIG="${WP_TESTS_DIR}/wp-tests-config.php"
	if [ ! -f "$CONFIG" ]; then
		return 0
	fi
	WP_TESTS_CONFIG_FILE="$CONFIG" \
	WP_TESTS_DB_NAME="$DB_NAME" \
	WP_TESTS_DB_USER="$DB_USER" \
	WP_TESTS_DB_PASSWORD="$DB_PASSWORD" \
	WP_TESTS_DB_HOST="$DB_HOST" \
	php "${SCRIPT_DIR}/wp-tests-config-sync.php"
}

cd "${PLUGIN_DIR}"

echo "Running unit tests (no WordPress DB)…"
vendor/bin/phpunit -c phpunit.unit.xml.dist "$@"

if [ ! -f "${WP_TESTS_DIR}/includes/functions.php" ]; then
	echo ""
	echo "WordPress test library not found. Installing (MySQL required once)…"
	bash "${SCRIPT_DIR}/install-wp-tests.sh" \
		"${DB_NAME}" "${DB_USER}" "${DB_PASSWORD}" "${DB_HOST}" "${WP_VERSION}" "${SKIP_DB_CREATE}"
fi

assert_mysql_for_tests
sync_wp_tests_config

echo ""
echo "Running integration tests (WordPress test suite)…"
vendor/bin/phpunit -c phpunit.xml.dist "$@"
