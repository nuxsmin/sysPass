#!/usr/bin/env bash
set -euo pipefail

: ${SQL_FILE:=}
: ${DB_NAME:=}
: ${DB_CONTAINER_NAME:=}

usage() {
  echo "Usage: $0 [ -s SQL_FILE ] [ -d DB_NAME ] [ -p PROJECT_DIR ] [ -C DB_CONTAINER_NAME ]" 1>&2
}

exit_abnormal() {
  usage
  exit 1
}

do_import() {
  case ${DB_NAME} in
  "syspass")
    mysql -h ${DB_HOST} -u root -psyspass ${DB_NAME} <${PROJECT_DIR}/schemas/dbstructure.sql
    mysql -h ${DB_HOST} -u root -psyspass ${DB_NAME} <${SQL_FILE}
    ;;
  "syspass-test")
    mysql -h ${DB_HOST} -u root -psyspass -e 'DROP DATABASE IF EXISTS `'"${DB_NAME}"'`;'
    mysql -h ${DB_HOST} -u root -psyspass -e 'CREATE DATABASE `'"${DB_NAME}"'` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;'
    mysql -h ${DB_HOST} -u root -psyspass ${DB_NAME} <${SQL_FILE}
    ;;
  *)
    echo "Database name not set"
    exit 1
    ;;
  esac

  if [[ $? -eq 0 ]]; then
    echo "Database data imported"
  fi
}

get_db_host() {
  DB_HOST=$(docker inspect ${DB_CONTAINER_NAME} --format {{.NetworkSettings.Networks.bridge.IPAddress}})

  if [[ -z "${DB_HOST}" ]]; then
    echo "Couldn't guess DB host from Docker container '${OPTARG}"
    exit_abnormal
  fi

  echo "Database host: ${DB_HOST}"
}

check_options() {
  if [ -z "${DB_NAME}" ]; then
    echo "Database name not set"
    exit_abnormal
  fi

  if [ -z "${SQL_FILE}" ]; then
    echo "SQL file not set"
    exit_abnormal
  fi

  if [ -z "${DB_CONTAINER_NAME}" ]; then
    echo "Database container name not set"
    exit_abnormal
  fi
}

while getopts ":hs:d:p:C:" OPTIONS; do
  case "${OPTIONS}" in
  h)
    usage
    ;;
  s)
    SQL_FILE=${OPTARG}

    if [[ ! -e "${SQL_FILE}" ]]; then
      echo "SQL file does not exist: ${SQL_FILE}"
      exit_abnormal
    fi

    echo "SQL file: ${SQL_FILE}"
    ;;
  d)
    DB_NAME=${OPTARG}

    echo "Database name: ${DB_NAME}"
    ;;
  p)
    PROJECT_DIR=${OPTARG}

    echo "Project dir: ${PROJECT_DIR}"
    ;;
  C)
    DB_CONTAINER_NAME=${OPTARG}

    echo "Database container name: ${DB_CONTAINER_NAME}"

    get_db_host
    ;;
  :)
    echo "$0: Must supply an argument to -$OPTARG." >&2
    exit_abnormal
    ;;
  ?)
    echo "Invalid option: -${OPTARG}."
    exit_abnormal
    ;;
  *)
    exit_abnormal
    ;;
  esac
done

check_options
do_import
