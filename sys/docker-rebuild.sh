#!/bin/bash
APP_DIR="`dirname $PWD`" docker-compose -p goat-domain up -d --build --remove-orphans --force-recreate
