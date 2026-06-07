#!/bin/bash
set -e

if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "[entrypoint] vendor/ ausente — executando composer install..."
    composer install --optimize-autoloader --no-interaction || {
        echo "[entrypoint] ERRO: composer install falhou. Verifique os logs acima."
        exit 1
    }
    echo "[entrypoint] Dependências instaladas."
fi

exec docker-php-entrypoint "$@"
