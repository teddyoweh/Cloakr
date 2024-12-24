#!/bin/bash

sed -i "s|username|${username}|g" ${cloakrConfigPath} && sed -i "s|password|${password}|g" ${cloakrConfigPath}

if [[ $# -eq 0 ]]; then
    exec /src/cloakr serve ${domain} --port ${port} --validateAuthTokens
else
    exec /src/cloakr "$@"
fi
