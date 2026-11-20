#!/usr/bin/env bash
# Start wp-env tests stack for CI (retry on transient Docker / BuildKit errors).
set -euo pipefail

CONFIG=".wp-env.tests.json"
MAX_ATTEMPTS=3

for attempt in $(seq 1 "${MAX_ATTEMPTS}"); do
	if npx wp-env start --config="${CONFIG}"; then
		exit 0
	fi
	echo "wp-env start attempt ${attempt}/${MAX_ATTEMPTS} failed." >&2
	if [ "${attempt}" -lt "${MAX_ATTEMPTS}" ]; then
		npx wp-env destroy --config="${CONFIG}" --force 2>/dev/null || true
		sleep 15
	fi
done

exit 1