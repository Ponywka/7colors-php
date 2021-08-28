#!/bin/sh

php artisan migrate
if [ $? -ne 0 ]; then
    # Wait database
    sleep 5
    ./migrate.sh
else
    # Fix permissions after artisan
    chown -R www-data:www-data .
    apache2-foreground
fi
