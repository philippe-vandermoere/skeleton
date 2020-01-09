#!/usr/bin/env bash

set -e

readonly GREEN='\e[0;32m'
readonly RESET='\e[0m'

readonly PROJECT_FOLDER=$(realpath "$(dirname "$0")")
readonly PROJECT_NAME=$(basename "${PROJECT_FOLDER}")
readonly PROJECT_DEV_URL="${PROJECT_NAME,,}.philou.dev"

cp -rpf "${PROJECT_FOLDER}/skeleton/". "${PROJECT_FOLDER}/"

rm -f "${PROJECT_FOLDER}/config/services.yaml"
rm -f "${PROJECT_FOLDER}/config/routes.yaml"

echo -e "${GREEN}Activate Symfony Messenger AMQP Transport${RESET}"
sed s/#\ MESSENGER_TRANSPORT_DSN=amqp/MESSENGER_TRANSPORT_DSN=amqp/g -i "${PROJECT_FOLDER}/.env"

echo -e "${GREEN}Update config files${RESET}"
for file in README.md docs/DOCKER.md docker/dev/.env.dist; do
    if [[ -w "${PROJECT_FOLDER}/${file}" ]]; then
        sed s/skeleton_name/${PROJECT_NAME}/g -i "${PROJECT_FOLDER}/${file}"
        sed s/skeleton_url/${PROJECT_DEV_URL}/g -i "${PROJECT_FOLDER}/${file}"
    fi
done

echo -e "${GREEN}Remove post-create-project-cmd in composer.json${RESET}"
readonly TMP_COMPOSER_FILE=$(mktemp)
mv "${PROJECT_FOLDER}/composer.json" "${TMP_COMPOSER_FILE}"
cat "${TMP_COMPOSER_FILE}" | jq 'del(.scripts."post-create-project-cmd")' --indent 4 > "${PROJECT_FOLDER}/composer.json"
rm -f "${TMP_COMPOSER_FILE}"

echo -e "${GREEN}Remove Skeleton files${RESET}"
rm -rf "${PROJECT_FOLDER}/skeleton"
rm -f "$0"

echo -e "${GREEN}Update .gitignore${RESET}"
echo '/.idea' >> "${PROJECT_FOLDER}/.gitignore"
echo '/docker/.env' >> "${PROJECT_FOLDER}/.gitignore"

echo -e "${GREEN}Initialize GIT repository for ${PROJECT_NAME^}${RESET}"
cd ${PROJECT_FOLDER}
git init -q
git add .
git commit -m "bootstrap project ${PROJECT_NAME}" -q
