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
    HOME: { path: '/', label: 'Home', showInHeader: true, icon: HomeIcon },
    LOGIN: { path: '/login', label: 'Sign in' },
    REGISTER: { path: '/register', label: 'Sign up' },
    PROFILE: { path: '/profile', label: 'Profile', showInHeader: true, icon: UserIcon },
    NOT_FOUND: { path: '/404', label: 'Page not found', disabled: true },
} satisfies Routes;

export type RouteKey = keyof typeof routes;

// Record<RouteKey, Route> instead of typeof routes: otherwise every route narrows
// to its own literal shape and Object.values() loses the optional fields.
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
