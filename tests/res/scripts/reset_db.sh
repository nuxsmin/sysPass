#!/bin/bash

DB_HOST=$(docker inspect syspass-db-test --format {{.NetworkSettings.Networks.bridge.IPAddress}})

if [[ -z "${DB_HOST}" ]]; then
    echo "Unknown host"
    exit 1
fi

SQL_FILE=$1
DB_NAME=$2
PROJECT_DIR=$3

if [[ ! -e "${SQL_FILE}" ]]; then
    echo "SQL file does not exist: ${SQL_FILE}"
    exit 1
fi

echo "Database host: ${DB_HOST}"
echo "Database name: ${DB_NAME}"

case ${DB_NAME} in
    "syspass")
        mysql -h ${DB_HOST} -u root -psyspass ${DB_NAME} < ${PROJECT_DIR}/schemas/dbstructure.sql
        mysql -h ${DB_HOST} -u root -psyspass ${DB_NAME} < ${SQL_FILE}
        ;;
    "syspass-test")
        mysql -h ${DB_HOST} -u root -psyspass -e 'DROP DATABASE IF EXISTS `'"${DB_NAME}"'`;'
        mysql -h ${DB_HOST} -u root -psyspass -e 'CREATE DATABASE `'"${DB_NAME}"'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;'
        mysql -h ${DB_HOST} -u root -psyspass ${DB_NAME} < ${SQL_FILE}
        ;;
    *)
        echo "Database name not set"
        exit 1
        ;;
esac

if [[ $? -eq 0 ]]; then
    echo "Database data imported"
fi