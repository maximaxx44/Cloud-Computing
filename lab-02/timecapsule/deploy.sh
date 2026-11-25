#!/usr/bin/env bash
set -euo pipefail

app_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$app_dir"

echo '==> Обновление из GitHub'
git pull --ff-only
echo '==> Установка зависимостей'
composer install --no-dev --optimize-autoloader --no-interaction
echo '==> Перечитывание конфигурации PHP-FPM'
sudo systemctl reload php-fpm
printf 'Версия на сервере: %s\n' "$(git log -1 --oneline)"
