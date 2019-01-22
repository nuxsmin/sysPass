#!/bin/bash

DB_HOST=$(docker inspect syspass-db-test --format {{.NetworkSettings.Networks.bridge.IPAddress}})

if [ -z "${DB_HOST}" ]; then
    echo "Unknown host"
    exit 1
fi

DB_TABLE=$1
DUMP_PATH=$2

if [ -z ${DB_TABLE} ]; then
    echo "Table not set"
    exit 1
fi

if [ -z ${DUMP_PATH} ]; then
    echo "Dump path not set"
    exit 1
fi

mysqldump --hex-blob -t -u root --password=syspass-test --host=${DB_HOST} --xml syspass-test `echo "${DB_TABLE^}"` >> ${DUMP_PATH}/syspass_${DB_TABLE}.xml
