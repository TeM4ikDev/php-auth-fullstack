import axios from "axios";
import { getTokenFromLocalStorage, removeTokenFromLocalStorage } from "@/utils/localstorage";

const prefix = import.meta.env.VITE_API_URL ?? ''

/** Login and register must not redirect on 401 — the form shows the error itself. */
const isAuthEndpoint = (url?: string) => !!url && /\/api\/auth\/(login|register)$/.test(url);

export const createAxiosInstance = (basePath: string) => {
    const instance = axios.create({
        baseURL: `${prefix}/api/${basePath}`,
        headers: {
            Authorization: 'Bearer ' + (getTokenFromLocalStorage() || '')
        },
    });

    instance.interceptors.request.use(
        config => {
            const token = getTokenFromLocalStorage();
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
            return config;
        },
        error => {
            return Promise.reject(error);
        }
    );

    instance.interceptors.response.use(
        response => response,
        error => {
            const status = error?.response?.status;
            const url = error?.config?.baseURL ? error.config.baseURL + (error.config.url ?? '') : error?.config?.url;

            // A stale or revoked token (including a ban) must not leave the app in a half-authenticated state
            if ((status === 401 || status === 403) && !isAuthEndpoint(url)) {
                removeTokenFromLocalStorage();

                if (window.location.pathname !== '/login') {
                    window.location.assign('/login');
                }
            }

            return Promise.reject(error);
        }
    );

    return instance;
};
