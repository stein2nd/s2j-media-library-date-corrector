#!/usr/bin/env bash
#
# Stage-1 smoke test for Local (or any WP-CLI site). Run from Local Site Shell
# when possible so DB credentials match. Optional: export WP_PATH to WordPress root.
#
# Usage:
#   export WP_PATH="/Users/you/Local Sites/yoursite/app/public"
#   ./tests/smoke-local.sh
#
set -euo pipefail

PLUGIN_SLUG="s2j-media-library-date-corrector"
PLUGIN_ENTRY="${PLUGIN_SLUG}/${PLUGIN_SLUG}.php"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
ADMIN_JS="${PLUGIN_ROOT}/dist/js/s2j-media-library-date-corrector-admin.js"

WP_ARGS=()
if [[ -n "${WP_PATH:-}" ]]; then
	WP_ARGS+=(--path="${WP_PATH}")
fi

if ! command -v wp >/dev/null 2>&1; then
	echo "FAIL: wp (WP-CLI) not found. Use Local → Site Shell." >&2
	exit 1
fi

if ! wp "${WP_ARGS[@]}" core is-installed >/dev/null 2>&1; then
	echo "FAIL: WordPress not reachable. Set WP_PATH to the site public directory (Local Site Shell sets cwd)." >&2
	exit 1
fi

if ! wp "${WP_ARGS[@]}" plugin is-installed "${PLUGIN_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: Plugin ${PLUGIN_SLUG} is not installed under wp-content/plugins." >&2
	exit 1
fi

wp "${WP_ARGS[@]}" plugin activate "${PLUGIN_SLUG}" >/dev/null 2>&1 || true

if ! wp "${WP_ARGS[@]}" plugin is-active "${PLUGIN_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: Plugin could not be activated." >&2
	exit 1
fi

if [[ ! -f "${ADMIN_JS}" ]]; then
	echo "WARN: ${ADMIN_JS} missing. Run npm run build for upload.php UI assets." >&2
fi

wp "${WP_ARGS[@]}" eval "
if ( ! defined( 'S2J_MLDC_REST_NAMESPACE' ) ) {
	exit( 1 );
}
do_action( 'rest_api_init' );
\$route = '/' . S2J_MLDC_REST_NAMESPACE . '/attachments/correct';
\$routes = rest_get_server()->get_routes();
if ( ! isset( \$routes[ \$route ] ) ) {
	exit( 2 );
}
if ( ! class_exists( 'S2J_MLDC_Media_Date_Service' ) ) {
	exit( 3 );
}
echo 'ok';
"

echo "PASS: ${PLUGIN_SLUG} active; REST route and service class present."
