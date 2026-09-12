import { cn } from "@/utils/cn";
import { useEffect, useRef, useState } from "react";

interface SliderProps {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max?: number;
    step?: number;
    label?: string;
    disabled?: boolean;
    className?: string;
    /** Задержка перед вызовом onChange после того, как пользователь перестал двигать ползунок (мс). */
    debounceMs?: number;
}

export function Slider({
    value, onChange, min = 0, max = 100, step = 1, label, disabled = false, className, debounceMs = 400,
}: SliderProps) {
    // локальное значение — двигается мгновенно, чтобы ползунок не лагал;
    // наружу (onChange -> запрос к бэку) отдаём с задержкой после остановки
    const [localValue, setLocalValue] = useState(value);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        setLocalValue(value);
    }, [value]);

    useEffect(() => () => {
        if (timerRef.current) clearTimeout(timerRef.current);
    }, []);

    const handleChange = (v: number) => {
        setLocalValue(v);
        if (timerRef.current) clearTimeout(timerRef.current);
        timerRef.current = setTimeout(() => onChange(v), debounceMs);
    };

    return (
        <div className={cn("rounded-2xl bg-pablo-card border border-pablo-border px-4 py-3", disabled && "opacity-50", className)}>
            {label && (
                <div className="mb-2 flex items-center justify-between">
                    <span className="text-sm font-semibold text-white">{label}</span>
                    <span className="rounded-full bg-gold-500/10 px-2 py-0.5 text-xs font-bold text-gold-400">{localValue}</span>
                </div>
            )}
            <input
                type="range"
                min={min}
                max={max}
                step={step}
                value={localValue}
                disabled={disabled}
                onChange={(e) => handleChange(Number(e.target.value))}
                className="w-full accent-gold-500 disabled:cursor-not-allowed"
            />
        </div>
    );
}
