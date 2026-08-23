import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

const DEFAULT_PROXY_TARGET = 'https://amsapi.eh.studio';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const proxyTarget = (env.VITE_PROXY_TARGET || DEFAULT_PROXY_TARGET).replace(/\/$/, '');

  const proxyOptions = {
    target: proxyTarget,
    changeOrigin: true,
    secure: true,
    cookieDomainRewrite: '',
    configure: (proxy) => {
      proxy.on('proxyRes', (proxyRes) => {
        const cookies = proxyRes.headers['set-cookie'];
        if (!cookies) {
          return;
        }

        proxyRes.headers['set-cookie'] = cookies.map((cookie) =>
          cookie
            .replace(/;\s*Secure/gi, '')
            .replace(/;\s*SameSite=None/gi, '; SameSite=Lax')
            .replace(/;\s*Domain=[^;]+/gi, ''),
        );
      });
    },
  };

  return {
    plugins: [vue(), tailwindcss()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      port: 5173,
      host: true,
      proxy: {
        '/api': proxyOptions,
        '/sanctum': proxyOptions,
        '/storage': proxyOptions,
        '/sitemap.xml': proxyOptions,
        '/robots.txt': proxyOptions,
      },
    },
  };
});
