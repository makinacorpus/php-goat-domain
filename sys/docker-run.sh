#!/bin/bash

echo "Running tests on PHP 7.4"
APP_DIR="`dirname $PWD`" docker-compose -p goat-domain run php74 vendor/bin/phpunit "$@"

echo "Running tests on PHP 8.0"
APP_DIR="`dirname $PWD`" docker-compose -p goat-domain run php80 vendor/bin/phpunit "$@"

echo "Running tests on PHP 8.1"
APP_DIR="`dirname $PWD`" docker-compose -p goat-domain run php81 vendor/bin/phpunit "$@"
