import { useDragScroll } from "@/hooks/useDragScroll";
import { cn } from "@/utils/cn";
import { motion, useReducedMotion } from "framer-motion";
import React, { Fragment, useEffect, useId, useRef } from "react";

export interface TabItem {
    label: string;
    value: string | number;
}

export interface TabsProps {
    tabs: TabItem[];
    activeTab: string | number;
    onChange: (value: string | number) => void;
    className?: string;
    variant?: "equal" | "scroll";
    /** @deprecated segments split the width evenly via flex-1 */
    gridCols?: number;
}

// same easing as the sheet: the move should read as one motion, not a spring
const INDICATOR_TRANSITION = { duration: 0.28, ease: [0.22, 1, 0.36, 1] } as const;

export const Tabs: React.FC<TabsProps> = ({ tabs, activeTab, onChange, className, variant = "equal" }) => {
    const activeTabRef = useRef<HTMLButtonElement>(null);
    // same ref for both auto-scrolling to the active tab and mouse-drag swiping
    const scrollRef = useDragScroll<HTMLDivElement>();
    const reduceMotion = useReducedMotion();
    // layoutId is shared document-wide, so each set of tabs needs its own,
    // otherwise the indicator would "fly" between different Tabs on the page
    const indicatorId = useId();

    useEffect(() => {
        if (variant === "scroll" && activeTabRef.current && scrollRef.current) {
            const container = scrollRef.current;
            const target = activeTabRef.current;
            const targetCenter = target.offsetLeft + target.offsetWidth / 2;
            const nextScrollLeft = Math.max(0, targetCenter - container.clientWidth / 2);
            container.scrollTo({ left: nextScrollLeft, behavior: "smooth" });
        }
    }, [activeTab, variant]);

    return (
        <div
            ref={scrollRef}
            className={cn(
                "flex w-full items-center overflow-hidden rounded-[28px] bg-back-alpha-xs p-[2px]",
                variant === "scroll" && "overflow-x-auto scrollbar-hide",
                className
            )}
        >
            {tabs.map((tab, index) => {
                const isActive = activeTab === tab.value;
                const nextToActive = isActive || activeTab === tabs[index - 1]?.value;

                return (
                    <Fragment key={tab.value}>
                        {index > 0 && (
                            <span
                                aria-hidden
                                className={cn(
                                    "h-3 w-px shrink-0 rounded-[0.5px] bg-border-secon transition-opacity",
                                    nextToActive && "opacity-0"
                                )}
                            />
                        )}
                        <button
                            type="button"
                            ref={isActive ? activeTabRef : null}
                            onClick={() => onChange(tab.value)}
                            className={cn(
                                // relative for the indicator; truncate moved to the text, otherwise overflow
                                // would clip the indicator while it's moving between tabs
                                "relative rounded-full p-2 text-center text-xs font-semibold leading-4 transition-colors",
                                variant === "equal" ? "min-w-0 flex-1" : "shrink-0 px-4",
                                isActive ? "text-text-primary-inverted" : "text-text-secondary"
                            )}
                        >
                            {isActive && (
                                <motion.span
                                    aria-hidden
                                    layoutId={indicatorId}
                                    transition={reduceMotion ? { duration: 0 } : INDICATOR_TRANSITION}
                                    className="absolute inset-0 rounded-full bg-back-brended"
                                />
                            )}

                            {/* above the indicator: without its own stacking context the text would end up under it */}
                            <span className="relative block truncate">{tab.label}</span>
                        </button>
                    </Fragment>
                );
            })}
        </div>
    );
};
