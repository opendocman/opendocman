#!/bin/sh
# wait-for-mysql.sh

set -e

host="$1"
shift
cmd="$@"

while ! mysqladmin ping -h ${host} --silent; do     sleep 1; done

exec $cmd

