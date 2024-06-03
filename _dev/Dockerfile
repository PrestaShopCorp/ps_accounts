# Build package
FROM node:14-slim as package
WORKDIR /app

ADD ./package.json ./yarn.lock ./

RUN yarn --frozen-lockfile

# Build vuejs
FROM package as builder
ADD . .

WORKDIR /app
RUN yarn run build-storybook


### Build clean docker image with only needed files
FROM nginx:stable-alpine as production

COPY --from=builder /app/storybook-static /usr/share/nginx/html
ADD ./.docker/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
