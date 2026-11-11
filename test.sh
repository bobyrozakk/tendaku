#!/bin/sh

docker exec -it \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e DB_URL= \
  tendaku_app \
  php artisan test "$@"
