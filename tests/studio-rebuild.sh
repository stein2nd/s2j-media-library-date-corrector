#!/usr/bin/env bash
#
# Stage-2: refresh WordPress Studio smoke site — fixtures, build, plugin deploy, seed reset.
#
# Usage:
#   export WP_PATH="$HOME/Studio/mldc-smoke"
#   npm run test:smoke:studio:rebuild
#
set -euo pipefail

PLUGIN_SLUG="s2j-media-library-date-corrector"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
WP_PATH="${WP_PATH:-${HOME}/Studio/mldc-smoke}"
STUDIO_PLUGIN_LINK="${STUDIO_PLUGIN_LINK:-1}"
FIXTURES_BRANCH="${STUDIO_FIXTURES_BRANCH:-main}"
FIXTURES_BASE_URL="${STUDIO_FIXTURES_BASE_URL:-https://raw.githubusercontent.com/stein2nd/s2j-media-library-date-corrector/${FIXTURES_BRANCH}/studio/assets}"
FIXTURE_NAME="mldc-smoke-sample.jpg"
LOCAL_FIXTURE="${PLUGIN_ROOT}/studio/assets/${FIXTURE_NAME}"

WP_ARGS=(--path="${WP_PATH}")

if [[ ! -d "${WP_PATH}" ]]; then
	echo "FAIL: WP_PATH is not a directory: ${WP_PATH}" >&2
	echo "Create the site from studio/smoke-test-blueprint.json first, or set WP_PATH." >&2
	exit 1
fi

if ! command -v wp >/dev/null 2>&1; then
	echo "FAIL: wp (WP-CLI) not found." >&2
	exit 1
fi

echo "==> Fetch smoke fixtures (upstream or local fallback)"
mkdir -p "${PLUGIN_ROOT}/studio/assets"
if ! curl -fsSL "${FIXTURES_BASE_URL}/${FIXTURE_NAME}" -o "${LOCAL_FIXTURE}.tmp"; then
	if [[ ! -f "${LOCAL_FIXTURE}" ]]; then
		echo "FAIL: could not download ${FIXTURE_NAME} and no local copy exists." >&2
		exit 1
	fi
	echo "WARN: upstream fetch failed; using existing ${LOCAL_FIXTURE}" >&2
else
	mv "${LOCAL_FIXTURE}.tmp" "${LOCAL_FIXTURE}"
fi

echo "==> npm run build"
npm run build --prefix "${PLUGIN_ROOT}"

PLUGINS_DIR="${WP_PATH}/wp-content/plugins"
TARGET="${PLUGINS_DIR}/${PLUGIN_SLUG}"
mkdir -p "${PLUGINS_DIR}"

echo "==> Deploy plugin to Studio (${TARGET})"
if [[ "${STUDIO_PLUGIN_LINK}" == "1" ]]; then
	ln -sfn "${PLUGIN_ROOT}" "${TARGET}"
else
	rsync -a --delete \
		--exclude node_modules \
		--exclude .git \
		--exclude release \
		--exclude .dist-tmp \
		"${PLUGIN_ROOT}/" "${TARGET}/"
fi

echo "==> Sync mu-plugin seed"
MU_DIR="${WP_PATH}/wp-content/mu-plugins"
mkdir -p "${MU_DIR}"
cp "${PLUGIN_ROOT}/studio/mu-plugin-smoke-seed.php" "${MU_DIR}/s2j-mldc-smoke-seed.php"

echo "==> Copy fixture into uploads/2024/01/"
UPLOAD_DIR="${WP_PATH}/wp-content/uploads/2024/01"
mkdir -p "${UPLOAD_DIR}"
cp "${LOCAL_FIXTURE}" "${UPLOAD_DIR}/${FIXTURE_NAME}"

echo "==> Reset smoke seed option and sample attachment"
wp "${WP_ARGS[@]}" option delete s2j_mldc_studio_smoke_seeded >/dev/null 2>&1 || true
SEED_IDS="$(wp "${WP_ARGS[@]}" post list --post_type=attachment --name=mldc-smoke-mismatch-sample --format=ids 2>/dev/null || true)"
if [[ -n "${SEED_IDS}" ]]; then
	wp "${WP_ARGS[@]}" post delete ${SEED_IDS} --force >/dev/null
fi

wp "${WP_ARGS[@]}" plugin activate "${PLUGIN_SLUG}" >/dev/null

echo "OK: Studio smoke site refreshed. Open wp-admin/upload.php once to re-seed mismatch attachment."
