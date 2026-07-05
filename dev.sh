#!/usr/bin/env bash
# Convenience wrapper to run artisan/composer/pint/phpstan commands
# against the project using PHP 8.3 in Docker, without needing PHP installed locally.
set -euo pipefail
cd "$(dirname "$0")"

IMAGE="php:8.3-cli"
UID_GID="$(id -u):$(id -g)"

docker run --rm -u "$UID_GID" -v "$(pwd)":/app -w /app "$IMAGE" "$@"
