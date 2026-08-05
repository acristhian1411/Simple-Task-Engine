FROM node:22-alpine AS base
WORKDIR /app
RUN apk add --no-cache python3 make g++
RUN corepack enable

COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile || (pnpm approve-builds --all && pnpm install --frozen-lockfile)
COPY . .

FROM base AS dev
EXPOSE 5173
CMD ["sh", "-lc", "pnpm install --frozen-lockfile && pnpm dev --host 0.0.0.0 --port 5173"]

FROM base AS build
RUN pnpm build

FROM node:22-alpine AS prod
WORKDIR /app
RUN corepack enable
COPY --from=build /app /app
EXPOSE 4173
CMD ["pnpm", "preview", "--host", "0.0.0.0", "--port", "4173"]
