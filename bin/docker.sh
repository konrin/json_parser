#!/bin/bash
docker run --rm -it --network php --volume $(pwd):/app -e PHP_IDE_CONFIG="serverName=application" prooph/php:7.1-cli php -d xdebug.remote_host=192.168.1.34 $@