import { useDragScroll } from "@/hooks/useDragScroll";
import { cn } from "@/utils/cn";
import { ChevronDown, X } from "lucide-react";
import { type ReactNode } from "react";

interface ChipProps {
    text: ReactNode;
    onClick?: () => void;
    /** chevron справа — чип открывает список или шторку */
    chevron?: boolean;
    /** картинка слева: url или готовый узел (например, цветной кружок фона) */
    image?: string | ReactNode;
    /** крестик справа — чип снимает выбор */
    onRemove?: () => void;
    active?: boolean;
    disabled?: boolean;
    /** m — обычный чип-фильтр, s — компактный (пресеты внутри карточек) */
    size?: "s" | "m";
    className?: string;
}

export const Chip = ({
    text,
    onClick,
    chevron = false,
    image,
    onRemove,
    active = false,
    disabled = false,
    size = "m",
    className,
}: ChipProps) => {
    const content = (
        <>
            {image && (
                <span className="flex h-5 w-5 shrink-0 items-center justify-center overflow-hidden rounded-full">
                    {typeof image === "string" ? <img src={image} alt="" className="h-full w-full object-cover" /> : image}
                </span>
            )}
            {text}
            {chevron && <ChevronDown className="h-3 w-3 shrink-0" strokeWidth={2.5} />}
        </>
    );

    const shell = cn(
        "flex font-normal shrink-0 items-center whitespace-nowrap rounded-full bg-back-alpha-xs text-xs leading-4 text-text-primary transition-colors",
        active && "border border-border-brended",
        disabled && "cursor-not-allowed opacity-50",
        className
    );

    // с крестиком чип состоит из двух кликабельных зон, поэтому не может быть одной кнопкой
    if (onRemove) {
        return (
            <span className={cn(shell, "justify-center")}>
                <button
                    type="button"
                    onClick={onClick}
                    disabled={disabled}
                    className={cn("flex items-center gap-2.5 pl-3", size === "s" ? "py-1.5" : "py-2.5", !onClick && "cursor-default")}
                >
                    {content}
                </button>
                <button
                    type="button"
                    onClick={onRemove}
                    disabled={disabled}
                    aria-label="Убрать"
                    className="flex shrink-0 items-center justify-center rounded-xl p-[9px] text-text-primary transition-opacity hover:opacity-70"
                >
                    <X className="h-[18px] w-[18px]" />
                </button>
            </span>
        );
    }

    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className={cn(shell, "justify-center gap-2.5 px-3", size === "s" ? "py-1.5" : "py-2.5")}
        >
            {content}
        </button>
    );
};

export const ChipGroup = ({ children, className }: { children: ReactNode; className?: string }) => {
    // ряд чипов почти всегда шире экрана, поэтому сразу учим его листаться мышью
    const ref = useDragScroll<HTMLDivElement>();

    return (
        <div ref={ref} className={cn("flex items-center gap-2", className)}>{children}</div>
    );
};
