#!/bin/sh

crond -f -l 8 &

php-fpm
