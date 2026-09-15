import { cn } from "@/utils/cn";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { useEffect, useState } from "react";
import { Modal } from "./Modal";
import { Block } from "./Block";
import { Button } from "./Button";

export interface MonthValue {
    year: number;
    /** 0 — January, 11 — December */
    month: number;
}

interface Props {
    isOpen: boolean;
    setIsOpen: (value: boolean) => void;
    value?: MonthValue | null;
    /** null — clears the filter: comes both from the "Reset" button and from re-clicking the selected month */
    onSelect: (value: MonthValue | null) => void;
    minYear?: number;
    maxYear?: number;
}

export const MONTHS = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

export const MonthPickerModal = ({ isOpen, setIsOpen, value = null, onSelect, minYear, maxYear }: Props) => {
    const [year, setYear] = useState(() => value?.year ?? new Date().getFullYear());

    useEffect(() => {
        if (isOpen) setYear(value?.year ?? new Date().getFullYear());
    }, [isOpen, value?.year]);

    const canGoBack = minYear === undefined || year > minYear;
    const canGoForward = maxYear === undefined || year < maxYear;

    const selectMonth = (month: number) => {
        const isSame = value?.year === year && value?.month === month;
        onSelect(isSame ? null : { year, month });
        setIsOpen(false);
    };

    const reset = () => {
        onSelect(null);
        setIsOpen(false);
    };

    return (
        <Modal isOpen={isOpen} setIsOpen={setIsOpen}>
            {/* <div className="flex flex-col gap-3 rounded-2xl drop-shadow-[0_4px_5px_rgba(0,0,0,0.15)]"> */}
            <Block className="flex-row justify-between items-center p-1">
                <Button
                    onClick={() => setYear((prev) => prev - 1)}
                    disabled={!canGoBack}
                    icon={<ChevronLeft className="h-5 w-5" />}
                    color="none"
                />

                <p className="text-[15px] font-semibold leading-6 text-[#f1f4f8]">{year}</p>

                <Button
                    onClick={() => setYear((prev) => prev + 1)}
                    disabled={!canGoForward}
                    icon={<ChevronRight className="h-5 w-5" />}
                    color="none"
                />
            </Block>

            <div className="grid grid-cols-3 gap-2">
                {MONTHS.map((label, month) => {
                    const selected = value?.year === year && value?.month === month;

                    return (
                        <Button
                            key={label}
                            aria-pressed={selected}
                            text={label}

                            onClick={() => selectMonth(month)}
                            className={cn(
                                selected ? "bg-back-brended text-text-primary-inverted" : "text-text-secondary"
                            )}

                            color="none"
                        />
                    );
                })}
            </div>
            {/* </div> */}

            {value && <Button color="gray" text="Reset" onClick={reset} />}
        </Modal>
    );
};
