FROM node:22-alpine
WORKDIR /app

# Evita fallos de build si aún no existe package.json; instalamos en runtime
EXPOSE 5173
RUN corepack enable

CMD ["sh", "-lc", "[ -f package.json ] && pnpm install || true; pnpm dev --host 0.0.0.0 --port 5173"]
