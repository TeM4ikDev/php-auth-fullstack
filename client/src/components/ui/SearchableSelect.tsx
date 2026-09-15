import { cn } from "@/utils/cn";
import { Check, ChevronDown, Search } from "lucide-react";
import { useMemo, useState } from "react";

export type SelectOption = { value: string; label: string; image?: string };

// How many rows we render at once — without this cap, a list of thousands of options
// (e.g. combining models/symbols across all collections with no gift selected)
// synchronously creates thousands of DOM nodes and freezes the tab on open.
const MAX_VISIBLE_OPTIONS = 150;

/** Searchable dropdown, single select. Open state is controlled externally (to hide other fields). */
export function SearchableSelect({
    placeholder, value, options, onChange, withImages, invert, open, onOpenChange, disabled,
}: {
    placeholder: string;
    value: string | null;
    options: SelectOption[];
    onChange: (v: string | null) => void;
    withImages?: boolean;
    invert?: boolean;
    open: boolean;
    onOpenChange: (o: boolean) => void;
    disabled?: boolean;
}) {
    const [query, setQuery] = useState("");

    const matched = useMemo(
        () => options.filter((o) => o.label.toLowerCase().includes(query.toLowerCase())),
        [options, query]
    );
    const filtered = useMemo(() => matched.slice(0, MAX_VISIBLE_OPTIONS), [matched]);
    const hiddenCount = matched.length - filtered.length;
    const selected = options.find((o) => o.value === value);

    const pick = (v: string | null) => {
        onChange(v);
        onOpenChange(false);
        setQuery("");
    };

    return (
        <div className="w-full">
            <button
                type="button"
                disabled={disabled}
                onClick={() => onOpenChange(!open)}
                className={cn(
                    "w-full flex items-center justify-between gap-1 px-4 py-2.5 rounded-full bg-pablo-card border text-left",
                    open ? "border-gold-500" : "border-pablo-border",
                    disabled && "opacity-50 cursor-not-allowed"
                )}
            >
                <span className={cn("flex items-center gap-2 truncate", selected ? "text-white" : "text-gray-400")}>
                    {withImages && selected?.image && <img src={selected.image} alt="" className={cn("w-5 h-5 rounded object-contain", invert && "invert")} onError={(e) => (e.currentTarget.style.display = "none")} />}
                    {selected ? selected.label : placeholder}
                </span>
                <ChevronDown className={cn("w-4 h-4 text-gray-400 transition-transform", open && "rotate-180")} />
            </button>

            {!disabled && open && (
                <div className="mt-2 w-full rounded-lg bg-pablo-dark border border-gold-500/60 shadow-2xl overflow-hidden">
                    <div className="p-2 border-b border-pablo-border">
                        <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-pablo-card border border-pablo-border">
                            <Search className="w-4 h-4 text-gray-400" />
                            <input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Search…"
                                className="w-full bg-transparent outline-none text-sm text-white placeholder-gray-500"
                            />
                        </div>
                    </div>
                    <div className="max-h-72 overflow-y-auto">
                        <button
                            type="button"
                            onClick={() => pick(null)}
                            className="w-full text-left px-4 py-2.5 text-sm text-gray-400 hover:bg-pablo-cardLight"
                        >
                            Any
                        </button>
                        {filtered.map((o) => (
                            <button
                                key={o.value}
                                type="button"
                                onClick={() => pick(o.value)}
                                className="w-full flex items-center gap-3 text-left px-4 py-2.5 text-sm text-white  hover:bg-pablo-cardLight"
                            >
                                {withImages && o.image && <img src={o.image} alt="" className={cn("w-6 h-6 rounded object-contain", invert && "invert")} onError={(e) => (e.currentTarget.style.display = "none")} />}
                                {o.label}
                            </button>
                        ))}
                        {!filtered.length && <p className="px-4 py-3 text-sm text-gray-500">Nothing found</p>}
                        {hiddenCount > 0 && (
                            <p className="px-4 py-2 text-xs text-gray-500">{hiddenCount} more — refine your search</p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

/** Searchable dropdown, multi select (checkboxes, "Any" = empty array). */
export function MultiSearchableSelect({
    placeholder, values, options, onChange, withImages, invert, open, onOpenChange, disabled,
}: {
    placeholder: string;
    values: string[];
    options: SelectOption[];
    onChange: (v: string[]) => void;
    withImages?: boolean;
    invert?: boolean;
    open: boolean;
    onOpenChange: (o: boolean) => void;
    disabled?: boolean;
}) {
    const [query, setQuery] = useState("");

    const matched = useMemo(
        () => options.filter((o) => o.label.toLowerCase().includes(query.toLowerCase())),
        [options, query]
    );
    const filtered = useMemo(() => matched.slice(0, MAX_VISIBLE_OPTIONS), [matched]);
    const hiddenCount = matched.length - filtered.length;
    const selected = options.filter((o) => values.includes(o.value));

    const toggle = (v: string) => {
        onChange(values.includes(v) ? values.filter((x) => x !== v) : [...values, v]);
    };

    const clear = () => {
        onChange([]);
        onOpenChange(false);
        setQuery("");
    };

    const summary = !selected.length
        ? placeholder
        : selected.length === 1
            ? selected[0].label
            : `${selected[0].label} +${selected.length - 1}`;

    return (
        <div className="w-full">
            <button
                type="button"
                disabled={disabled}
                onClick={() => onOpenChange(!open)}
                className={cn(
                    "w-full flex items-center justify-between gap-1 px-4 py-2.5 rounded-full bg-pablo-card border text-left",
                    open ? "border-gold-500" : "border-pablo-border",
                    disabled && "opacity-50 cursor-not-allowed"
                )}
            >
                <span className={cn("flex items-center gap-2 truncate", selected.length ? "text-white" : "text-gray-400")}>
                    {withImages && selected[0]?.image && <img src={selected[0].image} alt="" className={cn("w-5 h-5 rounded object-contain", invert && "invert")} onError={(e) => (e.currentTarget.style.display = "none")} />}
                    {summary}
                </span>
                <ChevronDown className={cn("w-4 h-4 text-gray-400 transition-transform", open && "rotate-180")} />
            </button>

            {!disabled && open && (
                <div className="mt-2 w-full rounded-lg bg-pablo-dark border border-gold-500/60 shadow-2xl overflow-hidden">
                    <div className="p-2 border-b border-pablo-border">
                        <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-pablo-card border border-pablo-border">
                            <Search className="w-4 h-4 text-gray-400" />
                            <input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Search…"
                                className="w-full bg-transparent outline-none text-sm text-white placeholder-gray-500"
                            />
                        </div>
                    </div>
                    <div className="max-h-72 overflow-y-auto">
                        <button
                            type="button"
                            onClick={clear}
                            className="w-full text-left px-4 py-2.5 text-sm text-gray-400 hover:bg-pablo-cardLight"
                        >
                            Any (clear selection)
                        </button>
                        {filtered.map((o) => {
                            const checked = values.includes(o.value);
                            return (
                                <button
                                    key={o.value}
                                    type="button"
                                    onClick={() => toggle(o.value)}
                                    className="w-full flex items-center gap-3 text-left px-4 py-2.5 text-sm text-white hover:bg-pablo-cardLight"
                                >
                                    <span className={cn(
                                        "flex h-4 w-4 shrink-0 items-center justify-center rounded border",
                                        checked ? "bg-gold-500 border-gold-500" : "border-pablo-border"
                                    )}>
                                        {checked && <Check className="h-3 w-3 text-pablo-darker" strokeWidth={3} />}
                                    </span>
                                    {withImages && o.image && <img src={o.image} alt="" className={cn("w-6 h-6 rounded object-contain", invert && "invert")} onError={(e) => (e.currentTarget.style.display = "none")} />}
                                    {o.label}
                                </button>
                            );
                        })}
                        {!filtered.length && <p className="px-4 py-3 text-sm text-gray-500">Nothing found</p>}
                        {hiddenCount > 0 && (
                            <p className="px-4 py-2 text-xs text-gray-500">{hiddenCount} more — refine your search</p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
