# AMS Frontend (Vue 3)

SPA for the AMS administration console.

```bash
npm install
cp .env.example .env
npm run dev
```

Dev server proxies `/api` and `/sanctum` to `VITE_PROXY_TARGET` (default `http://127.0.0.1:8000`).
