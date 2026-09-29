import axios from "axios";
import {
    getRefreshTokenFromLocalStorage,
    getTokenFromLocalStorage,
    removeTokenFromLocalStorage,
    setRefreshTokenToLocalStorage,
    setTokenToLocalStorage,
} from "@/utils/localstorage";

const prefix = import.meta.env.VITE_API_URL ?? ''

/** Login/register/refresh must not trigger the refresh-and-retry dance — the form shows the error itself. */
const isAuthEndpoint = (url?: string) => !!url && /\/api\/auth\/(login|register|refresh)$/.test(url);

type RefreshResponse = { accessToken: string; refreshToken: string };

// Общее для всех axios-инстансов состояние: конкурентные 401 не должны запускать несколько
// параллельных refresh — только один запрос, остальные ждут его результата
let refreshPromise: Promise<string> | null = null;
const retriedRequests = new WeakSet<object>();

const performRefresh = async (): Promise<string> => {
    const refreshToken = getRefreshTokenFromLocalStorage();

    if (!refreshToken) {
        throw new Error("No refresh token available");
    }

    const { data } = await axios.post<RefreshResponse>(`${prefix}/api/auth/refresh`, { refreshToken });

    setTokenToLocalStorage(data.accessToken);
    setRefreshTokenToLocalStorage(data.refreshToken);

    return data.accessToken;
};

const refreshAccessToken = (): Promise<string> => {
    refreshPromise ??= performRefresh().finally(() => {
        refreshPromise = null;
    });

    return refreshPromise;
};

const forceLogout = () => {
    removeTokenFromLocalStorage();

    if (window.location.pathname !== '/login') {
        window.location.assign('/login');
    }
};

export const createAxiosInstance = (basePath: string) => {
    const instance = axios.create({
        baseURL: `${prefix}/api/${basePath}`,
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
        async error => {
            const status = error?.response?.status;
            const originalRequest = error?.config;
            const url = originalRequest?.baseURL ? originalRequest.baseURL + (originalRequest.url ?? '') : originalRequest?.url;

            if (isAuthEndpoint(url)) {
                return Promise.reject(error);
            }

            // Протухший access-токен: один раз пробуем обновить пару и повторить исходный запрос
            if (status === 401 && originalRequest && !retriedRequests.has(originalRequest)) {
                retriedRequests.add(originalRequest);

                try {
                    const newAccessToken = await refreshAccessToken();
                    originalRequest.headers = { ...originalRequest.headers, Authorization: `Bearer ${newAccessToken}` };
                    return instance(originalRequest);
                } catch {
                    forceLogout();
                    return Promise.reject(error);
                }
            }

            // Бан (403) или повторный 401 после неудачного refresh — сессии больше нет
            if (status === 401 || status === 403) {
                forceLogout();
            }

            return Promise.reject(error);
        }
    );

    return instance;
};
