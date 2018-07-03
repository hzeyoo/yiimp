#!/bin/bash

alias php5='php -d max_execution_time=120'

cd /var/web
while true; do
        php5 run.php cronjob/runLoop2
        sleep 60
done
exec bash

