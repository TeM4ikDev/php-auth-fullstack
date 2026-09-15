import { cn } from "@/utils/cn";
import { useRef } from "react";

export interface Range {
    min: number;
    max: number;
}

interface RangeSliderProps {
    value: Range;
    onChange: (value: Range) => void;
    /** scale bounds, not to be confused with value.min / value.max */
    lowerBound?: number;
    upperBound?: number;
    step?: number;
    formatLabel?: (value: number) => string;
    disabled?: boolean;
    className?: string;
}

type Thumb = "min" | "max";

// the thumb's travel is narrowed by its own width, otherwise at 0% and 100% half the circle sticks out past the block
const THUMB_SIZE = 26;

const clamp = (value: number, from: number, to: number) => Math.min(Math.max(value, from), to);

export const RangeSlider = ({
    value,
    onChange,
    lowerBound = 0,
    upperBound = 100,
    step = 1,
    formatLabel = (v) => `${v}%`,
    disabled = false,
    className,
}: RangeSliderProps) => {
    const trackRef = useRef<HTMLDivElement>(null);
    const dragging = useRef<Thumb | null>(null);

    const span = upperBound - lowerBound;
    const toPercent = (v: number) => ((v - lowerBound) / span) * 100;
    // position of the thumb's center within the track, narrowed by its own width
    const toOffset = (v: number) =>
        `calc(${THUMB_SIZE / 2}px + (100% - ${THUMB_SIZE}px) * ${toPercent(v) / 100})`;

    const valueFromClientX = (clientX: number) => {
        const track = trackRef.current;
        if (!track) return lowerBound;
        const rect = track.getBoundingClientRect();
        const ratio = clamp((clientX - rect.left) / rect.width, 0, 1);
        const raw = lowerBound + ratio * span;
        return clamp(Math.round(raw / step) * step, lowerBound, upperBound);
    };

    // thumbs cannot pass through each other
    const commit = (thumb: Thumb, next: number) => {
        if (thumb === "min") onChange({ min: Math.min(next, value.max), max: value.max });
        else onChange({ min: value.min, max: Math.max(next, value.min) });
    };

    const startDrag = (thumb: Thumb) => (event: React.PointerEvent<HTMLDivElement>) => {
        if (disabled) return;
        dragging.current = thumb;
        event.currentTarget.setPointerCapture(event.pointerId);
    };

    const handleMove = (event: React.PointerEvent<HTMLDivElement>) => {
        if (!dragging.current) return;
        commit(dragging.current, valueFromClientX(event.clientX));
    };

    const endDrag = (event: React.PointerEvent<HTMLDivElement>) => {
        dragging.current = null;
        event.currentTarget.releasePointerCapture(event.pointerId);
    };

    // tapping the track pulls the nearest thumb toward it
    const handleTrackPointerDown = (event: React.PointerEvent<HTMLDivElement>) => {
        if (disabled) return;
        const next = valueFromClientX(event.clientX);
        commit(Math.abs(next - value.min) <= Math.abs(next - value.max) ? "min" : "max", next);
    };

    const handleKeyDown = (thumb: Thumb) => (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (disabled) return;
        const delta = event.key === "ArrowRight" || event.key === "ArrowUp" ? step
            : event.key === "ArrowLeft" || event.key === "ArrowDown" ? -step
                : 0;
        if (!delta) return;
        event.preventDefault();
        commit(thumb, clamp(value[thumb] + delta, lowerBound, upperBound));
    };

    const thumbProps = (thumb: Thumb) => ({
        role: "slider" as const,
        tabIndex: disabled ? -1 : 0,
        "aria-valuemin": lowerBound,
        "aria-valuemax": upperBound,
        "aria-valuenow": value[thumb],
        "aria-valuetext": formatLabel(value[thumb]),
        "aria-disabled": disabled,
        onPointerDown: startDrag(thumb),
        onPointerMove: handleMove,
        onPointerUp: endDrag,
        onPointerCancel: endDrag,
        onKeyDown: handleKeyDown(thumb),
        style: { left: toOffset(value[thumb]) },
        className: cn(
            "absolute top-1/2 h-[26px] w-[26px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-white",
            "drop-shadow-[0_2px_2px_rgba(24,39,75,0.12)] focus:outline-none focus-visible:ring-2 focus-visible:ring-border-brended",
            disabled ? "cursor-not-allowed" : "cursor-grab touch-none active:cursor-grabbing"
        ),
    });

    return (
        <div className={cn("flex w-full flex-col gap-2", disabled && "opacity-50", className)}>
            <div className="flex w-full gap-1 text-xs font-normal leading-4">
                <span className="flex-1 text-text-secondary">{formatLabel(value.min)}</span>
                <span className="flex-1 text-right text-gold-500">{formatLabel(value.max)}</span>
            </div>

            <div className="relative h-[23px] w-full">
                <div
                    ref={trackRef}
                    onPointerDown={handleTrackPointerDown}
                    className="absolute top-2 h-1.5 rounded-full bg-back-alpha-xs"
                    style={{ left: THUMB_SIZE / 2, right: THUMB_SIZE / 2 }}
                />
                <div
                    className="pointer-events-none absolute top-2 h-1.5 rounded-full bg-border-brended"
                    style={{
                        left: toOffset(value.min),
                        width: `calc((100% - ${THUMB_SIZE}px) * ${(toPercent(value.max) - toPercent(value.min)) / 100})`,
                    }}
                />
                <div {...thumbProps("min")} />
                <div {...thumbProps("max")} />
            </div>
        </div>
    );
};
