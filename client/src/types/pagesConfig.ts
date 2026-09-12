import { createAxiosInstance } from "@/api/axios.api";
import { HomeIcon, UserIcon } from "lucide-react";
import type { ElementType } from "react";

export type Route = {
    path: string;
    label: string;
    icon?: ElementType;
    showInHeader?: boolean;
    disabled?: boolean;
    admin?: boolean;
};

export interface Routes {
    [key: string]: Route;
}

const routes = {
    HOME: { path: '/', label: 'Главная', showInHeader: true, icon: HomeIcon },
    LOGIN: { path: '/login', label: 'Вход' },
    REGISTER: { path: '/register', label: 'Регистрация' },
    PROFILE: { path: '/profile', label: 'Профиль', showInHeader: true, icon: UserIcon },
    NOT_FOUND: { path: '/404', label: 'Страница не найдена', disabled: true },
} satisfies Routes;

export type RouteKey = keyof typeof routes;

// Record<RouteKey, Route> вместо typeof routes: иначе каждый роут сужается
// до своей литеральной формы и Object.values() теряет необязательные поля.
export const RoutesConfig: Record<RouteKey, Route> = routes;

class ApiConfig {
    auth = {
        baseInstance: createAxiosInstance('auth/'),
        login: "login",
        register: "register",
        profile: "profile",
    };

    users = {
        baseInstance: createAxiosInstance('users/'),
        me: 'me',
    };
}

export const apiConfig = new ApiConfig();
