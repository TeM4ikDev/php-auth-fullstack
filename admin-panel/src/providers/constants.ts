// Пусто в dev — Vite проксирует /api на бэкенд (см. vite.config.ts).
// В проде admin-panel раздаётся своим nginx, который проксирует /api туда же.
export const API_URL = import.meta.env.VITE_API_URL ?? "";

// Тот же ключ, что и у клиентского приложения: оба ходят в один бэкенд
export const TOKEN_KEY = "token";
