import { cn } from "@/utils/cn";
import { motion, useReducedMotion } from "framer-motion";
import { ChevronDown } from "lucide-react";
import React, { type ReactNode, useCallback, useMemo, useRef, useState } from "react";

export interface BlockProps {
    children?: ReactNode;
    className?: string;
    variant?: 'default' | 'lighter' | 'darker' | 'transparent';
    title?: ReactNode;
    subtitle?: ReactNode;
    icons?: ReactNode[];
    canCollapse?: boolean;
    isCollapsedInitially?: boolean;
    titleCenter?: boolean;
    hugeTitle?: boolean;
    smallTitle?: boolean;
    mediumTitle?: boolean;
    overflowHidden?: boolean;
    /** fade content in from a dark overlay when the block appears */
    appear?: boolean;
    onClick?: () => void;
    disabled?: boolean;
}

/** fade-in duration in seconds */
const APPEAR_DURATION = 0.45;

export const Block = React.memo(({
    children,
    className,
    variant = 'default',
    title,
    subtitle,
    icons: Icons,
    canCollapse = false,
    isCollapsedInitially = false,
    titleCenter = false,
    hugeTitle = false,
    smallTitle = false,
    mediumTitle = false,
    overflowHidden = false,
    appear,
    onClick,
    disabled = false,
}: BlockProps) => {
    const [isCollapsed, setIsCollapsed] = useState(isCollapsedInitially);
    const blockRef = useRef<HTMLDivElement>(null);
    const reduceMotion = useReducedMotion();

    const handleCollapse = useCallback((value: boolean) => setIsCollapsed(value), []);

    const getBackgroundColor = useMemo(() => {
        switch (variant) {
            case 'lighter':
                return 'bg-back-alpha-xs';
            case 'darker':
                return 'bg-blackSabath-900';
            case 'transparent':
                return 'bg-transparent border-none !shadow-none !p-0 !rounded-none';
            default:
                return 'bg-back-alpha-xs';
        }
    }, [variant]);

    return (
        <div
            ref={blockRef}
            className={cn(
                getBackgroundColor,
                `flex w-full gap-3 p-3 h-min min-h-0 flex-col relative shadow-lg rounded-3xl border border-pablo-border `,
                overflowHidden && '!overflow-hidden',
                className,
                onClick && 'cursor-pointer',
                disabled && 'opacity-50 cursor-not-allowed'
            )}
            onClick={onClick}
        >
            {disabled && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.3, ease: "easeIn" }}
                    className="absolute  top-0 left-0 w-full h-full bg-black/50 z-20 rounded-xl"
                />
            )}

            {appear && !reduceMotion && (
                <motion.span
                    aria-hidden
                    initial={{ opacity: 1 }}
                    animate={{ opacity: 0 }}
                    transition={{ duration: APPEAR_DURATION, ease: "easeOut" }}
                    className="pointer-events-none absolute inset-0 z-10 rounded-[inherit] bg-pablo-darker"
                />
            )}

            {(title || Icons || canCollapse) && (
                <div className={cn("flex flex-col gap-2 w-auto items-center justify-center", canCollapse && 'cursor-pointer !flex-row')}
                    onClick={() => canCollapse && handleCollapse(!isCollapsed)}>
                    <div className="flex flex-col items-start gap-1 w-full h-min">
                        <div className="flex w-full h-auto gap-2">
                            <div className={cn("flex h-full w-full items-center", titleCenter && '!justify-center text-center')}>
                                <div className={cn("flex items-center justify-center", Icons && Icons.length > 0 && 'mr-2')}>
                                    {Icons && Icons.map((Icon, index) => (
                                        <div key={index} className="flex items-center justify-center">{Icon}</div>
                                    ))}
                                </div>
                                {title && (
                                    typeof title === 'string'
                                        ? <h3 className={cn("text-lg items-center h-min font-bold", hugeTitle && '!text-3xl lg:!text-4xl', mediumTitle && '!text-2xl lg:!text-3xl', smallTitle && '!text-sm')}>{title}</h3>
                                        : title
                                )}
                            </div>
                        </div>
                        {subtitle && (
                            <p className={cn("!text-xs font-bold md:text-lg w-full text-gray-400", titleCenter && '!text-center', smallTitle && '!text-xs')}>{subtitle}</p>
                        )}
                    </div>
                    {canCollapse && (
                        <button
                            onClick={() => handleCollapse(!isCollapsed)}
                            className="p-1 rounded-md hover:bg-pablo-cardLight transition-colors"
                            aria-label={isCollapsed ? 'Expand block' : 'Collapse block'}
                        >
                            <motion.div animate={{ rotate: isCollapsed ? 0 : 180 }} transition={{ duration: 0.2, ease: "easeInOut" }} style={{ willChange: 'transform' }}>
                                <ChevronDown className="w-5 h-5" />
                            </motion.div>
                        </button>
                    )}
                </div>
            )}

            {!isCollapsed && children}
        </div>
    );
});
