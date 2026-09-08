#!/usr/bin/env bash
set -euo pipefail

cd /home/site/wwwroot
exec php artisan schedule:run --no-interaction
