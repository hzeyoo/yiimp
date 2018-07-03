#!/bin/bash

alias php5='php -d max_execution_time=60'

cd /var/web
while true; do
        php5 run.php cronjob/runBlocks
        sleep 20
done
exec bash


