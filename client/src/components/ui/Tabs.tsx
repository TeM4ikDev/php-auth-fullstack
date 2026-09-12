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
    /** @deprecated сегменты делят ширину поровну через flex-1 */
    gridCols?: number;
}

// то же easing, что у шторки: переезд должен читаться как одно движение, а не пружина
const INDICATOR_TRANSITION = { duration: 0.28, ease: [0.22, 1, 0.36, 1] } as const;

export const Tabs: React.FC<TabsProps> = ({ tabs, activeTab, onChange, className, variant = "equal" }) => {
    const activeTabRef = useRef<HTMLButtonElement>(null);
    // тот же ref и для автоскролла к активной вкладке, и для свайпа мышью
    const scrollRef = useDragScroll<HTMLDivElement>();
    const reduceMotion = useReducedMotion();
    // layoutId общий на весь документ, поэтому у каждого набора вкладок он должен быть свой,
    // иначе подложка «перелетала» бы между разными Tabs на странице
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
                                // relative под подложку; truncate уехал на текст, иначе overflow
                                // обрезал бы её во время переезда между вкладками
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

                            {/* поверх подложки: без своего контекста наложения текст ушёл бы под неё */}
                            <span className="relative block truncate">{tab.label}</span>
                        </button>
                    </Fragment>
                );
            })}
        </div>
    );
};
