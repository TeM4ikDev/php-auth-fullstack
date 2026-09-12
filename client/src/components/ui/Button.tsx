import { useRoutes } from "@/hooks/useRoutes";
import type { RouteKey } from "@/types/pagesConfig";
import { cn } from "@/utils/cn";
import { motion } from "framer-motion";
import React, { type ReactNode, useMemo } from "react";
import { NavLink } from "react-router-dom";

interface Props {
    text?: string | ReactNode;
    onClick?: () => void;
    routeKey?: RouteKey;
    color?: "red" | "blue" | "green" | "transparent" | "gold" | "none" | "gray";
    widthMin?: boolean;
    openNewPage?: boolean;
    href?: string;
    formSubmit?: boolean;
    disabled?: boolean;
    className?: string;
    icon?: ReactNode;
    loading?: boolean;
}

export const Button = React.memo(({ text, onClick, routeKey, icon, widthMin = false, href, className, openNewPage = false, disabled = false, formSubmit = false, color = "gold", loading = false }: Props) => {
    const { getPathByKey } = useRoutes();

    const buttonColor = useMemo(() => {
        if (color === "red") return "bg-gradient-to-r from-red-500/90 to-red-600/90 active:from-red-600 active:to-red-700 md:hover:from-red-600 md:hover:to-red-700 border border-red-500/50";
        if (color === "green") return "bg-gradient-to-r from-green-500/90 to-green-600/90 active:from-green-600 active:to-green-700 md:hover:from-green-600 md:hover:to-green-700 border border-green-500/50";
        if (color === "transparent") return "bg-gradient-to-r from-gray-800/30 to-gray-700/30 active:from-gray-800/50 active:to-gray-700/50 md:hover:from-gray-800/50 md:hover:to-gray-700/50 border border-gray-600/50 text-gray-300";
        if (color === "gold") return "bg-gradient-to-r from-gold-500 to-gold-500/90 active:from-gold-600 active:to-gold-700 md:hover:from-gold-600 md:hover:to-gold-700 border border-gold-500/50 text-pablo-darker";
        if (color === "gray") return " bg-blackSabath-800 active:from-gray-600 active:to-gray-700 md:hover:from-gray-600 md:hover:to-gray-700 border border-gray-500/50 text-gray-300";
        if (color === "none") return "";
        return "bg-gradient-to-r from-blue-500/90 to-blue-600/90 active:from-blue-600 active:to-blue-700 md:hover:from-blue-600 md:hover:to-blue-700 border border-blue-500/50";
    }, [color]);

    const isIconOnly = !text;
    const buttonWidth = isIconOnly ? "aspect-square w-auto shrink-0" : !widthMin ? "w-full" : "w-min";

    let path = '';
    if (routeKey) path = getPathByKey(routeKey);
    if (!path && href) path = href;

    const renderButton = () => (
        <button
            type={formSubmit ? 'submit' : 'button'}
            onClick={onClick}
            className={cn(
                buttonWidth,
                (disabled || loading) ? "bg-gradient-to-r from-gray-700/50 to-gray-600/50 cursor-not-allowed text-gray-500 border border-gray-600/30" : buttonColor,
                "relative flex !font-extrabold text-sm flex-row justify-center items-center text-nowrap transition-all duration-200 gap-3  rounded-xl shadow-sm",
                `${!text ? 'py-2.5 px-2.5' : 'py-2.5 px-4'} `,
                className
            )}
            disabled={disabled || loading}
        >
            <span className={cn("flex flex-row items-center justify-center gap-3", loading && "invisible")}>
                {Array.isArray(icon)
                    ? icon.map((el, idx) => el && React.cloneElement(el as React.ReactElement, { key: (el as any).key ?? idx }))
                    : icon}
                {text}
            </span>

            {loading && (
                <span className="absolute inset-0 flex items-center justify-center">
                    <motion.span
                        style={{ width: 16, height: 16, border: '2px solid #fff', borderTop: '2px solid transparent', borderRadius: '50%', display: 'inline-block', willChange: 'transform' }}
                        animate={{ rotate: 360 }}
                        transition={{ repeat: Infinity, duration: 1, ease: "linear" }}
                    />
                </span>
            )}
        </button>
    );

    const asLink = !formSubmit && !!path;

    return asLink ? (
        <NavLink to={path} target={openNewPage ? "_blank" : ''} className={cn("flex z-10", isIconOnly ? "w-auto shrink-0" : buttonWidth)}>
            {renderButton()}
        </NavLink>
    ) : (
        renderButton()
    );
});
