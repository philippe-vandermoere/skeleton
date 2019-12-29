#!/usr/bin/env bash

set -e

# PROMPT COLOURS
readonly RESET='\033[0;0m'
readonly BLACK='\033[0;30m'
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[0;33m'
readonly BLUE='\033[0;34m'
readonly PURPLE='\033[0;35m'
readonly CYAN='\033[0;36m'
readonly WHITE='\033[0;37m'

function ask_value() {
    local message=$1
    local default_value=$2
    local count=${3:-0}
    local value
    local default_value_message=''

    if [[ ${count} -ge 3 ]]; then
        exit 1
    fi

    if [[ -n ${default_value} ]]; then
        default_value_message=" (default: ${YELLOW}${default_value}${CYAN})"
    fi

    echo -e "${CYAN}${message}${default_value_message}: ${RESET}" > /dev/tty
    read -r value < /dev/tty

    if [[ -z "${value}" ]]; then
        if [[ -z ${default_value} ]]; then
            value=$(ask_value "${message}" '' $(( count +1 )))
        else
            value="${default_value}"
        fi
    fi

    echo "${value}"
}

function add_host() {
    local host=$1

    if [[ $(grep -c "${host}" /etc/hosts ) -eq 0 ]]; then
        sudo /bin/bash -c "echo \"127.0.0.1 ${host}\" >> /etc/hosts"
    fi
}

function get_compute_env_value() {
    local key=$1
    local default_value=$2
    local value

    case ${key} in
        COMPOSE_PROJECT_NAME)
            value="${default_value}"
        ;;
        HTTP_HOST)
            value=${default_value}
            add_host "${value}"
        ;;
        DOCKER_UID)
            value=$(id -u)
        ;;
    esac

    echo "${value}"
}

function configure_env_value() {
    local env_file=$1
    local key=$2
    local default_value=$3
    local value

    if [[ ! -f "${env_file}" ]]; then
        touch "${env_file}"
    fi

    value=$(get_compute_env_value "${key}" "${default_value}")

    if [[ -z "${value}" ]]; then
        if [[ $(grep -Ec "^${key}=" "${env_file}") -eq 0 ]]; then
            value=$(ask_value "Define the value of ${key}" "${default_value}")

            if [[ -z "${value}" ]]; then
                echo -e "${RED}No value provide for key ${key}.${RESET}" > /dev/tty
                exit 1
            fi

        else
            value=$(awk -F "${key} *= *" '{print $2}' "${env_file}")
        fi
    fi

    sed -e "/^${key}=/d" -i "${env_file}"
    echo "${key}=${value}" >> "${env_file}"
}

cd "$(dirname "$0")"

while read -r line; do
    if [[ -z "${line}" ]]; then
        continue
    fi

    configure_env_value .env "${line%%=*}" "${line#*=}"
done < .env.dist
