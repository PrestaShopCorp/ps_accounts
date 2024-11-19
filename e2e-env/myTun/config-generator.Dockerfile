FROM alpine:latest

# Créer le répertoire de configuration
RUN mkdir -p /config

ADD scripts/generateConfigFiles.sh /
WORKDIR /

ENTRYPOINT [ "sh" ]
CMD ["/generateConfigFiles.sh"]
