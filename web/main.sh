#!/bin/bash

alias php5='php -d max_execution_time=120'

cd /var/web
while true; do
        php5 run.php cronjob/run
        sleep 90
done
exec bash

