# AMS Frontend (Vue 3)

SPA for the AMS administration console.

## Development

```bash
npm install
cp .env.example .env
npm run dev
```

Leave `VITE_API_BASE_URL` empty so the Vite dev server proxies `/api` and `/sanctum` to `VITE_PROXY_TARGET` (default `https://amsapi.eh.studio`). Direct cross-origin calls from `localhost:5173` cannot send Sanctum CSRF cookies and return `CSRF token mismatch.`

## Production

```bash
npm run build
```

Upload the contents of `dist/` to the SPA host. The production API origin is set in `.env.production` (`https://amsapi.eh.studio`). Apache SPA fallback for Vue Router history mode is copied from `public/.htaccess`.
