import { useRoutes } from "@/hooks/useRoutes";
import { forwardRef } from "react";
import { NavLink } from "react-router-dom";

export const hiddenRoutes: string[] = [
    // RoutesConfig.LOGIN.path,
    // RoutesConfig.REGISTER.path,
];

export const isNavFooterHidden = (pathname: string): boolean => {
    return hiddenRoutes.some(route => pathname.startsWith(route));
};

export const NavFooter = forwardRef<HTMLDivElement>((_, ref) => {
    const { headerRoutes, isActive, pathname } = useRoutes();

    if (isNavFooterHidden(pathname)) {
        return null;
    }

    return (
        <div
            ref={ref}
            className="sticky bottom-0 left-0 right-0 z-40 flex w-full items-center justify-center rounded-t-xl border-t border-white/[0.12] bg-back-primary/85 backdrop-blur-sm"
        >
            {headerRoutes.map((item) => {
                const Icon = item.icon;
                const active = isActive(item.path);

                return (
                    <NavLink
                        key={item.path}
                        to={item.path}
                        className="flex flex-1 flex-col items-center gap-1 py-4 "
                    >
                        {Icon && (
                            <Icon className={`h-6 w-6 ${active ? "text-brand-500" : "text-text-secondary"}`} />
                        )}
                        <span className={`text-xs ${active ? "text-text-primary" : "text-text-secondary"}`}>
                            {item.label}
                        </span>
                    </NavLink>
                );
            })}
        </div>
    );
});
