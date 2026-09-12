import { useStore } from "@/store/root.store";
import { RoutesConfig } from "@/types/pagesConfig";
import type { Route, RouteKey } from "@/types/pagesConfig";
import { UserRoles } from "@/types";
import { useMemo } from "react";
import { useLocation } from "react-router-dom";

export const useRoutes = () => {
    const { userStore: { user } } = useStore();
    const { pathname } = useLocation();

    const isAdmin = user?.role === UserRoles.Admin;

    const getPathByKey = (key: RouteKey): string => RoutesConfig[key]?.path ?? '/';

    const headerRoutes = useMemo(
        () => Object.values(RoutesConfig).filter((route): route is Route =>
            !!route.showInHeader && !route.disabled && (!route.admin || isAdmin)
        ),
        [isAdmin]
    );

    const isActive = (path: string) => pathname === path;

    return { getPathByKey, headerRoutes, isActive, pathname };
};
