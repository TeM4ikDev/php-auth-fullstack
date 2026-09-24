import react from "@vitejs/plugin-react";
import { defineConfig, loadEnv } from "vite";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  // client's nginx already proxies /api to php:9000 — reuse it in dev instead of
  // talking to the php-fpm container directly, which isn't published on the host.
  const target = env.VITE_PROXY_TARGET || "http://localhost:8081";
  // notification-service имеет собственный nginx на отдельном порту — /api/notifications
  // должен матчиться раньше общего /api, поэтому объявлен первым (порядок ключей важен для Vite)
  const notificationTarget = env.VITE_NOTIFICATION_PROXY_TARGET || "http://localhost:8083";

  return {
    plugins: [react()],
    server: {
      port: 5174,
      proxy: {
        "/api/notifications": {
          target: notificationTarget,
          changeOrigin: true,
        },
        "/api": {
          target,
          changeOrigin: true,
        },
      },
    },
  };
});
