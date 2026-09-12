import { cn } from "@/utils/cn";
import { type ReactNode } from "react";

export type BadgeTone = "neutral" | "brand" | "success" | "error" | "warning" | "process";

interface BadgeProps {
    text: ReactNode;
    tone?: BadgeTone;
    className?: string;
}

interface DeltaBadgeProps {
    value: number;
    fractionDigits?: number;
    suffix?: string;
    className?: string;
}

const TONE_STYLES: Record<BadgeTone, string> = {
    neutral: "bg-white/[0.04] text-text-secondary",
    brand: "bg-gold-500/[0.08] text-gold-500",
    success: "bg-success-primary/30 text-success-secondary",
    error: "bg-price-down/20 text-error-secondary",
    warning: "bg-white/[0.04] text-warning-primary",
    process: "bg-white/[0.04] text-text-secondary",
};

const DOT_STYLES: Partial<Record<BadgeTone, string>> = {
    warning: "bg-warning-primary",
    process: "bg-gold-500",
};

export const Badge = ({ text, tone = "neutral", className }: BadgeProps) => {
    const dot = DOT_STYLES[tone];

    return (
        <span
            className={cn(
                "flex shrink-0 items-center justify-center gap-1 rounded-xl px-2 py-0.5",
                dot ? "text-[0.625rem] leading-[0.875rem]" : "text-xs leading-4",
                TONE_STYLES[tone],
                className
            )}
        >
            {dot && <span aria-hidden className={cn("h-1.5 w-1.5 shrink-0 rounded-full", dot)} />}
            {text}
        </span>
    );
};


export const DeltaBadge = ({ value, fractionDigits, suffix = "", className }: DeltaBadgeProps) => {
    const tone: BadgeTone = value === 0 ? "neutral" : value > 0 ? "success" : "error";
    const formatted = fractionDigits == null ? String(value) : value.toFixed(fractionDigits);

    return <Badge tone={tone} className={className} text={`${value > 0 ? "+" : ""}${formatted}${suffix}`} />;
};
