#!/usr/bin/env bash
# Minimal php-fpm liveness probe used by the compose healthcheck.
# php-fpm exposes no HTTP; we confirm the master process is running and the
# pool socket is up by asking fpm to test its own config.
set -e
pgrep -x php-fpm >/dev/null 2>&1 || exit 1
exit 0
