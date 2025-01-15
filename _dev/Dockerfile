# Build package
FROM node:22-slim as package
WORKDIR /app

ADD ./package.json ./pnpm-lock.yaml ./

RUN npm install -g pnpm \
  && pnpm install --frozen-lockfile

# Build vuejs
FROM package as builder
ADD . .

WORKDIR /app
RUN pnpm run build-storybook

### Build clean docker image with only needed files
FROM nginx:stable-alpine as production

COPY --from=builder /app/storybook-static /usr/share/nginx/html
ADD ./.docker/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
