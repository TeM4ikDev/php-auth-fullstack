import { cn } from "@/utils/cn";
import { Check } from "lucide-react";
import type { ReactElement, ReactNode } from "react";

export type ListMarker = "radio" | "checkbox" | "none";

export interface ListItem<T extends string = string> {
    value: T;
    label: ReactNode;
    description?: ReactNode;
    icon?: ReactNode;
    disabled?: boolean;
}

interface ListBaseProps<T extends string> {
    items: ListItem<T>[];
    marker?: ListMarker;
    className?: string;
    itemClassName?: string;
}

interface ListSingleProps<T extends string> extends ListBaseProps<T> {
    multiple?: false;
    value?: T | null;
    onSelect?: (value: T) => void;
}

interface ListMultipleProps<T extends string> extends ListBaseProps<T> {
    multiple: true;
    value?: T[];
    onSelect?: (value: T[]) => void;
    selectAllValue?: T;
}

type ListProps<T extends string> = ListSingleProps<T> | ListMultipleProps<T>;

const RadioMarker = ({ checked }: { checked: boolean }) => (
    <span
        className={cn(
            "flex aspect-square h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 transition-colors",
            checked ? "border-gold-500" : "border-text-secondary"
        )}
    >
        {checked && <span className="h-[11px] w-[11px] shrink-0 rounded-full bg-gold-500" />}
    </span>
);

const CheckboxMarker = ({ checked }: { checked: boolean }) => (
    <span
        className={cn(
            "flex aspect-square h-6 w-6 shrink-0 items-center justify-center rounded-[0.4375rem] border-2 transition-colors",
            checked ? "border-gold-500 bg-gold-500" : "border-text-secondary bg-transparent"
        )}
    >
        {checked && <Check className="h-4 w-4 text-text-primary-inverted" strokeWidth={3} />}
    </span>
);

export function List<T extends string>(props: ListSingleProps<T>): ReactElement;
export function List<T extends string>(props: ListMultipleProps<T>): ReactElement;
export function List<T extends string>(props: ListProps<T>) {
    const { items, className, itemClassName } = props;
    const multiple = props.multiple ?? false;
    const requestedMarker = props.marker ?? (multiple ? "checkbox" : "radio");
    const marker = multiple && requestedMarker === "radio" ? "checkbox" : requestedMarker;
    const selectable = !!props.onSelect;

    const isChecked = (value: T) =>
        props.multiple ? (props.value ?? []).includes(value) : props.value === value;

    const handleSelect = (value: T) => {
        if (!props.onSelect) return;

        if (!props.multiple) {
            props.onSelect(value);
            return;
        }

        const current = props.value ?? [];
        const selectAll = props.selectAllValue;
        const selectableValues = items.filter((item) => !item.disabled).map((item) => item.value);

        if (selectAll !== undefined && value === selectAll) {
            props.onSelect(current.includes(selectAll) ? [] : selectableValues);
            return;
        }

        const next = current.includes(value) ? current.filter((v) => v !== value) : [...current, value];

        if (selectAll === undefined) {
            props.onSelect(next);
            return;
        }

        const others = selectableValues.filter((v) => v !== selectAll);
        const allSelected = others.length > 0 && others.every((v) => next.includes(v));

        props.onSelect(allSelected ? [...next.filter((v) => v !== selectAll), selectAll] : next.filter((v) => v !== selectAll));
    };

    return (
        <ul
            role={selectable ? (multiple ? "group" : "radiogroup") : "list"}
            className={cn("flex w-full gap-5 flex-col", className)}
        >
            {items.map((item) => {
                const checked = isChecked(item.value);

                const content = (
                    <>
                        {marker === "radio" && <RadioMarker checked={checked} />}
                        {marker === "checkbox" && <CheckboxMarker checked={checked} />}
                        {item.icon && <span className="flex shrink-0 items-center">{item.icon}</span>}
                        <span className="flex min-w-0 flex-1 flex-col gap-1 py-0.5">
                            <span
                                className={cn(
                                    "text-sm  leading-[1.125rem]",
                                    marker === "none" && checked ? "text-gold-500" : "text-text-primary"
                                )}
                            >
                                {item.label}
                            </span>
                            {item.description && (
                                <span className="text-xs font-normal leading-4 text-text-secondary">
                                    {item.description}
                                </span>
                            )}
                        </span>
                    </>
                );

                const itemClasses = cn(
                    "flex w-full items-center gap-2 text-left",
                    item.disabled && "cursor-not-allowed opacity-50",
                    itemClassName
                );

                return (
                    <li key={item.value} className="w-full">
                        {selectable ? (
                            <button
                                type="button"
                                role={multiple ? "checkbox" : "radio"}
                                aria-checked={checked}
                                disabled={item.disabled}
                                onClick={() => handleSelect(item.value)}
                                className={itemClasses}
                            >
                                {content}
                            </button>
                        ) : (
                            <div className={itemClasses}>{content}</div>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}
