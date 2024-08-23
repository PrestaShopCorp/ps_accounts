FROM node:20.10-alpine

RUN apk add --no-cache curl

ENV CI=true
ENV RUN_IN_DOCKER=1
RUN npm install -g pnpm

WORKDIR /home/node
ADD . /home/node

# Avoid "failed to compile" error from ARM deps shipped in pnpm-lock.yaml
RUN rm pnpm-lock.yaml \
  && pnpm install \
  && pnpm run build

ENTRYPOINT [ "pnpm" ]
CMD [ "start" ]
