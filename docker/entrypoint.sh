#!/bin/bash
set -e

php artisan migrate --force

if [ -n "$INIT_DEFAULT_EMAIL" ]; then
    NAME="${INIT_DEFAULT_NAME:-Admin}"
    EMAIL="$INIT_DEFAULT_EMAIL"
    PASSWORD="${INIT_DEFAULT_PASSWORD:-$(openssl rand -base64 12)}"

    if php artisan user:create "$NAME" "$EMAIL" --password="$PASSWORD" --no-interaction 2>/dev/null; then
        echo "========================================="
        echo " Default user created"
        echo " Name:     $NAME"
        echo " Email:    $EMAIL"
        echo " Password: $PASSWORD"
        echo "========================================="
    fi
fi

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
